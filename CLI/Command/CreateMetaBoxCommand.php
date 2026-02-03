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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\ConsoleSectionOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use WPPF\CLI\Enum\ConsoleColor;
use WPPF\CLI\Support\PluginCliCommand;
use WPPF\CLI\Support\SectionQuestionHelper;
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

		$bundle = new HelperBundle( new SectionQuestionHelper, $input, $output );
		$slug = basename( getcwd() );

		// Select post type for meta box
		$selectedPostType = $this->promptForPostTypeFile( $bundle );
		$postTypePath = sprintf( '%s/%s/%s', getcwd(), CreatePostTypeCommand::POST_TYPES_DIR, $selectedPostType );
		$postTypeClass = Utility::get_file_class_name( $postTypePath );

		if ( '' === $postTypeClass ) {
			$output->writeln( StyleUtil::error( 'Could not determine the post type class name.' ) );
			return Command::FAILURE;
		}

		// Collect general meta box details
		$title = self::askMetaBoxTitle( $bundle );
		$snakeTitle = CliUtil::underscorify( $title );
		$metaBoxId = self::askMetaBoxId( $bundle, $snakeTitle );
		$metaBoxKey = self::askMetaBoxKey( $bundle, $snakeTitle . '_meta' );

		// Ensure the meta box doesn't already exist
		$className = sprintf( '%s_Meta_Box', CliUtil::underscorify( $metaBoxId, true ) );
		$templateSlug = Utility::slugify( $className );
		$metaBoxFile = self::metaBoxFilePath( $templateSlug );
		$renderTemplateFile = self::metaBoxTemplatePath( $templateSlug );

		if ( file_exists( $metaBoxFile ) || file_exists( $renderTemplateFile ) ) {
			$output->writeln( StyleUtil::error( 'Error: The meta box file or template file already exist.' ) );
			return Command::FAILURE;
		}

		// Select post meta class to connect (optional)
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
				'{{maybe_init_meta}}' => $metaClass ? "\n\t\t\$Meta = new {$metaClass}( \$Post );\n" : '',
				'{{maybe_save_meta}}' => $metaClass ? self::buildSaveMetaSnippet( $metaClass ) : '',
				'{{maybe_pass_meta}}' => $metaClass ? ' $Meta ' : '',
				'{{maybe_import_data}}' => $metaClass ? self::buildImportDataSnippet( $metaVariables ) : '',
				'{{maybe_pass_args}}' => $metaClass ? sprintf( ' %s $Meta ', $metaClass ) : '',
				'{{maybe_pass_args_doc}}' => $metaClass ? sprintf( "\n\t\t * @param %s \$Meta The meta data associated with this meta box.\n\t\t * ", $metaClass ) : '',
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

		// Create the template and meta box class files
		if ( ! self::createFile( $metaBoxTemplate, $metaBoxFile ) ) {
			$output->writeln( StyleUtil::error( 'There was an error writing out the meta box file to disk.' ) );
			return Command::FAILURE;
		}

		if ( ! self::createFile( $renderTemplate, $renderTemplateFile ) ) {
			$output->writeln( StyleUtil::error( 'There was an error writing out the render template to disk.' ) );
			return Command::FAILURE;
		}

		// Write out the meta box registration to the screen/post-type
		$placementStatus = $this->registerMetaBoxPlacement( $bundle, $postTypePath, $className, $postTypeClass );

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
			$value = trim( strval( $value ) );

			if ( '' === $value ) {
				throw new \RuntimeException( 'The title cannot be empty.' );
			}

			return $value;
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
	 * Let the user select a Post_Meta class to connect to the meta box, if available.
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

		$skip_text = 'None (skip)';
		$choices = array_merge( [ $skip_text ], array_keys( $metaClasses ) );

		$question = new ChoiceQuestion(
			StyleUtil::color( 'Select a post meta class to attach (optional):', ConsoleColor::BrightYellow ),
			$choices
		);

		// Ask which meta class to attach to the meta box
		$selection = $bundle->helper->ask( $bundle->input, $bundle->output, $question );

		if ( $skip_text === $selection ) {
			return [];
		}

		$metaPath = $metaClasses[ $selection ];
		$variables = self::extractMetaVariables( $metaPath );

		return [
			'class' => $selection,
			'file' => $metaPath,
			'variables' => $variables,
		];
	}

	/**
	 * Find Post_Meta classes in includes/classes.
	 *
	 * @return array The list of meta classes by display name.
	 */
	private static function findPostMetaClasses(): array
	{
		$metaDir = sprintf( '%s/includes/classes', getcwd() );

		if ( ! is_dir( $metaDir ) ) {
			return [];
		}

		$files = Utility::scandir( $metaDir, 'files' );
		$metaClasses = [];

		// Loop through files and check which ones extend Post_Meta classes
		foreach ( $files as $file ) {
			$path = sprintf( '%s/%s', $metaDir, $file );
			$contents = file_get_contents( $path );
			$regexp = '/final class ([A-Za-z_][A-Za-z0-9_]*) extends Post_Meta/';

			if ( ! preg_match( $regexp, $contents, $matches ) ) {
				continue;
			}

			$metaClasses[ $matches[1] ] = $path;
		}

		return $metaClasses;
	}

	/**
	 * Extract variable names and types from a Post_Meta class property declarations.
	 *
	 * @param string $file The meta class file path.
	 *
	 * @return array<string, string> The variable names and inferred types.
	 */
	private static function extractMetaVariables( string $file ): array
	{
		$contents = file_get_contents( $file );
		$variables = [];

		if ( false === $contents ) {
			return [];
		}

		// Find all the generated variables first.
		if ( ! preg_match( '/\\$generated_values = \\[\\n(.+)\\n\\];/', $contents, $matches ) ) {
			return [];
		}

		$lines = preg_split( '/\\n/', $matches[1] );
		$variable_names = [];

		// Pick out the variable names from the generated values
		foreach ( $lines as $line ) {
			$trimmed = trim( $line );

			if ( '' === $trimmed ) {
				continue;
			}

			if ( preg_match( '/\'([a-z_]+)\' =>/', $trimmed, $var_search ) ) {
				$variable_names[] = $var_search[1];
			}
		}

		// Find the variable types from the class properties by using the names
		foreach ( $variable_names as $name ) {
			$pattern = sprintf(
				'/public (array|bool|float|int|string) \\$%s;/',
				$name
			);

			if ( preg_match( $pattern, $contents, $matches ) ) {
				$type = $matches[1];

				switch ( $type ) {
					case 'bool':
						$type = 'boolean';
						break;
					case 'int':
						$type = 'integer';
						break;
				}

				$variables[ $name ] = $type;
			} else {
				$variables[ $name ] = 'string';
			}
		}

		return $variables;
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
		return <<<END
			// Load meta data for updating and validate changes
			\$Meta->import( \$data );
			\$validation = \$Meta->validate();

			// Save meta data if there are no errors
			if ( is_wp_error( \$validation ) ) {
				Admin_Notices::error( Meta_Schema::create_error_message( \$validation ) );
			} else {
				\$Meta->save();
			}

		END;
	}

	/**
	 * Build the code snippets for handling data submitted through the WP admin.
	 *
	 * @param array $variables The variable names as keys and types as values.
	 *
	 * @return string The formatted code snippet.
	 */
	private static function buildImportDataSnippet( array $variables ): string
	{
		$sections = [''];

		foreach ( $variables as $name => $type ) {
			// Handle boolean template
			if ( 'boolean' === $type ) {
				$sections[] = str_replace(
					'{{var_name}}',
					$name,
<<<'END'
		// Boolean values require text->boolean translation
		if ( isset( $data['{{var_name}}'] ) && 'yes' === $data['{{var_name}}'] ) {
			$data['{{var_name}}'] = true;
		} else {
			$data['{{var_name}}'] = false;
		}
END
				);

			}

			// Handle array template
			elseif ( 'array' === $type ) {
				$sections[] = str_replace(
					'{{var_name}}',
					$name,
<<<'END'
		// Array values must be manually managed
		$data['{{var_name}}'] = $Meta->{{var_name}};

		if ( isset( $data['clear_{{var_name}}'] ) && 'yes' === $data['clear_{{var_name}}'] ) {
			$data['{{var_name}}'] = [];
		}

		array_push( $data['{{var_name}}'], strval( time() ) );
END
				);
			}

		} // foreach

		return implode( "\n\n", $sections );
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
			"global \$post;\n\$Meta = new %s( \$post );\n",
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
		// Define the array of section HTML templates
		$templates = [

			'array' => // ----- Array HTML template -----
<<<'HTML'
	<div class="{{var_name_slug}} array-meta">
		<h3>Displaying values in a list from `{{var_name}}`:</h3>
		<ul>

			<?php foreach ( $Meta->{{var_name}} as $value ) : $Time = new \DateTime( sprintf( '@%s', $value ) ); ?>

				<li><?php echo $Time->format( 'd/m/Y H:i:s' ); ?></li>

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

			'string' => // ----- String HTML template -----
<<<'HTML'
<div class="{{var_name_slug}} string-meta">
	<h3>Current string value for `{{var_name}}`:</h3>

	<div>
		<sup>(This value should only contain letters, numbers, and spaces)</sup>
	</div>

	<div>
		<input
			name="<?php echo {{class_name}}::create_input_name( '{{var_name}}' ); ?>"
			type="text"
			value="<?php echo $Meta->{{var_name}}; ?>"
		/>
	</div>
</div>
HTML,

			'boolean' => // ----- Boolean HTML template -----
<<<'HTML'
<div class="{{var_name_slug}} boolean-meta">
	<h3>A boolean toggle for `{{var_name}}`:</h3>

	<div>
		<input
			name="<?php echo {{class_name}}::create_input_name( '{{var_name}}' ); ?>"
			type="checkbox"
			value="yes"
			<?php echo checked( $Meta->{{var_name}}, true ); // https://developer.wordpress.org/reference/functions/checked/ ?>
		/>
	</div>
</div>
HTML,

			'integer' => // ----- Integer HTML template -----
<<<'HTML'
	<div class="{{var_name_slug}} integer-meta">
		<h3>An integer field for `{{var_name}}`:</h3>

		<div>
			<input
				inputmode="numeric"
				min="0"
				name="<?php echo {{class_name}}::create_input_name( '{{var_name}}' ); ?>"
				step="1"
				type="number"
				value="<?php echo $Meta->{{var_name}}; ?>"
			/>
		</div>
	</div>
HTML,

			'float' => // ----- Float HTML template -----
<<<'HTML'
	<div class="{{var_name_slug}} float-meta">
		<h3>A float field for `{{var_name}}`:</h3>

		<div>
			<input
				inputmode="decimal"
				min="0.1"
				name="<?php echo {{class_name}}::create_input_name( '{{var_name}}' ); ?>"
				step="0.01"
				type="number"
				value="<?php echo $Meta->{{var_name}}; ?>"
			/>
		</div>
	</div>
HTML,

		]; // $templates

		// Construct the sections from the passed variables
		$sections = [];

		foreach ( $variables as $name => $type ) {
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

		return implode( "\n\n<hr />\n\n", $sections );
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
		return sprintf( "\t<p>This is the output of %s.</p>", $className );
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
	private function registerMetaBoxPlacement(
		HelperBundle $bundle,
		string $postTypePath,
		string $className,
		string $postTypeClass
	): int {
		$screens = self::getScreenFiles();

		// Give a choice between updating a screen and a post type, if screens are available
		if ( ! empty( $screens ) ) {
			$choice = new ChoiceQuestion(
				StyleUtil::color(
					sprintf(
						'Would you like to add the meta box to an existing %s class or the %s post type?',
						StyleUtil::color( 'Screen', ConsoleColor::BrightCyan ),
						StyleUtil::color( $postTypeClass, ConsoleColor::BrightCyan )
					),
					ConsoleColor::BrightYellow
				),
				[
					'Add to screen class',
					'Add to all ' . $postTypeClass . ' screens',
				]
			);

			if ( $bundle->output instanceof ConsoleOutputInterface ) {
				/** @var ConsoleSectionOutput $output */
				$output = $bundle->output->section();
			} else {
				/** @var ConsoleOutputInterface $output */
				$output = $bundle->output;
			}

			$selection = $bundle->helper->ask( $bundle->input, $output, $choice );

			if ( 'Add to screen class' === $selection ) {
				// Get the screen and location from the user before inserting the import
				if ( $output instanceof ConsoleSectionOutput ) {
					$output->clear();
				}

				$screenFile = self::selectScreenFile( $bundle, $output, $screens );

				if ( $output instanceof ConsoleSectionOutput ) {
					$output->clear();
				}

				$screenLocation = self::selectScreenLocation( $bundle, $output );

				return self::insertMetaBoxIntoScreen( $screenFile, $className, $screenLocation );
			} else {
				// Explicitly insert into the post type
				return self::insertMetaBoxIntoPostType( $postTypePath, $className );
			}

		}

		// Otherwise offer to update the post type
		else {
			$insertIntoPostType = $this->askYesNo(
				$bundle->input,
				$bundle->output,
				sprintf( 'Would you like to add the meta box to the %s post type?', $postTypeClass )
			);

			if ( ! $insertIntoPostType ) {
				return Command::SUCCESS;
			}

			// Insert into the post type by default
			return self::insertMetaBoxIntoPostType( $postTypePath, $className );
		}

	}

	/**
	 * Get available screen files in the admin includes directory.
	 *
	 * @return array The list of screen file paths.
	 */
	private static function getScreenFiles(): array
	{
		$directory = sprintf( '%s/%s', getcwd(), 'admin/includes/screens' );

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
	 * Prompt the user to select a screen class.
	 *
	 * @param HelperBundle $bundle The IO interfaces for the terminal.
	 * @param array $screens The screen file paths.
	 *
	 * @return string The selected screen file path.
	 */
	private static function selectScreenFile(
		HelperBundle $bundle,
		ConsoleSectionOutput $output,
		array $screens
	): string {
		$choices = [];
		$classToFile = [];

		// Find class names for given screen files
		foreach ( $screens as $screenFile ) {
			$className = Utility::get_file_class_name( $screenFile );
			$label = '' !== $className ? $className : basename( $screenFile );

			// Labels are unique and prefer class names for display
			if ( isset( $classToFile[ $label ] ) ) {
				$label = sprintf( '%s (%s)', $label, basename( $screenFile ) );
			}

			$choices[] = $label;
			$classToFile[ $label ] = $screenFile;
		}

		$question = new ChoiceQuestion(
			StyleUtil::color( 'Select which screen to use:', ConsoleColor::BrightYellow ),
			$choices
		);

		// Ask the user which screen class to use
		$selectedClass = $bundle->helper->ask( $bundle->input, $output, $question );

		if ( ! isset( $classToFile[ $selectedClass ] ) ) {
			throw new \RuntimeException( sprintf( 'Screen `%s` is invalid.', $selectedClass ) );
		}

		return $classToFile[ $selectedClass ];
	}

	/**
	 * Prompt the user to select which screen location to insert the meta box.
	 *
	 * @param HelperBundle $bundle The IO interfaces for the terminal.
	 *
	 * @return string The screen location choice.
	 */
	private static function selectScreenLocation( HelperBundle $bundle, ConsoleSectionOutput $output ): string
	{
		$question = new ChoiceQuestion(
			StyleUtil::color(
				'Which screen should the meta box appear on?',
				ConsoleColor::BrightYellow
			),
			[ 'Post create screen', 'Post edit screen', 'Both post screens' ]
		);

		return $bundle->helper->ask( $bundle->input, $output, $question );
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

		$insert = "\t{$className}::instance()->add_meta_box();\n\t\t";
		$target = '';

		if ( 'Post create screen' === $location ) {
			$target = 'add_post';
		} elseif ( 'Post edit screen' === $location ) {
			$target = 'view_post';
		} else {
			$target = 'current_screen';
		}

		$contents = CliUtil::insertIntoFunction( $insert, $target, $contents, 'before_closing' );

		if ( ! file_put_contents( $screenFile, $contents ) ) {
			return Command::FAILURE;
		}

		return Command::SUCCESS;
	}

	/**
	 * Insert a meta box registration call into a post type file.
	 *
	 * @param string $postTypePath The post type file path.
	 * @param string $className The meta box class name.
	 *
	 * @return int The status of the insertion.
	 */
	private static function insertMetaBoxIntoPostType( string $postTypePath, string $className ): int
	{
		$contents = file_get_contents( $postTypePath );
		$insert = "\t\$this->add_meta_box( {$className}::instance() );\n\t\t";
		$contents = CliUtil::insertIntoFunction( $insert, '__construct', $contents, 'before_closing' );

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
