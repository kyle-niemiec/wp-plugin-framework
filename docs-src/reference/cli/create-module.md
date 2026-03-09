# Create Module

Command: `make:module`

## Purpose

Creates a custom module class from a template.

## Output

- `includes/modules/class-<slug>.php`

## Requirements

- An existing plugin file in the current directory.

## Prompts

- Module name

## Notes

- The module class name is converted to Upper_Underscore_Case from the module name prompt.
- The generated class extends `WPPF\v1_2_2\Framework\Module`.
- If the plugin file is missing, the CLI offers to create it first.
- `WPPF\v1_2_2\Framework\Admin_Module` discovers module files in `admin/includes/modules`.

## Admin Option

Pass `--admin` to scaffold the module under the admin module tree:

```bash
vendor/kyle-niemiec/wp-plugin-framework/bin/wppf make:module --admin
```

- Output path changes to `admin/includes/modules/class-<slug>.php`.
