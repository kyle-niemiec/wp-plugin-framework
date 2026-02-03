# CLI Docs

The framework ships with a small Symfony Console app for scaffolding. It lives at:

- `vendor/kyle-niemiec/wp-plugin-framework/bin/wppf`

Make sure you installed the framework’s dev dependencies (see the tutorial) so the CLI can load its autoloader.

## Usage

```bash
vendor/kyle-niemiec/wp-plugin-framework/bin/wppf list
```

Run commands from your plugin root directory so generated paths are correct.

## Available commands

| Command | Description | Notes |
| --- | --- | --- |
| [`make:plugin`](create-plugin.md) | Create a base plugin file with prompts. | Writes `<plugin-slug>.php`. |
| [`make:plugin-admin`](create-plugin-admin.md) | Create an admin module class. | Requires an existing plugin file. |
| [`make:post-type`](create-post-type.md) | Create a custom post type class. | Writes to `includes/post-types`. |
| [`make:post-meta`](create-post-type-meta.md) | Create a post meta class. | Requires post types. |
| [`make:post-screen`](create-post-screen.md) | Create a post screen class. | Requires post types. |
| [`make:meta-box`](create-meta-box.md) | Create a meta box class and template. | Requires admin module + post types. |
| `framework:version:upgrade` | Bump framework version across files. | Used inside the framework repo. |

## Dependency prompts

Some commands will offer to generate missing prerequisites (for example, `make:plugin-admin` will prompt to create the plugin file if it is missing) when the CLI is run interactively.
