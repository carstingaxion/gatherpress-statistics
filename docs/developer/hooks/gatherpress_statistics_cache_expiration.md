# gatherpress_statistics_cache_expiration


Filters the statistics cache expiration time.

## Example

```php
// Cache for 6 hours instead of the 12-hour default.
add_filter( 'gatherpress_statistics_cache_expiration', function ( int $expiration ): int {
    return 6 * HOUR_IN_SECONDS;
} );
```

## Parameters

- *`int`* `$expiration` Cache expiration time in seconds. Default: `12 * HOUR_IN_SECONDS`.

## Files

- [includes/classes/class-cache.php:92](https://github.com/carstingaxion/gatherpress-statistics/blob/main/includes/classes/class-cache.php#L92)
```php
apply_filters(
			'gatherpress_statistics_cache_expiration',
			12 * HOUR_IN_SECONDS
		)
```



[← All Hooks](Hooks.md)
