# Upgrade Schema

Upgrade schemas let you run one‑time migration tasks when the plugin version increases.

## How it works

`WPPF\v1_2_2\WordPress\Plugin` uses `Plugin_Upgrader_Trait` to:

1. Read the plugin version from the plugin header.
2. Compare it to the stored version in the database.
3. Run any upgrade schemas for newer versions.
4. Update the stored version.

Upgrade schemas are discovered under:

- `includes/upgrades`

## Example

```php
use WPPF\v1_2_2\Plugin\Upgrader_Schema;

final class Upgrade_1_1_0 extends Upgrader_Schema
{
    public function __construct()
    {
        $this->add_action([self::class, 'migrate'], 10);
    }

    public function get_version()
    {
        return '1.1.0';
    }

    public static function migrate()
    {
        // Your one-time migration logic.
    }
}
```

## Tips

- Name files as `class-<slug>.php` so the module loader can find them.
- Bump the plugin header version to trigger upgrades.
- Use the action priority to control ordering within a version.
