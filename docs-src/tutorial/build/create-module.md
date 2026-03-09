# Create a custom module (using CLI)

Command: `make:module`

This scaffolds a custom `Module` class for plugin-specific features.

## Run it

```bash
vendor/kyle-niemiec/wp-plugin-framework/bin/wppf make:module
```

To scaffold an admin-specific module, add `--admin`:

```bash
vendor/kyle-niemiec/wp-plugin-framework/bin/wppf make:module --admin
```

## Requirements

- An existing plugin file (`<plugin-slug>.php`).

If the plugin file is missing, the CLI will offer to create it first.

## Output

- `includes/modules/class-<slug>.php`
- `admin/includes/modules/class-<slug>.php` when using `--admin`

The generated class extends `WPPF\v1_2_2\Framework\Module`.

## Prompt

- Module name

## Notes

- The class name is converted to Upper_Underscore_Case from your module name.
