# Action Scheduler

The framework includes a lightweight scheduling system built on WP‑Cron. It stores timers in a WordPress option and periodically checks whether any actions are due.

## Key classes

- `WPPF\v1_2_2\Plugin\Action_Scheduler\Timer` (abstract)
- `WPPF\v1_2_2\Plugin\Action_Scheduler\Simple_Timer`
- `WPPF\v1_2_2\Plugin\Action_Scheduler\Interval_Timer`
- `WPPF\v1_2_2\Plugin\Action_Scheduler\Action`
- `WPPF\v1_2_2\Plugin\Action_Scheduler\Timer_Manager`

`Timer_Manager` stores timers in the `wppf_action_scheduler_timers` option and runs them on a WP‑Cron schedule (every 5 minutes by default).

## Basic usage

```php
use WPPF\v1_2_2\Plugin\Action_Scheduler\Action;
use WPPF\v1_2_2\Plugin\Action_Scheduler\Simple_Timer;
use WPPF\v1_2_2\Plugin\Action_Scheduler\Timer_Manager;

$timer = new Simple_Timer('my_timer', [
    'interval' => 'hour',
    'multiplier' => 6,
]);

$timer->add_action(new Action('sync', [
    'action' => [My_Sync::class, 'run'],
    'callable_args' => [],
]));

Timer_Manager::update_timer($timer);
```

## Admin tools

The framework ships with an admin screen (`WPPF Actions`) that prints a timer viewer template. If you want to build your own UI, check the templates under `admin/templates` and the classes under `WPPF\v1_2_2\Plugin\Action_Scheduler`.
