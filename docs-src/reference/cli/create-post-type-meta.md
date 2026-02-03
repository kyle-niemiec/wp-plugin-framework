# Create Post Type Meta

Command: `make:post-meta`

## Purpose

Generates a `Post_Meta` class with schema definitions and default values.

## Output

- `includes/classes/class-<slug>.php`

## Requirements

- At least one post type in `includes/post-types`.

## Prompts

- Post type selection
- Variable names in `lower_snake_case`
- Variable types: `array`, `boolean`, `float`, `integer`, `string`

## Notes

- Default values and schemas can be edited in the generated class.
- The meta key is derived from the class name.
