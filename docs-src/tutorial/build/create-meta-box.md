# Create a meta box (using CLI)

Command: `make:meta-box`

This creates a meta box class plus a render template, and can optionally wire it to a `Post_Meta` class.

## Run it

```bash
vendor/kyle-niemiec/wp-plugin-framework/bin/wppf make:meta-box
```

## Requirements

- An admin module (`admin/<plugin-slug>-admin.php`).
- At least one post type in `includes/post-types`.

If the admin module is missing, the CLI will offer to generate it first.

## Output

- `admin/includes/meta-boxes/class-<slug>.php`
- `admin/templates/<slug>-template.php`

The generated class extends `WPPF\v1_2_2\WordPress\Admin\Meta_Box` and uses the admin module to render templates.

## Prompts

- Post type to attach to
- Meta box title, ID, and key
- Optional `Post_Meta` class to bind for schema and saving

## Notes

- If you choose a `Post_Meta` class, the template includes generated form fields for its variables.
- The render template is loaded via the admin module’s `get_template()`.
