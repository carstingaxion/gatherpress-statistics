# gatherpress_stats_calculate_{$statistic_type}


Filters a calculated statistic value before it's cached.

The hook name is dynamic - one filter per statistic type:

- `gatherpress_stats_calculate_total_events`
- `gatherpress_stats_calculate_events_per_taxonomy`
- `gatherpress_stats_calculate_events_multi_taxonomy`
- `gatherpress_stats_calculate_total_taxonomy_terms`
- `gatherpress_stats_calculate_taxonomy_terms_by_taxonomy`
- `gatherpress_stats_calculate_total_attendees`

## Example

```php
// Round counts over 50 to the nearest 10.
add_filter( 'gatherpress_stats_calculate_total_events', function ( int $count, array $filters ): int {
    return $count > 50 ? (int) round( $count / 10 ) * 10 : $count;
}, 10, 2 );
```

## Example

```php
// Apply a 1.5x multiplier to all event counts.
add_filter( 'gatherpress_stats_calculate_total_events', function ( int $count, array $filters ): int {
    return (int) round( $count * 1.5 );
}, 10, 2 );
```

## Parameters

- *`int`* `$result` The calculated statistic value.
- *`array<string,`* `mixed>` $filters The filters applied to this statistic.

## Files

- [includes/classes/class-statistics.php:120](https://github.com/carstingaxion/gatherpress-statistics/blob/main/includes/classes/class-statistics.php#L120)
```php
apply_filters( 'gatherpress_stats_calculate_' . $statistic_type, $result, $filters )
```



[← All Hooks](Hooks.md)
