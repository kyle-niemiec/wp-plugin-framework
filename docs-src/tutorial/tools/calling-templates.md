# Calling a template

Templates are PHP files loaded relative to the plugin or admin module file. The framework’s `get_template()` method handles locating and including them.

## Plugin templates

```php
My_Plugin::instance()->get_template('hero', [
    'title' => 'Hello world',
]);
```

This will load:

- `templates/hero-template.php`

The array keys become local variables inside the template.

## Admin templates

```php
My_Plugin::instance()->get_admin_module()->get_template('settings', [
    'Settings' => $settings,
]);
```

This will load:

- `admin/templates/settings-template.php`

## Missing templates

If the template file is missing, `Utility::doing_it_wrong()` is triggered with a helpful message.
