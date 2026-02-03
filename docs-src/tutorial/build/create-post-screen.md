# Create a post screen (using CLI)

Command: `make:post-screen`

This generates a screen controller for a custom post type admin UI.

## Run it

```bash
vendor/kyle-niemiec/wp-plugin-framework/bin/wppf make:post-screen
```

## Requirements

- At least one post type in `includes/post-types`.

## Output

- `admin/includes/screens/class-<slug>.php`

The generated class extends `WPPF\v1_2_2\WordPress\Admin\Screens\Post_Screens` and provides hooks you can override:

- `current_screen()`
- `view_post()`
- `view_posts()`
- `add_post()`

## Notes

- `Admin_Module` automatically scans `admin/includes/screens` and constructs screen classes.
