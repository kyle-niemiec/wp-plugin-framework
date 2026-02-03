# Admin Notice

Admin notices are queued into a transient and printed on the next admin page load. The framework provides simple helpers for common notice types.

## Quick usage

```php
use WPPF\v1_2_2\WordPress\Admin\Admin_Notices;

Admin_Notices::success('Settings saved.');
Admin_Notices::warning('Be careful with this setting.');
Admin_Notices::error('Something went wrong.');
```

## Custom notices

```php
use WPPF\v1_2_2\WordPress\Admin\Admin_Notice;

$notice = new Admin_Notice('warning', 'Custom warning message', [
    'header' => 'Heads up',
    'status_code' => 500,
    'hint' => 'Try again after saving.',
]);

$notice->queue_notice();
```

## How notices render

The `Admin_Notice_Module` hooks `admin_notices` and prints queued notices via `Admin_Notices::print_notices()`. The template lives under `admin/templates/admin-notice-print-template.php` and is rendered through the admin module.
