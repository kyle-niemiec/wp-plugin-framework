# Create Meta Box

Command: `make:meta-box`

## Purpose

Generates a meta box class and render template for a selected post type.

## Output

- `admin/includes/meta-boxes/class-<slug>.php`
- `admin/templates/<slug>-template.php`

## Requirements

- An admin module (`admin/<plugin-slug>-admin.php`).
- At least one post type in `includes/post-types`.

## Prompts

- Post type selection
- Meta box title, ID, and key
- Optional `Post_Meta` class to bind for schema and saving

## Notes

- If the admin module is missing, the CLI offers to create it first.
- If a meta class is selected, the generated template includes a form layout for its fields.
