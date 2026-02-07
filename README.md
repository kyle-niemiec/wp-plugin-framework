# WordPress Plugin Framework (WPPF)

The WordPress Plugin Framework (WPPF) is the culmination of a few years of my hard work building WordPress plugins and the need for a stricter organization than what examples I had seen. It is built to automate boilder-plate code of registering post types and meta boxes, enqueuing assets, separating administrative functionality, and more. While a set of classes manages many of the native WordPress function calls, developers are more free to define "modules", which describe parts of a plugin and focus on assigning functionality to actions and filters to build out custom functionality. Best practices for [WordPress PHP Coding Standards](https://developer.wordpress.org/coding-standards/wordpress-coding-standards/php/) are encouraged through the use of file names, variable conventions, whitespace, and more.

## Features

- Set of object-oriented classes abstracting WordPress concepts with accessible methods.
- Map-backed PSR-4 autoloader separating projects into functional "modules".
- CLI-generated scaffolding for plugins, post types, meta, screens, and meta boxes.
- Versioned framework namespaces with backward compatibility support.
- Supports the [WP Plugin Update Server](https://github.com/kyle-niemiec/wp-plugin-update-server) and the [WPPF Update Helper](https://github.com/kyle-niemiec/wppf-update-helper).
- Assortment of tools for productivity including:
	- Template loading with variable support.
	- Admin notice messaging system.
	- Upgrade actions for specific plugin versions.
	- CRON-based, lightweight action scheduler.

## Requirements

- WordPress development environment
- Composer for installing the framework

## Installation

1. Create a plugin project folder with a name matching your plugin slug. (e.g. "My Test Plugin" has the slug "my-test-plugin").

```bash
mkdir my-test-plugin ; cd my-test-plugin
```

2. Install the framework via Composer from inside the folder you just created:

```bash
composer require kyle-niemiec/wp-plugin-framework
```

3. Install the framework’s dev dependencies (required for the CLI):

```bash
cd vendor/kyle-niemiec/wp-plugin-framework;
composer install --dev;
cd ../../;
```

4. Run the CLI using the vendor-packaged binary

```bash
vendor/bin/wppf list
```

## Documentation

- The current examples and API docs are currently hosted at [CodeFlower.io](https://wp-plugin-framework.codeflower.io).
- An older article with some examples and concepts exists at [CodeFlower.io](https://codeflower.io/designink-wordpress-framework/).

## CLI Commands

Common scaffolding commands (run from your plugin root):

- `make:plugin` — create the main plugin file
- `make:plugin-admin` — create an admin module class
- `make:post-type` — create a custom post type class
- `make:post-meta` — create a post meta class
- `make:post-screen` — create a post screen class
- `make:meta-box` — create a meta box class + template

## License

GPL-3.0. See `license.txt`.

## Contributing

Issues and PRs are welcome. Please follow the project and WordPress PHP Coding Standards when contributing.
