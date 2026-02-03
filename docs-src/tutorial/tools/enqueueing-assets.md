# Enqueueing JS/CSS

The base plugin class provides helpers for enqueueing assets from the `assets` directory.

## JavaScript

```php
add_action('wp_enqueue_scripts', function () {
    My_Plugin::instance()->enqueue_js('frontend', ['jquery'], [
        'MyPlugin' => [
            'ajaxUrl' => admin_url('admin-ajax.php'),
        ],
    ]);
});
```

This loads:

- `assets/js/frontend.js`

The `enqueue_js()` method uses `wp_localize_script()` for each localization array you pass.

## CSS

```php
add_action('wp_enqueue_scripts', function () {
    My_Plugin::instance()->enqueue_css('frontend');
});
```

This loads:

- `assets/css/frontend.css`

## Admin assets

You can enqueue assets through the admin module too:

```php
My_Plugin::instance()->get_admin_module()->enqueue_css('admin');
```

That expects:

- `admin/assets/css/admin.css`

because the admin module’s base path is `admin/`.
