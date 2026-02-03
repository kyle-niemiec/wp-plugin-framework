# Create Post Screen

Command: `make:post-screen`

## Purpose

Creates an admin screen controller for a custom post type.

## Output

- `admin/includes/screens/class-<slug>.php`

## Requirements

- At least one post type in `includes/post-types`.

## Notes

- The generated class extends `WPPF\v1_2_2\WordPress\Admin\Screens\Post_Screens`.
- `Admin_Module` automatically discovers and constructs these classes.
