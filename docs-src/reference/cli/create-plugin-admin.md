# Create Plugin Admin

Command: `make:plugin-admin`

## Purpose

Creates the admin module file that houses admin-only functionality.

## Output

- `admin/<plugin-slug>-admin.php`

## Requirements

- An existing plugin file in the current directory.

## Notes

- The generated class extends `WPPF\v1_2_2\Framework\Admin_Module`.
- If the plugin file is missing, the CLI offers to create it first.
