# Intro

This framework exists to make WordPress plugin development repeatable. It emphasizes a predictable filesystem layout, module-based loading, and a lightweight CLI to scaffold the repetitive pieces.

## Core ideas

- **Modules over globals**: The framework centers around `WPPF\v1_2_2\Framework\Module`, which discovers and constructs submodules under `includes/modules`. This keeps features isolated and predictable.
- **One plugin class**: Each plugin extends `WPPF\v1_2_2\WordPress\Plugin`. The base class handles initialization, admin module discovery, and post type registration.
- **Versioned namespaces**: The public API is namespaced under `WPPF\v1_2_2` to keep compatibility boundaries clear.
- **CLI scaffolding**: Commands in `bin/wppf` generate plugin files, post types, meta classes, admin modules, post screens, and meta boxes.

## Typical project layout

A plugin created with the CLI assumes this structure:

- `my-plugin.php` (the plugin entry file)
- `vendor/` (Composer dependencies)
- `includes/` (post types, classes, upgrades, modules)
- `admin/` (admin module file + admin includes)
- `admin/templates/` (admin templates loaded through the admin module)
- `templates/` (front-end templates loaded through the plugin)
- `assets/js` and `assets/css` (scripts and styles enqueued by the plugin)

## How modules are discovered

- `Module` scans `includes/modules` for submodules and loads them automatically.
- `Plugin` scans `includes/post-types` for post type classes.
- `Admin_Module` scans `admin/includes/screens` for admin screens.

This structure means you can add a class in the expected location and it will load without extra registration code.
