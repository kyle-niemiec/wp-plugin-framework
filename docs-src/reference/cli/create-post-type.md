# Create Post Type

Command: `make:post-type`

## Purpose

Scaffolds a custom post type class and basic options.

## Output

- `includes/post-types/class-<slug>.php`

## Prompts

- Singular name
- Plural name
- Show in menu (and menu title if enabled)

## Notes

- The post type slug and class name are derived from the singular name.
- `WPPF\v1_2_2\WordPress\Plugin` automatically discovers classes in `includes/post-types`.
