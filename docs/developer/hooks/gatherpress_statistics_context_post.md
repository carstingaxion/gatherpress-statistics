# gatherpress_statistics_context_post


Filters the context post used to resolve context-derived taxonomy
terms for the GatherPress Statistics block.

The Statistics block resolves its "context post" (used to derive
context-driven taxonomy terms) from the block's postId context or the
queried object. That's the right post on a Single Event/Venue
template, but wrong for a post type whose singular template renders
in the context of a different "parent" post — for example a
gatherpress_play_sub belonging to a parent gatherpress_play — where
the statistics should really be about the parent.

## Example

Use parent_post as context

```php
add_filter( 'gatherpress_statistics_context_post', function ( $context, $post_id, $post_type ) {
    if ( 'gatherpress_play_sub' === $post_type ) {
        $parent_id = wp_get_post_parent_id( $post_id );
        if ( $parent_id ) {
            $context['post_id']   = $parent_id;
            $context['post_type'] = get_post_type( $parent_id );
        }
    }
    return $context;
}, 10, 3 );
```

## Parameters

- *`array`* `array{post_id:` int, post_type: string} $context   The context post's id and post type. Other variable names: `$string_list`
- *`int`* `$post_id` The original (unfiltered) context post id.
- `$string_post_type_the_original_context_post_s_post_type` Other variable names: `$post_type`

## Files

- [includes/classes/class-query.php:232](https://github.com/carstingaxion/gatherpress-statistics/blob/main/includes/classes/class-query.php#L232)
```php
apply_filters(
			'gatherpress_statistics_context_post',
			array(
				'post_id'   => $post_id,
				'post_type' => $post_type,
			),
			$post_id,
			$post_type
		)
```



[← All Hooks](Hooks.md)
