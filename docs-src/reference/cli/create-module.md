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
