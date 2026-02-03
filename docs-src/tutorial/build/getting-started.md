# Getting started (installation)

This framework is installed with Composer and expects a plugin folder that matches your plugin slug.

## 1. Create the plugin folder

Create a folder under `wp-content/plugins` that matches your slug:

```bash
mkdir wp-content/plugins/my-plugin
cd wp-content/plugins/my-plugin
```

The CLI uses the current working directory name to derive class names and file paths, so this step matters.

## 2. Require the framework

From the plugin root, install the framework via Composer:

```bash
composer require kyle-niemiec/wp-plugin-framework
```

This installs the framework under `vendor/kyle-niemiec/wp-plugin-framework`.

## 3. Install the framework’s dev dependencies (for the CLI)

The CLI (`bin/wppf`) lives inside the framework and depends on its dev packages. Run this once from inside the framework folder:

```bash
cd vendor/kyle-niemiec/wp-plugin-framework
composer install --dev
```

After that, you can run the CLI from your plugin root via:

```bash
vendor/kyle-niemiec/wp-plugin-framework/bin/wppf
```

If you skip this step, the CLI will fail because the framework’s `vendor/autoload.php` is missing.
