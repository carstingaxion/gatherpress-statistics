# gatherpress_statistics_excluded_taxonomies


Filters taxonomies excluded from statistics generation and block editor selection.


(true) or statistics generation (false).

## Example

```php
add_filter( 'gatherpress_statistics_excluded_taxonomies', function ( array $excluded, bool $for_editor ): array {
    $excluded[] = 'post_tag';
    $excluded[] = 'custom_event_type';
    return $excluded;
}, 10, 2 );
```

## Parameters

- *`array<int,`* `string>` $excluded_taxonomies Taxonomy slugs to exclude. Default: `array()`.
- *`bool`* `$for_editor` Whether this call is for editor selection

## Files

- [includes/classes/class-taxonomy.php:97](https://github.com/carstingaxion/gatherpress-statistics/blob/main/includes/classes/class-taxonomy.php#L97)
```php
apply_filters(
			'gatherpress_statistics_excluded_taxonomies',
			array(),
			$for_editor
		)
```



[← All Hooks](Hooks.md)
