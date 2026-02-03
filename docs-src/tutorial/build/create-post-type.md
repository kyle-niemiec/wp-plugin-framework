# Create a post type (using CLI)

Command: `make:post-type`

This creates a `Post_Type` class and registers it automatically when your plugin loads.

## Run it

```bash
vendor/kyle-niemiec/wp-plugin-framework/bin/wppf make:post-type
```

## Output

- `includes/post-types/class-<slug>.php`

The generated class extends `WPPF\v1_2_2\WordPress\Post_Type` and returns basic options.

## Prompts

- Singular name
- Plural name
- Show in menu (and menu label if yes)

## Notes

- The slug and class name are derived from the singular name.
- `WPPF\v1_2_2\WordPress\Plugin` scans `includes/post-types` and registers these automatically.
