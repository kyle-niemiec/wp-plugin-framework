# Create a post type meta (using CLI)

Command: `make:post-meta`

This builds a `Post_Meta` class with a generated schema and default values.

## Run it

```bash
vendor/kyle-niemiec/wp-plugin-framework/bin/wppf make:post-meta
```

## Requirements

- At least one post type in `includes/post-types`.

If none exist, the command will stop and tell you to create one first.

## Output

- `includes/classes/class-<slug>.php`

The generated class extends `WPPF\v1_2_2\WordPress\Post_Meta` and configures a `Meta_Schema`.

## Prompts

- Select a post type
- Add variables in `lower_snake_case`
- Choose a type for each variable: `array`, `boolean`, `float`, `integer`, or `string`

## Notes

- Default values are generated and can be edited inside the class.
- The schema is created from your variable list.
