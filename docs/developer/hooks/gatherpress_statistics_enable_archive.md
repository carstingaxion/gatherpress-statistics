# gatherpress_statistics_enable_archive


Filters whether the Statistics Archive Dashboard is enabled.

Disabling this skips registering the archive database table, the
Dashboard → Statistics Archive admin page, and the monthly archive
cron job.

## Example

```php
add_filter( 'gatherpress_statistics_enable_archive', '__return_false' );
```

## Parameters

- *`bool`* `$enabled` Whether archive is enabled. Default true.

## Files

- [includes/classes/class-plugin.php:84](https://github.com/carstingaxion/gatherpress-statistics/blob/main/includes/classes/class-plugin.php#L84)
```php
apply_filters( 'gatherpress_statistics_enable_archive', true )
```



[← All Hooks](Hooks.md)
