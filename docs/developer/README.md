# Developer Documentation

Architecture, caching internals, the post type support system, and hooks for extending GatherPress Statistics.

## Architecture Overview

Layered, top to bottom:

1. **Post Type Support Layer** — `add_post_type_support( $post_type, 'gatherpress_statistics', $config )` controls which statistic types are available per post type.
2. **Data Layer** — WordPress hooks (`transition_post_status`, meta and taxonomy-relationship hooks) monitor relevant data changes.
3. **Scheduling Layer** — changes clear the cache immediately and schedule a single background regeneration 60 seconds later.
4. **Calculation Layer** — `WP_Query`-based counting (`GatherPressStatistics\Query`).
5. **Cache Layer** — results are stored as WordPress transients (`GatherPressStatistics\Cache`).
6. **Presentation Layer** — the block's `render.php` reads only cached values.

## Post Type Support System

By default, support is registered for `gatherpress_event` with this configuration:

```php
add_post_type_support( 'gatherpress_event', 'gatherpress_statistics', array(
    'total_events'               => true,
    'events_per_taxonomy'        => true,
    'events_multi_taxonomy'      => false,
    'total_taxonomy_terms'       => false,
    'taxonomy_terms_by_taxonomy' => false,
    'total_attendees'            => is_plugin_active( 'gatherpress-attendee-count/plugin.php' ),
) );
```

Change the defaults with the `gatherpress_statistics_support_config` filter (see [Hooks](#hooks) below), or register support for another post type directly:

```php
add_action( 'init', function () {
	add_post_type_support( 'my_event_cpt', 'gatherpress_statistics', array(
		'total_events'        => true,
		'events_per_taxonomy' => true,
		'total_attendees'     => false,
	) );
}, 20 );
```

### Available Statistic Types

| Type | Description |
| --- | --- |
| `total_events` | All events, with optional filters |
| `events_per_taxonomy` | Events in one taxonomy term |
| `events_multi_taxonomy` | Events matching a term in every selected taxonomy (AND) |
| `total_taxonomy_terms` | Total terms in a taxonomy |
| `taxonomy_terms_by_taxonomy` | Terms from one taxonomy that have events in another |
| `total_attendees` | Sum of attendees, with optional filters |

`GatherPressStatistics\Support::get_instance()->is_statistic_type_supported( $type, $post_type )` and `->get_supported_statistic_types( $post_type )` read the resolved config back.

## Hooks

Every filter this plugin provides is documented directly above its `apply_filters()` call in source, including runnable `@example` snippets. The [`extract-wp-hooks`](https://github.com/akirk/extract-wp-hooks) GitHub Action generates per-hook reference pages from those docblocks into `docs/developer/hooks/` automatically on every push to `main` — that's the canonical, always-up-to-date hook reference. This section is a quick index:

| Hook | File | Purpose |
| --- | --- | --- |
| `gatherpress_statistics_support_config` | `includes/classes/class-setup.php` | Change the default statistic-type support config registered on `gatherpress_event` |
| `gatherpress_statistics_excluded_taxonomies` | `includes/classes/class-taxonomy.php` | Exclude taxonomies from statistics generation and block editor selection |
| `gatherpress_statistics_cache_expiration` | `includes/classes/class-cache.php` | Change how long statistics stay cached (default 12 hours) |
| `gatherpress_stats_calculate_{$statistic_type}` | `includes/classes/class-statistics.php` | Modify a calculated value before it's cached, per statistic type |
| `gatherpress_statistics_context_post` | `includes/classes/class-query.php` | Redirect context-term resolution to a different post (e.g. a parent post for a sub-post-type template) |
| `gatherpress_statistics_enable_archive` | `includes/classes/class-plugin.php` | Disable the Statistics Archive Dashboard entirely |

### Context-Term Resolution

`GatherPressStatistics\Query::resolve_context_term( int $post_id, string $taxonomy, ?WP_Term $context_term = null ): int` backs the block's "Use current post's term" toggles. It tries, in order:

1. `$context_term` belongs to `$taxonomy` — the current request is that taxonomy's own archive; use it directly.
2. `$taxonomy` is the `gatherpress-shadow-source` shadow taxonomy of `$post_id`'s own post type — the post is a shadow-source post viewed on its own singular (e.g. a `gatherpress_venue` post); resolve its self term by `post_name` via `GatherPress\Core\Shadow_Source`.
3. Otherwise, `wp_get_post_terms( $post_id, $taxonomy )` — the normal case of a consumer post (e.g. an Event) tagged with a term.

Gracefully no-ops back to step 3 when the installed GatherPress core predates the `Shadow_Source` primitive (introduced in GatherPress 0.34.0).

## Caching

### Cached Value Example

Given 15 events (8 upcoming, 7 past) across 3 topics and 3 venues, cache keys look like:

```php
get_transient( 'gatherpress_stats_total_events_upcoming_abc123' ); // 8
get_transient( 'gatherpress_stats_total_events_past_def456' );     // 7
get_transient( 'gatherpress_stats_total_attendees_past_jkl012' );  // 195
get_transient( 'gatherpress_stats_total_taxonomy_terms_mno345' );  // 3 (topics)
get_transient( 'gatherpress_stats_events_per_taxonomy_upcoming_stu901' ); // 4 (a specific term, upcoming)
get_transient( 'gatherpress_stats_taxonomy_terms_by_taxonomy_efg123' );   // 2 (venues with events in another taxonomy)
```

The key suffix is a hash of the statistic type and its resolved `$filters` array (`GatherPressStatistics\Cache::get_cache_key()`), so context-resolved terms get their own cache entries automatically — no extra cache-key handling needed when adding new filter dimensions.

### Invalidation Triggers

| Trigger | Hook |
| --- | --- |
| Event published / unpublished | `transition_post_status` |
| Attendee count meta changed | `updated_post_meta`, `added_post_meta`, `deleted_post_meta` |
| GatherPress taxonomy term created/edited/deleted | `create_term`, `edit_term`, `delete_term` |
| Term relationship changed | `set_object_terms` |

On any trigger: all statistics transients are deleted immediately, and a single cron job is scheduled 60 seconds out to regenerate ~50–60 common statistics (additional triggers inside that window don't schedule duplicate jobs — this avoids repeated regeneration during bulk imports). Each regenerated value is cached again for `get_cache_expiration()` (default 12 hours).

### Manual Cache Control

```php
gatherpress_statistics_clear_cache();       // Delete all statistics transients now.
gatherpress_statistics_pregenerate_cache(); // Force immediate regeneration (use sparingly — do this in a background job, not on a request).
```

### Performance

* Cache hit: ~0.001s
* Cache miss + calculation: ~0.005–0.02s per statistic
* Full regeneration (50–60 stats, background): ~0.3–1.2s
* Database impact: transients only, cleared automatically
