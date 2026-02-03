# Create a plugin admin module (using CLI)

Command: `make:plugin-admin`

This scaffolds the admin module file for your plugin.

## Run it

```bash
vendor/kyle-niemiec/wp-plugin-framework/bin/wppf make:plugin-admin
```

## Requirements

- An existing plugin file (`<plugin-slug>.php`).

If the plugin file is missing, the CLI will offer to create it first.

## Output

- `admin/<plugin-slug>-admin.php`

The generated class extends `WPPF\v1_2_2\Framework\Admin_Module`.

## Notes

- The base plugin class automatically discovers and loads the admin module when the file exists.
- Admin templates are loaded from `admin/templates` via the admin module.
