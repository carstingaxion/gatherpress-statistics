# gatherpress_statistics_support_config


Filters the default gatherpress_statistics support configuration.

Runs once, when support for `gatherpress_statistics` is registered on
the `gatherpress_event` post type. Use this to change which statistic
types are enabled by default without re-registering post type support
yourself.

## Example

```php
add_filter( 'gatherpress_statistics_support_config', function ( array $config ): array {
    // Disable the more expensive cross-taxonomy statistics.
    $config['events_multi_taxonomy']      = false;
    $config['taxonomy_terms_by_taxonomy'] = false;
    return $config;
} );
```

## Parameters

- *`array<string,`* `bool>` $default_config Default statistic types and whether each is enabled.

## Files

- [includes/classes/class-setup.php:84](https://github.com/carstingaxion/gatherpress-statistics/blob/main/includes/classes/class-setup.php#L84)
```php
apply_filters( 'gatherpress_statistics_support_config', $default_config )
```



[← All Hooks](Hooks.md)
