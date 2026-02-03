# Staging Detection

The framework can detect when a site is running on a different host than the expected production host. This is useful for warning users when they are on staging or a cloned environment.

## How it works

- On first run, `Staging_Detection` stores the current host in the `wppf_staging_detection_expected_domain` option.
- On subsequent runs, if the current host differs, `Staging_Detection::is_staging()` returns `true`.

## Add a notice

```php
use WPPF\v1_2_2\Plugin\Staging_Detection\Staging_Detection_Notice;

Staging_Detection_Notice::add_notice(__FILE__, 'This plugin is running in staging.');
```

When staging is detected, each notice is displayed in the admin with the plugin name prepended.

## Resetting the expected host

Delete the `wppf_staging_detection_expected_domain` option to reset the expected host, or update it to match the current environment.
