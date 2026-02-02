<?php
/**
 * WordPress Plugin Framework
 *
 * Copyright (c) 2008-2026 DesignInk, LLC
 * Copyright (c) 2026 Kyle Niemiec
 *
 * This file is licensed under the GNU General Public License v3.0.
 * See the LICENSE file for details.
 *
 * @package WPPF
 */

namespace WPPF\CLI\Command;

use WPPF\CLI\Util\CliUtil;
use WPPF\CLI\Support\HelperBundle;
use WPPF\CLI\Util\StyleUtil;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use WPPF\CLI\Enum\ConsoleColor;
use WPPF\CLI\Support\PluginCliCommand;
use WPPF\v1_2_1\Framework\Utility;

/**
 * A command to create a meta box class and render template for a post type.
 */
#[AsCommand(
	description: 'Create a meta box class and render template from prompts.',
	name: 'make:meta-box'
)]
final class CreateMetaBoxCommand extends PluginCliCommand
{
	/** @var bool {@inheritDoc} */
	protected static bool $requiresAdminModule = true;

	/** @var bool {@inheritDoc} */
	protected static bool $requiresPostTypes = true;

	/** @var string The directory for admin meta boxes. */
	private const META_BOX_DIR = 'admin/includes/meta-boxes';

	/** @var string The directory for admin templates. */
	private const META_BOX_TEMPLATE_DIR = 'admin/templates';

	/** @var string The directory for admin screens. */
	private const SCREEN_DIR = 'admin/includes/screens';

	/** @var string The directory for post meta classes. */
	private const POST_META_DIR = 'includes/classes';

	/**
	 * Set up the helper variables, control message flow.
	 *
	 * @param InputInterface $input The terminal input interface.
	 * @param OutputInterface $output The terminal output interface.
	 *
	 * @return int The command success status.
	 */
	protected function execute( InputInterface $input, OutputInterface $output ): int
	{
		$output->writeln( StyleUtil::color(
			sprintf( 'Creating a new meta box.' ),
			ConsoleColor::BrightCyan
		) );

		$bundle = new HelperBundle( new QuestionHelper, $input, $output );
		$slug = basename( getcwd() );

		// Select post type for meta box.
		$selectedPostType = $this->promptForPostTypeFile( $bundle );
		$postTypePath = sprintf( '%s/%s/%s', getcwd(), CreatePostTypeCommand::POST_TYPES_DIR, $selectedPostType );
		$postTypeClass = Utility::get_file_class_name( $postTypePath );

		if ( '' === $postTypeClass ) {
			$output->writeln( StyleUtil::error( 'Could not determine the post type class name.' ) );
			return Command::FAILURE;
		}

		// Collect meta box details.
		$title = self::askMetaBoxTitle( $bundle );
		$snakeTitle = CliUtil::underscorify( $title );
		$metaBoxId = self::askMetaBoxId( $bundle, $snakeTitle );
		$metaBoxKey = self::askMetaBoxKey( $bundle, $snakeTitle . '_meta' );

		$className = sprintf( '%s_Meta_Box', CliUtil::underscorify( $metaBoxId, true ) );
		$templateSlug = Utility::slugify( $className );

		$metaBoxFile = self::metaBoxFilePath( $templateSlug );
		$renderTemplateFile = self::metaBoxTemplatePath( $templateSlug );

		if ( file_exists( $metaBoxFile ) || file_exists( $renderTemplateFile ) ) {
			$output->writeln( StyleUtil::error( 'Error: The meta box file or template already exists.' ) );
			return Command::FAILURE;
		}

		// Select post meta class to connect (optional).
		$metaInfo = self::selectPostMetaClass( $bundle );
		$metaClass = $metaInfo['class'] ?? null;
		$metaVariables = $metaInfo['variables'] ?? [];

		$pluginClass = self::getPluginClassName( $slug );

		// Apply the data to the meta box template.
		$metaBoxTemplate = CliUtil::applyTemplate(
			'MetaBox',
			[
				'{{class_name}}' => $className,
				'{{post_type_class}}' => $postTypeClass,
				'{{meta_box_key}}' => $metaBoxKey,
				'{{meta_box_title}}' => $title,
				'{{meta_box_id}}' => $metaBoxId,
				'{{plugin_class}}' => $pluginClass,
				'{{template_slug}}' => $templateSlug,
				'{{maybe_save_meta}}' => $metaClass ? self::buildSaveMetaSnippet( $metaClass ) : '',
				'{{maybe_import_data}}' => $metaClass ? self::buildImportDataSnippet( $metaVariables ) : '',
			]
		);

		// Apply the data to the render template.
		$renderTemplate = CliUtil::applyTemplate(
			'MetaBoxRender',
			[
				'{{class_name}}' => $className,
				'{{class_name_slug}}' => Utility::slugify( $className ),
				'{{maybe_import_meta}}' => $metaClass ? self::buildImportMetaSnippet( $metaClass ) : '',
				'{{render_body_html}}' => $metaClass
					? self::buildRenderBodyHtml( $metaVariables, $className )
					: self::renderFallbackHtml( $className ),
			]
		);

		if ( ! self::createFile( $metaBoxTemplate, $metaBoxFile ) ) {
			$output->writeln( StyleUtil::error( 'There was an error writing out the meta box file to disk.' ) );
			return Command::FAILURE;
		}

		if ( ! self::createFile( $renderTemplate, $renderTemplateFile ) ) {
			$output->writeln( StyleUtil::error( 'There was an error writing out the render template to disk.' ) );
			return Command::FAILURE;
		}

		$placementStatus = self::registerMetaBoxPlacement( $bundle, $postTypePath, $className );
		if ( Command::FAILURE === $placementStatus ) {
			return Command::FAILURE;
		}

		$output->writeln(
			StyleUtil::color(
				sprintf( 'Meta box class `%s` created at `%s`.', $className, $metaBoxFile ),
				ConsoleColor::BrightGreen
			)
		);

		$output->writeln(
			StyleUtil::color(
				sprintf( 'Render template created at `%s`.', $renderTemplateFile ),
				ConsoleColor::Gray
			)
		);

		return Command::SUCCESS;
	}

	/**
	 * Ask the user for the meta box title.
	 *
	 * @param HelperBundle $bundle The question helper and IO interfaces.
	 *
	 * @return string The title entered by the user.
	 */
	private static function askMetaBoxTitle( HelperBundle $bundle ): string
	{
		$question = new Question( 'What should the title of the meta box be? ' );

		$question->setValidator( function ( $value ): string {
			$clean = trim( strval( $value ) );
			if ( '' === $clean ) {
				throw new \RuntimeException( 'The title cannot be empty.' );
			}
			return $clean;
		} );

		return $bundle->helper->ask( $bundle->input, $bundle->output, $question );
	}

	/**
	 * Ask the user for the meta box ID.
	 *
	 * @param HelperBundle $bundle The question helper and IO interfaces.
	 * @param string $default The default ID.
	 *
	 * @return string The ID entered by the user.
	 */
	private static function askMetaBoxId( HelperBundle $bundle, string $default ): string
	{
		$question = new Question(
			sprintf(
				'What should the ID of the meta box be? %s ',
				StyleUtil::optional( sprintf( 'Default: %s', $default ) )
			),
			$default
		);

		$question->setValidator( self::snakeCaseValidator( $default ) );
		return $bundle->helper->ask( $bundle->input, $bundle->output, $question );
	}

	/**
	 * Ask the user for the meta box meta key.
	 *
	 * @param HelperBundle $bundle The question helper and IO interfaces.
	 * @param string $default The default key.
	 *
	 * @return string The meta key entered by the user.
	 */
	private static function askMetaBoxKey( HelperBundle $bundle, string $default ): string
	{
		$question = new Question(
			sprintf(
				'What should the meta key of the meta box be? %s ',
				StyleUtil::optional( sprintf( 'Default: %s', $default ) )
			),
			$default
		);

		$question->setValidator( self::snakeCaseValidator( $default ) );
		return $bundle->helper->ask( $bundle->input, $bundle->output, $question );
	}

	/**
	 * Return a callable function which can validate snake case.
	 *
	 * @param string $default The default value to use when input is empty.
	 *
	 * @return callable A callable which can evaluate snake case validity.
	 */
	private static function snakeCaseValidator( string $default ): callable
	{
		return function ( $value ) use ( $default ): string {
			$value = trim( strval( $value ) );

			if ( '' === $value ) {
				return $default;
			}

			if ( ! preg_match( '/^[a-z][a-z0-9_]*$/', $value ) ) {
				throw new \RuntimeException( 'Please use lower_snake_case (letters, numbers, underscores).' );
			}

			return $value;
		};
	}

	/**
	 * Select a Post_Meta class to connect to the meta box, if available.
	 *
	 * @param HelperBundle $bundle The IO interfaces for the terminal.
	 *
	 * @return array The selected class data.
	 */
	private static function selectPostMetaClass( HelperBundle $bundle ): array
	{
		$metaClasses = self::findPostMetaClasses();

		if ( empty( $metaClasses ) ) {
			return [];
		}

		$choices = array_merge( [ 'None (skip)' ], array_keys( $metaClasses ) );
		$question = new ChoiceQuestion(
			StyleUtil::color( 'Select a post meta class to attach (optional):', ConsoleColor::BrightYellow ),
			$choices
		);

		$selection = $bundle->helper->ask( $bundle->input, $bundle->output, $question );

		if ( 'None (skip)' === $selection ) {
			return [];
		}

		$metaInfo = $metaClasses[ $selection ];
		$variables = self::extractMetaVariables( $metaInfo['file'] );

		return [
			'class' => $metaInfo['class'],
			'file' => $metaInfo['file'],
			'variables' => $variables,
		];
	}

	/**
	 * Find Post_Meta classes in includes/classes using Utility and is_subclass_of.
	 *
	 * @return array The list of meta classes by display name.
	 */
	private static function findPostMetaClasses(): array
	{
		self::requirePostMetaDependencies();

		$metaDir = sprintf( '%s/%s', getcwd(), self::POST_META_DIR );
		if ( ! is_dir( $metaDir ) ) {
			return [];
		}

		$files = Utility::scandir( $metaDir, 'files' );
		$metaClasses = [];

		foreach ( $files as $file ) {
			if ( ! str_ends_with( $file, '.php' ) ) {
				continue;
			}

			$path = sprintf( '%s/%s', $metaDir, $file );
			$className = Utility::get_file_class_name( $path );

			if ( '' === $className ) {
				continue;
			}

			require_once $path;
			$namespace = Utility::get_file_namespace( $path );
			$qualified = $namespace ? sprintf( '\\%s\\%s', $namespace, $className ) : $className;

			if ( class_exists( $qualified ) && is_subclass_of( $qualified, '\WPPF\v1_2_1\WordPress\Post_Meta' ) ) {
				$display = Utility::class_basename( $qualified );
				$metaClasses[ $display ] = [
					'class' => $qualified,
					'file' => $path,
				];
			}
		}

		return $metaClasses;
	}

	/**
	 * Ensure Post Meta class dependencies are loaded for is_subclass_of checks.
	 */
	private static function requirePostMetaDependencies(): void
	{
		$root = dirname( __DIR__, 2 );
		$metaSchema = sprintf( '%s/includes/modules/wordpress-module/includes/classes/class-meta-schema.php', $root );
		$metaBase = sprintf( '%s/includes/modules/wordpress-module/includes/abstracts/class-meta.php', $root );
		$postMeta = sprintf( '%s/includes/modules/wordpress-module/includes/abstracts/class-post-meta.php', $root );

		if ( file_exists( $metaSchema ) ) {
			require_once $metaSchema;
		}

		if ( file_exists( $metaBase ) ) {
			require_once $metaBase;
		}

		if ( file_exists( $postMeta ) ) {
			require_once $postMeta;
		}
	}

	/**
	 * Extract variable names and types from a Post_Meta class generated defaults.
	 *
	 * @param string $file The meta class file path.
	 *
	 * @return array<string, string> The variable names and inferred types.
	 */
	private static function extractMetaVariables( string $file ): array
	{
		$contents = file_get_contents( $file );
		if ( false === $contents ) {
			return [];
		}

		if ( ! preg_match( '/\\$generated_values\\s*=\\s*\\[(.*?)\\];/s', $contents, $matches ) ) {
			return [];
		}

		$block = $matches[1];
		$lines = preg_split( '/\\r?\\n/', $block );
		$variables = [];

		foreach ( $lines as $line ) {
			$trimmed = trim( $line );

			if ( '' === $trimmed ) {
				continue;
			}

			if ( preg_match( '/^[\\\'"]([^\\\'"]+)[\\\'"]\\s*=>\\s*([^,]+),?$/', $trimmed, $matches ) ) {
				$name = $matches[1];
				$value = trim( $matches[2] );
				$variables[ $name ] = self::inferTypeFromValue( $value );
			}
		}

		return $variables;
	}

	/**
	 * Infer variable type from default values.
	 *
	 * @param string $value The default value string.
	 *
	 * @return string The inferred type.
	 */
	private static function inferTypeFromValue( string $value ): string
	{
		$value = trim( $value );

		if ( '[]' === $value || str_starts_with( $value, '[' ) || str_starts_with( $value, 'array' ) ) {
			return 'array';
		}

		if ( 'true' === $value || 'false' === $value ) {
			return 'boolean';
		}

		if ( preg_match( '/^-?\\d+\\.\\d+$/', $value ) ) {
			return 'float';
		}

		if ( preg_match( '/^-?\\d+$/', $value ) ) {
			return 'integer';
		}

		if ( preg_match( '/^([\\\'"]).*\\1$/', $value ) ) {
			return 'string';
		}

		return 'string';
	}

	/**
	 * Build the meta save snippet for the meta box template.
	 *
	 * @param string $metaClass The meta class name.
	 *
	 * @return string The formatted save snippet.
	 */
	private static function buildSaveMetaSnippet( string $metaClass ): string
	{
		$snippet = <<<PHP
// Load meta data for updating and validate changes
\$Meta = new {$metaClass}( \$Post );
\$Meta->import( \$data );
\$validation = \$Meta->validate();

// Save meta data if there are no errors
if ( is_wp_error( \$validation ) ) {
	Admin_Notices::error( Meta_Schema::create_error_message( \$validation ) );
} else {
	\$Meta->save();
}
PHP;

		return self::indentSnippet( $snippet, "\t\t\t", true );
	}

	/**
	 * Build the import data snippet for boolean/array values.
	 *
	 * @param array<string, string> $variables The variable names and types.
	 *
	 * @return string The formatted import snippet.
	 */
	private static function buildImportDataSnippet( array $variables ): string
	{
		$sections = [];

		foreach ( $variables as $name => $type ) {
			if ( 'boolean' === $type ) {
				$sections[] = str_replace(
					'{{var_name}}',
					$name,
					<<<'PHP'
// Boolean values require text->boolean translation
if ( isset( $data['{{var_name}}'] ) && 'yes' === $data['{{var_name}}'] ) {
	$data['{{var_name}}'] = true;
} else {
	$data['{{var_name}}'] = false;
}
PHP
				);
			} elseif ( 'array' === $type ) {
				$sections[] = str_replace(
					'{{var_name}}',
					$name,
					<<<'PHP'
// Array values must be manually managed
if ( isset( $data['clear_{{var_name}}'] ) && 'yes' === $data['clear_{{var_name}}'] ) {
	$data['{{var_name}}'] = [];
}

array_push( $data['{{var_name}}'], strval( time() ) );
PHP
				);
			}
		}

		if ( empty( $sections ) ) {
			return '';
		}

		return self::indentSnippet( implode( "\n\n", $sections ), "\t\t\t", true );
	}

	/**
	 * Build the import meta snippet for the render template.
	 *
	 * @param string $metaClass The meta class name.
	 *
	 * @return string The formatted snippet.
	 */
	private static function buildImportMetaSnippet( string $metaClass ): string
	{
		return sprintf(
			"global \$post;\n\$Test_Meta = new %s( \$post );\n",
			$metaClass
		);
	}

	/**
	 * Build the render body HTML based on meta variables.
	 *
	 * @param array<string, string> $variables The variable names and types.
	 * @param string $className The meta box class name.
	 *
	 * @return string The HTML snippet.
	 */
	private static function buildRenderBodyHtml( array $variables, string $className ): string
	{
		$templates = [
			'array' => <<<'HTML'
<div class="{{var_name_slug}} array-meta">
    <h3>Displaying values in a list from `{{var_name}}`:</h3>
    <ul>

        <?php foreach ( $Test_Meta->{{var_name}} as $value ) : ?>

            <li><?php echo $value; ?></li>

        <?php endforeach; ?>
    
    </ul>

    <div>
        Clear list on page save?
        <input
            name="<?php echo {{class_name}}::create_input_name( 'clear_{{var_name}}' ); ?>"
            type="checkbox"
            value="yes"
        />
    </div>
</div>
HTML,
			'string' => <<<'HTML'
<div class="{{var_name_slug}} string-meta">
    <h3>Current string value for `{{var_name}}`:</h3>

    <div>
        <sup>(This value should only contain letters, numbers, and spaces)</sup>
    </div>

    <div>
        <input
            name="<?php echo {{class_name}}::create_input_name( '{{var_name}}' ); ?>"
            type="text"
            value="<?php echo $Test_Meta->{{var_name}}; ?>"
        />
    </div>
</div>
HTML,
			'boolean' => <<<'HTML'
<div class="{{var_name_slug}} boolean-meta">
    <h3>A boolean toggle for `{{var_name}}`:</h3>

    <div>
        <input
            name="<?php echo {{class_name}}::create_input_name( '{{var_name}}' ); ?>"
            type="checkbox"
            value="yes"
            <?php echo checked( $Test_Meta->{{var_name}}, true ); // https://developer.wordpress.org/reference/functions/checked/ ?>
        />
    </div>
</div>
HTML,
			'integer' => <<<'HTML'
<div class="{{var_name_slug}} integer-meta">
    <h3>An integer field for `{{var_name}}`:</h3>

    <div>
        <input
            inputmode="numeric"
            min="0"
            name="<?php echo {{class_name}}::create_input_name( '{{var_name}}' ); ?>"
            step="1"
            type="number"
        />
    </div>
</div>
HTML,
			'float' => <<<'HTML'
<div class="{{var_name_slug}} float-meta">
    <h3>A float field for `{{var_name}}`:</h3>

    <div>
        <input
            inputmode="decimal"
            min="0.1"
            name="<?php echo {{class_name}}::create_input_name( '{{var_name}}' ); ?>"
            step="0.01"
            type="number"
        />
    </div>
</div>
HTML,
		];

		$sections = [];

		foreach ( $variables as $name => $type ) {
			if ( ! array_key_exists( $type, $templates ) ) {
				continue;
			}

			$slug = Utility::slugify( $name );
			$section = str_replace(
				[ '{{var_name}}', '{{var_name_slug}}', '{{class_name}}' ],
				[ $name, $slug, $className ],
				$templates[ $type ]
			);

			$sections[] = $section;
		}

		if ( empty( $sections ) ) {
			return self::renderFallbackHtml( $className );
		}

		$combined = implode( "\n\n<hr />\n\n", $sections );
		return self::indentSnippet( $combined, '    ', true );
	}

	/**
	 * Render fallback HTML when no meta variables are selected.
	 *
	 * @param string $className The meta box class name.
	 *
	 * @return string The fallback HTML snippet.
	 */
	private static function renderFallbackHtml( string $className ): string
	{
		return self::indentSnippet( sprintf( '<p>This is the output of %s.</p>', $className ), '    ', true );
	}

	/**
	 * Register the meta box in a screen or post type file.
	 *
	 * @param HelperBundle $bundle The IO interfaces for the terminal.
	 * @param string $postTypePath The post type file path.
	 * @param string $className The meta box class name.
	 *
	 * @return int The status of the registration steps.
	 */
	private static function registerMetaBoxPlacement(
		HelperBundle $bundle,
		string $postTypePath,
		string $className
	): int {
		$screens = self::getScreenFiles();

		if ( ! empty( $screens ) ) {
			$choice = new ChoiceQuestion(
				StyleUtil::color(
					'Would you like to add the meta box to a screen or the post type?',
					ConsoleColor::BrightYellow
				),
				[ 'screen', 'post type' ]
			);

			$selection = $bundle->helper->ask( $bundle->input, $bundle->output, $choice );

			if ( 'screen' === $selection ) {
				$screenFile = self::selectScreenFile( $bundle, $screens );
				$screenLocation = self::selectScreenLocation( $bundle );
				return self::insertMetaBoxIntoScreen( $screenFile, $className, $screenLocation );
			}
		} else {
			$yn = StyleUtil::color( '(yes/no) ', ConsoleColor::Yellow );
			$question = new Question(
				'No screens were found. Add the meta box to the post type instead? ' . $yn
			);
			$question->setValidator( CliUtil::yesNoValidator() );
			$answer = $bundle->helper->ask( $bundle->input, $bundle->output, $question );
			if ( ! in_array( $answer, [ 'yes', 'y' ], true ) ) {
				return Command::SUCCESS;
			}
		}

		return self::insertMetaBoxIntoPostType( $postTypePath, $className );
	}

	/**
	 * Get available screen files.
	 *
	 * @return array The list of screen file paths.
	 */
	private static function getScreenFiles(): array
	{
		$directory = sprintf( '%s/%s', getcwd(), self::SCREEN_DIR );
		if ( ! is_dir( $directory ) ) {
			return [];
		}

		$files = Utility::scandir( $directory, 'files' );
		$paths = [];

		foreach ( $files as $file ) {
			$paths[] = sprintf( '%s/%s', $directory, $file );
		}

		return $paths;
	}

	/**
	 * Prompt the user to select a screen file.
	 *
	 * @param HelperBundle $bundle The IO interfaces for the terminal.
	 * @param array $screens The screen file paths.
	 *
	 * @return string The selected screen file path.
	 */
	private static function selectScreenFile( HelperBundle $bundle, array $screens ): string
	{
		$choices = array_map( 'basename', $screens );
		$question = new ChoiceQuestion(
			StyleUtil::color( 'Select which screen to use:', ConsoleColor::BrightYellow ),
			$choices
		);

		$selection = $bundle->helper->ask( $bundle->input, $bundle->output, $question );
		$index = array_search( $selection, $choices, true );

		return $screens[ $index ];
	}

	/**
	 * Prompt the user to select which screen location to insert the meta box.
	 *
	 * @param HelperBundle $bundle The IO interfaces for the terminal.
	 *
	 * @return string The screen location choice.
	 */
	private static function selectScreenLocation( HelperBundle $bundle ): string
	{
		$question = new ChoiceQuestion(
			StyleUtil::color(
				'Which screen should the meta box appear on?',
				ConsoleColor::BrightYellow
			),
			[ 'post create screen', 'post edit screen', 'both post screens' ]
		);

		return $bundle->helper->ask( $bundle->input, $bundle->output, $question );
	}

	/**
	 * Insert a meta box call into a screen file.
	 *
	 * @param string $screenFile The screen file path.
	 * @param string $className The meta box class name.
	 * @param string $location The selected screen location.
	 *
	 * @return int The status of the insertion.
	 */
	private static function insertMetaBoxIntoScreen(
		string $screenFile,
		string $className,
		string $location
	): int {
		$contents = file_get_contents( $screenFile );
		if ( false === $contents ) {
			return Command::FAILURE;
		}

		$insert = "\n\t\t\t{$className}::instance()->add_meta_box();\n";
		$targets = [];

		if ( 'post create screen' === $location ) {
			$targets[] = 'add_post';
		} elseif ( 'post edit screen' === $location ) {
			$targets[] = 'view_post';
		} else {
			$targets = [ 'add_post', 'view_post' ];
		}

		try {
			foreach ( $targets as $functionName ) {
				$contents = CliUtil::insertIntoFunction( $insert, $functionName, $contents, 'before_closing' );
			}
		} catch ( \RuntimeException $e ) {
			return Command::FAILURE;
		}

		if ( ! file_put_contents( $screenFile, $contents ) ) {
			return Command::FAILURE;
		}

		return Command::SUCCESS;
	}

	/**
	 * Insert a meta box call into a post type file.
	 *
	 * @param string $postTypePath The post type file path.
	 * @param string $className The meta box class name.
	 *
	 * @return int The status of the insertion.
	 */
	private static function insertMetaBoxIntoPostType( string $postTypePath, string $className ): int
	{
		$contents = file_get_contents( $postTypePath );
		if ( false === $contents ) {
			return Command::FAILURE;
		}

		$insert = "\n\t\t\t\$this->add_meta_box( {$className}::instance() );\n";

		try {
			$contents = CliUtil::insertIntoFunction( $insert, '__construct', $contents, 'before_closing' );
		} catch ( \RuntimeException $e ) {
			return Command::FAILURE;
		}

		if ( ! file_put_contents( $postTypePath, $contents ) ) {
			return Command::FAILURE;
		}

		return Command::SUCCESS;
	}

	/**
	 * Build the meta box file path for the current plugin.
	 *
	 * @param string $slug The meta box class slug.
	 *
	 * @return string The meta box file path.
	 */
	private static function metaBoxFilePath( string $slug ): string
	{
		return sprintf( '%s/%s/class-%s.php', getcwd(), self::META_BOX_DIR, $slug );
	}

	/**
	 * Build the meta box render template file path.
	 *
	 * @param string $slug The template slug.
	 *
	 * @return string The render template file path.
	 */
	private static function metaBoxTemplatePath( string $slug ): string
	{
		return sprintf( '%s/%s/%s-template.php', getcwd(), self::META_BOX_TEMPLATE_DIR, $slug );
	}

	/**
	 * Create a file from a completed template.
	 *
	 * @param string $template The file contents.
	 * @param string $filePath The output file path.
	 *
	 * @return bool The status of the file write operation.
	 */
	private static function createFile( string $template, string $filePath ): bool
	{
		$outputDir = dirname( $filePath );

		if ( ! is_dir( $outputDir ) ) {
			if ( ! mkdir( $outputDir, 0755, true ) && ! is_dir( $outputDir ) ) {
				return false;
			}
		}

		return file_put_contents( $filePath, $template );
	}

	/**
	 * Indent a multi-line snippet by a prefix.
	 *
	 * @param string $snippet The snippet to indent.
	 * @param string $prefix The prefix to add to each line.
	 *
	 * @return string The indented snippet.
	 */
	private static function indentSnippet( string $snippet, string $prefix, bool $skipFirst = false ): string
	{
		$lines = preg_split( '/\\r?\\n/', rtrim( $snippet ) );

		foreach ( $lines as $index => $line ) {
			if ( '' === $line ) {
				continue;
			}

			if ( $skipFirst && 0 === $index ) {
				continue;
			}

			$lines[ $index ] = $prefix . $line;
		}

		return implode( "\n", $lines );
	}

	/**
	 * Determine the plugin class name from the current slug.
	 *
	 * @param string $slug The current plugin slug.
	 *
	 * @return string The plugin class name.
	 */
	private static function getPluginClassName( string $slug ): string
	{
		$pluginFile = sprintf( '%s/%s.php', getcwd(), $slug );

		if ( is_file( $pluginFile ) ) {
			$className = Utility::get_file_class_name( $pluginFile );
			if ( '' !== $className ) {
				return $className;
			}
		}

		return CliUtil::underscorify( $slug, true );
	}
}
