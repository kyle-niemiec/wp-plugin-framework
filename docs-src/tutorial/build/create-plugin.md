# Create a plugin file (using CLI)

Command: `make:plugin`

This creates the main plugin file in your plugin root and wires it to the framework.

## What it does

- Generates `./<plugin-slug>.php` based on `CLI/Template/PluginTemplate.php.tpl`.
- Extends `WPPF\v1_2_2\WordPress\Plugin`.
- Requires the framework entry file from `vendor/kyle-niemiec/wp-plugin-framework/index.php`.

## Run it

```bash
vendor/kyle-niemiec/wp-plugin-framework/bin/wppf make:plugin
```

## What it prompts for

- Plugin name
- Plugin URI (optional)
- Description (optional)
- Author
- Author URI (optional)

## Notes

- The class name is derived from your folder name.
- The command fails if `./<plugin-slug>.php` already exists.
