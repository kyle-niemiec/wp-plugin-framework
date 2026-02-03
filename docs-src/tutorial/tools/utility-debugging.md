# Utility class and debugging

The framework’s `Utility` class is a grab‑bag of helpers for common tasks and debugging.

## Useful helpers

- `Utility::slugify()` converts text into a URL‑safe slug.
- `Utility::pascal_underscorify()` turns a string into `Pascal_Underscore_Case`.
- `Utility::guided_array_merge()` merges arrays using a guide array’s keys.
- `Utility::get_file_namespace()` and `Utility::get_file_class_name()` help introspect PHP files.

## Debugging

```php
use WPPF\v1_2_2\Framework\Utility;

Utility::print_debug($value); // Prints <pre> output and dies by default.
Utility::debug_backtrace();   // Prints a simplified backtrace and dies by default.
```

## Warnings for misuse

```php
Utility::doing_it_wrong(__METHOD__, 'This should not happen.');
```

`doing_it_wrong()` triggers a `E_USER_WARNING` when `WP_DEBUG` is enabled, which is useful for surfacing incorrect usage without breaking production.
