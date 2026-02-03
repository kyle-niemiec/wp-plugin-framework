# Create Plugin

Command: `make:plugin`

## Purpose

Creates the main plugin file in the current directory using the framework template.

## Output

- `./<plugin-slug>.php`

## Prompts

- Plugin name
- Plugin URI (optional)
- Description (optional)
- Author
- Author URI (optional)

## Notes

- The plugin class name is derived from the folder name.
- The generated file requires `vendor/kyle-niemiec/wp-plugin-framework/index.php` and extends `WPPF\v1_2_2\WordPress\Plugin`.
