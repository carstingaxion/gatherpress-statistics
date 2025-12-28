<?php
/**
 * Manages plugin setup for GatherPress Statistics.
 *
 * @package GatherPress_Statistics
 */

namespace GatherPress_Statistics;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

use GatherPress\Core\Traits\Singleton;

/**
 * Class Setup.
 *
 * Manages plugin setup and initialization.
 */
class Setup {
	/**
	 * Enforces a single instance of this class.
	 */
	use Singleton;

	/**
	 * Constructor for the Setup class.
	 *
	 * Initializes and sets up various components of the plugin.
	 */
	protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Set up hooks for various purposes.
	 *
	 * This method adds hooks for different purposes as needed.
	 *
	 * @return void
	 */
	protected function setup_hooks(): void {
		add_action( 'registered_post_type_gatherpress_event', array( $this, 'register_post_type_support' ) );
		add_action( 'init', array( $this, 'block_init' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'gatherpress_statistics_regenerate_cache', array( $this, 'pregenerate_cache' ) );
		add_action( 'transition_post_status', array( $this, 'clear_cache_on_status_change' ), 10, 3 );
		add_action( 'updated_post_meta', array( $this, 'clear_cache_on_meta_update' ), 10, 3 );
		add_action( 'added_post_meta', array( $this, 'clear_cache_on_meta_update' ), 10, 3 );
		add_action( 'deleted_post_meta', array( $this, 'clear_cache_on_meta_delete' ), 10, 3 );
		add_action( 'create_term', array( $this, 'clear_cache_on_term_change' ), 10, 3 );
		add_action( 'edit_term', array( $this, 'clear_cache_on_term_change' ), 10, 3 );
		add_action( 'delete_term', array( $this, 'clear_cache_on_term_change' ), 10, 3 );
		add_action( 'set_object_terms', array( $this, 'clear_cache_on_term_relationship' ), 10, 3 );
	}

	/**
	 * Register post type support for gatherpress_statistics with granular control.
	 *
	 * This function adds the 'gatherpress_statistics' support to the 'gatherpress_event'
	 * post type by default with some statistic types enabled. Developers can modify
	 * this configuration to enable/disable specific or all statistic types.
	 *
	 * Example to disable specific statistic types:
	 *
	 * ```php
	 * add_filter( 'gatherpress_statistics_support_config', function( $config ) {
	 *     $config['events_multi_taxonomy'] = false;
	 *     $config['total_taxonomy_terms'] = false;
	 *     return $config;
	 * } );
	 * ```
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_post_type_support(): void {
		$default_config = array(
			'total_events'                => true,
			'events_per_taxonomy'         => true,
			'events_multi_taxonomy'       => false,
			'total_taxonomy_terms'        => false,
			'taxonomy_terms_by_taxonomy'  => false,
			'total_attendees'             => true,
		);
		
		/**
		 * Filter the statistics support configuration.
		 *
		 * Allows developers to enable or disable specific statistic types.
		 *
		 * @since 0.1.0
		 *
		 * @param array<string, bool> $config Configuration array with statistic type keys and boolean values.
		 */
		$config = apply_filters( 'gatherpress_statistics_support_config', $default_config );
		
		add_post_type_support( 'gatherpress_event', 'gatherpress_statistics', $config );
	}

	/**
	 * Registers the block using the metadata loaded from the `block.json` file.
	 *
	 * This function is hooked to the 'init' action and registers the GatherPress
	 * Statistics block with WordPress. The block metadata is loaded from the
	 * compiled build directory.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function block_init(): void {
		register_block_type( GATHERPRESS_STATISTICS_CORE_PATH . '/build/' );
	}

	/**
	 * Register REST API routes.
	 *
	 * Provide filtered taxonomies and supported statistic types to the block editor via REST API.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_rest_routes(): void {
		\register_rest_route(
			'gatherpress-statistics/v1',
			'/taxonomies',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_taxonomies_endpoint' ),
				'permission_callback' => function (): bool {
					return current_user_can( 'edit_posts' );
				},
			)
		);
		
		\register_rest_route(
			'gatherpress-statistics/v1',
			'/supported-types',
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'get_supported_types_endpoint' ),
				'permission_callback' => function (): bool {
					return current_user_can( 'edit_posts' );
				},
			)
		);
	}

	/**
	 * REST API endpoint callback to get filtered taxonomies.
	 *
	 * Returns taxonomies that are not excluded by the filter, formatted for
	 * use in the block editor.
	 *
	 * @since 0.1.0
	 *
	 * @return \WP_REST_Response List of filtered taxonomies.
	 */
	public function get_taxonomies_endpoint(): \WP_REST_Response {
		$taxonomies = $this->get_filtered_taxonomies( true );
		
		if ( empty( $taxonomies ) ) {
			return new \WP_REST_Response( array(), 200 );
		}
		
		$formatted_taxonomies = array();
		foreach ( $taxonomies as $taxonomy ) {
			if ( isset( $taxonomy->name ) && isset( $taxonomy->labels->name ) ) {
				$formatted_taxonomies[] = array(
					'slug' => $taxonomy->name,
					'name' => $taxonomy->labels->name,
				);
			}
		}
		
		return new \WP_REST_Response( $formatted_taxonomies, 200 );
	}

	/**
	 * REST API endpoint to get supported statistic types.
	 *
	 * @since 0.1.0
	 *
	 * @return \WP_REST_Response List of supported statistic types.
	 */
	public function get_supported_types_endpoint(): \WP_REST_Response {
		$supported_types = $this->get_supported_statistic_types();
		
		return new \WP_REST_Response( $supported_types, 200 );
	}

	/**
	 * Clear cache when event post status changes to or from 'publish'.
	 *
	 * Hooked to 'transition_post_status' action. This is more precise than
	 * 'save_post' as it only triggers when the post status actually changes.
	 * Clears all statistics caches whenever a supported post type event is published
	 * or unpublished. The actual regeneration happens 60 seconds later via cron.
	 *
	 * Examples of status transitions that trigger cache clearing:
	 * - draft → publish (new event published)
	 * - publish → draft (event unpublished)
	 * - publish → trash (event deleted)
	 * - trash → publish (event restored)
	 * - pending → publish (event approved)
	 *
	 * @since 0.1.0
	 *
	 * @param string   $new_status New post status.
	 * @param string   $old_status Old post status.
	 * @param \WP_Post $post       Post object.
	 * @return void
	 */
	public function clear_cache_on_status_change( string $new_status, string $old_status, $post ): void {
		if ( ! is_object( $post ) || ! isset( $post->post_type ) ) {
			return;
		}
		
		if ( ! post_type_supports( $post->post_type, 'gatherpress_statistics' ) ) {
			return;
		}
		
		// Only clear cache if status is changing to or from 'publish'
		// This catches: publish→anything or anything→publish
		if ( 'publish' === $new_status || 'publish' === $old_status ) {
			// Only clear if status actually changed
			if ( $new_status !== $old_status ) {
				$this->clear_cache();
			}
		}
	}

	/**
	 * Clear cache when attendee count post meta is updated.
	 *
	 * Hooked to post meta update actions. Clears all statistics caches when the
	 * 'gatherpress_attendee_count' meta field is added or updated for posts 
	 * from supported post types.
	 * The actual regeneration happens 60 seconds later via cron.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $meta_id  ID of updated metadata entry.
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Meta key that was updated.
	 * @return void
	 */
	public function clear_cache_on_meta_update( int $meta_id, int $post_id, string $meta_key ): void {
		if ( 'gatherpress_attendee_count' === $meta_key && $this->is_supported_post( $post_id ) ) {
			$this->clear_cache();
		}
	}

	/**
	 * Clear cache when attendee count post meta is deleted.
	 *
	 * The deleted_post_meta hook passes parameters differently - the first parameter
	 * is an array of meta IDs when bulk deleting. We need to handle this special case.
	 *
	 * @since 0.1.0
	 *
	 * @param array<int>|int $meta_ids Meta ID or array of meta IDs being deleted.
	 * @param int            $post_id  Post ID.
	 * @param string         $meta_key Meta key that was deleted.
	 * @return void
	 */
	public function clear_cache_on_meta_delete( $meta_ids, int $post_id, string $meta_key ): void {
		if ( 'gatherpress_attendee_count' === $meta_key && $this->is_supported_post( $post_id ) ) {
			$this->clear_cache();
		}
	}

	/**
	 * Clear cache when taxonomy terms are modified.
	 *
	 * Hooked to term creation, editing, and deletion actions. Clears all statistics
	 * caches when terms in any supported taxonomy are changed, but only if at least
	 * one statistic type that depends on term data is currently supported.
	 * The actual regeneration happens 60 seconds later via cron.
	 *
	 * This function respects the exclusion filter - if a taxonomy is excluded,
	 * changes to its terms won't trigger cache clearing.
	 *
	 * This function also checks if any term-dependent statistic types are supported
	 * before clearing cache. If none are supported (e.g., only total_events and
	 * total_attendees are enabled), term changes won't trigger cache clearing.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return void
	 */
	public function clear_cache_on_term_change( int $term_id, int $tt_id, string $taxonomy ): void {
		// First check if any term-dependent statistic types are supported
		if ( ! $this->should_clear_cache_for_term_changes() ) {
			return;
		}
		
		// Get filtered taxonomies (excludes any taxonomies filtered out)
		$supported_taxonomies = $this->get_filtered_taxonomies();

		if ( empty( $supported_taxonomies ) || ! is_array( $supported_taxonomies ) ) {
			return;
		}

		// Extract taxonomy slugs from objects
		$taxonomy_slugs = array();
		foreach ( $supported_taxonomies as $tax_obj ) {
			if ( isset( $tax_obj->name ) ) {
				$taxonomy_slugs[] = $tax_obj->name;
			}
		}

		// Only proceed if the changed term is in a supported taxonomy
		if ( in_array( $taxonomy, $taxonomy_slugs, true ) ) {
			$this->clear_cache();
		}
	}

	/**
	 * Clear cache when term relationships change.
	 *
	 * Hooked to 'set_object_terms' action. Clears all statistics caches when
	 * taxonomy terms are assigned to or removed from posts of supported post types.
	 * The actual regeneration happens 60 seconds later via cron.
	 *
	 * @since 0.1.0
	 *
	 * @param int                 $object_id Object ID (post ID).
	 * @param array<int, int>     $terms     An array of term IDs.
	 * @param array<int, int>     $tt_ids    An array of term taxonomy IDs.
	 * @return void
	 */
	public function clear_cache_on_term_relationship( int $object_id, array $terms, array $tt_ids ): void {
		// Only proceed if terms were assigned to a supported post
		if ( $this->is_supported_post( $object_id ) ) {
			$this->clear_cache();
		}
	}

	/**
	 * Get the statistics support configuration for a post type.
	 *
	 * Retrieves the configuration array that specifies which statistic types
	 * are enabled for a given post type.
	 *
	 * @since 0.1.0
	 *
	 * @param string $post_type Post type slug.
	 * @return array<string, bool> Configuration array, or empty array if not supported.
	 */
	protected function get_support_config( string $post_type = 'gatherpress_event' ): array {
		if ( ! post_type_supports( $post_type, 'gatherpress_statistics' ) ) {
			return array();
		}
		
		// Get the support args (third parameter from add_post_type_support)
		$supports = get_all_post_type_supports( $post_type );
		
		if ( isset( $supports['gatherpress_statistics'] ) && is_array( $supports['gatherpress_statistics'] ) ) {
			// Return the first element which contains our configuration
			return reset( $supports['gatherpress_statistics'] );
		}
		
		// Default configuration if none found
		return array(
			'total_events'                => true,
			'events_per_taxonomy'         => true,
			'events_multi_taxonomy'       => true,
			'total_taxonomy_terms'        => true,
			'taxonomy_terms_by_taxonomy'  => true,
			'total_attendees'             => true,
		);
	}

	/**
	 * Check if a specific statistic type is supported.
	 *
	 * @since 0.1.0
	 *
	 * @param string $statistic_type The statistic type to check.
	 * @param string $post_type      Optional. Post type to check.
	 * @return bool True if supported.
	 */
	protected function is_statistic_type_supported( string $statistic_type, string $post_type = 'gatherpress_event' ): bool {
		$config = $this->get_support_config( $post_type );
		
		if ( empty( $config ) ) {
			return false;
		}
		
		return ! empty( $config[ $statistic_type ] );
	}

	/**
	 * Get all supported statistic types for a post type.
	 *
	 * @since 0.1.0
	 *
	 * @param string $post_type Optional. Post type to check.
	 * @return array<int, string> Array of supported statistic type slugs.
	 */
	protected function get_supported_statistic_types( string $post_type = 'gatherpress_event' ): array {
		$config = $this->get_support_config( $post_type );
		
		if ( empty( $config ) ) {
			return array();
		}
		
		// Filter to only enabled types
		$enabled_types = array();
		foreach ( $config as $type => $enabled ) {
			if ( $enabled ) {
				$enabled_types[] = $type;
			}
		}
		
		return $enabled_types;
	}

	/**
	 * Get all post types that support gatherpress_statistics.
	 *
	 * This function retrieves all post types that have registered support for
	 * the 'gatherpress_statistics' feature. This allows the plugin to work with
	 * any post type that opts in, not just gatherpress_event.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, string> Array of post type slugs that support statistics.
	 */
	protected function get_supported_post_types(): array {
		$post_types = get_post_types_by_support( 'gatherpress_statistics' );
		
		if ( empty( $post_types ) || ! is_array( $post_types ) ) {
			return array();
		}
		
		return $post_types;
	}

	/**
	 * Check if any post types support gatherpress_statistics.
	 *
	 * This function verifies that at least one post type has registered support
	 * for the 'gatherpress_statistics' feature.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if at least one post type supports statistics, false otherwise.
	 */
	protected function has_supported_post_types(): bool {
		$post_types = $this->get_supported_post_types();
		return ! empty( $post_types );
	}

	/**
	 * Check if a specific post is supported for statistics.
	 *
	 * A post is considered supported if its post type supports
	 * 'gatherpress_statistics' and its status is 'publish'.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id Post ID to check.
	 * @return bool True if supported, false otherwise.
	 */
	protected function is_supported_post( int $post_id ) : bool {
		$post = get_post( $post_id );
		
		return post_type_supports( $post->post_type, 'gatherpress_statistics' ) 
			&& $post->post_status === 'publish';
	}

	/**
	 * Get all taxonomies registered for supported post types.
	 *
	 * Retrieves all taxonomy objects that are registered to post types supporting
	 * the 'gatherpress_statistics' feature. This allows the block to dynamically work with
	 * any taxonomies associated with supported post types.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, \WP_Taxonomy> Array of taxonomy objects, or empty array if none found.
	 */
	protected function get_taxonomies(): array {
		$post_types = $this->get_supported_post_types();
		
		if ( empty( $post_types ) ) {
			return array();
		}
		
		$all_taxonomies = array();
		
		// Get taxonomies for each supported post type
		foreach ( $post_types as $post_type ) {
			if ( ! post_type_exists( $post_type ) ) {
				continue;
			}
			
			$taxonomies = \get_object_taxonomies( $post_type, 'objects' );
			
			if ( ! empty( $taxonomies ) && is_array( $taxonomies ) ) {
				// Merge taxonomies, avoiding duplicates by slug
				foreach ( $taxonomies as $taxonomy ) {
					if ( isset( $taxonomy->name ) ) {
						$all_taxonomies[ $taxonomy->name ] = $taxonomy;
					}
				}
			}
		}
		
		return array_values( $all_taxonomies );
	}

	/**
	 * Get filtered taxonomies, excluding those specified by developers.
	 *
	 * This function applies the 'gatherpress_statistics_excluded_taxonomies' filter
	 * to allow developers to exclude specific taxonomies from statistics generation
	 * and block selection.
	 *
	 * By default, the '_gatherpress_venue' taxonomy is excluded.
	 *
	 * Example usage to exclude additional taxonomies:
	 *
	 * ```php
	 * add_filter( 'gatherpress_statistics_excluded_taxonomies', function( $excluded ) {
	 *     $excluded[] = 'post_tag';           // Exclude WordPress tags
	 *     $excluded[] = 'custom_event_type';  // Exclude custom taxonomy
	 *     return $excluded;
	 * } );
	 * ```
	 *
	 * @since 0.1.0
	 *
	 * @param bool $for_editor Optional. Whether this is for editor selection. Default false.
	 * @return array<int, \WP_Taxonomy> Array of taxonomy objects after exclusion filtering.
	 */
	protected function get_filtered_taxonomies( bool $for_editor = false ): array {
		$taxonomies = $this->get_taxonomies();
		
		if ( empty( $taxonomies ) || ! is_array( $taxonomies ) ) {
			return array();
		}
		
		/**
		 * Filter the list of taxonomy slugs to exclude from statistics.
		 *
		 * This filter allows developers to exclude specific taxonomies from being
		 * used in statistics calculations and from appearing in the block editor.
		 *
		 * @since 0.1.0
		 *
		 * @param array<int, string> $excluded_taxonomies Array of taxonomy slugs to exclude.
		 * @param bool               $for_editor          Whether this is for editor selection.
		 */
		$excluded_taxonomies = apply_filters(
			'gatherpress_statistics_excluded_taxonomies',
			array( '_gatherpress_venue' ),
			$for_editor
		);
		
		// Ensure excluded taxonomies is an array
		if ( ! is_array( $excluded_taxonomies ) ) {
			$excluded_taxonomies = array();
		}
		
		// Filter out excluded taxonomies
		$filtered_taxonomies = array();
		foreach ( $taxonomies as $taxonomy ) {
			if ( ! isset( $taxonomy->name ) ) {
				continue;
			}
			
			// Skip if this taxonomy is in the exclusion list
			if ( in_array( $taxonomy->name, $excluded_taxonomies, true ) ) {
				continue;
			}
			
			$filtered_taxonomies[] = $taxonomy;
		}
		
		return $filtered_taxonomies;
	}

	/**
	 * Get cache key for a specific statistic configuration.
	 *
	 * Generates a unique cache key based on the statistic type and any filters
	 * applied. This ensures that different combinations of statistics and filters
	 * are cached separately.
	 *
	 * Example cache keys:
	 * - 'gatherpress_stats_total_events_upcoming_abc123' (upcoming events)
	 * - 'gatherpress_stats_total_events_past_def456' (past events)
	 * - 'gatherpress_stats_events_per_taxonomy_upcoming_ghi789' (with taxonomy filter)
	 * - 'gatherpress_stats_total_attendees_past_jkl012' (past attendees with filters)
	 *
	 * @since 0.1.0
	 *
	 * @param string               $statistic_type The type of statistic (e.g., 'total_events').
	 * @param array<string, mixed> $filters        Additional filters (taxonomy terms, event query, etc.).
	 * @return string Cache key suitable for use with transients.
	 */
	protected function get_cache_key( string $statistic_type, array $filters = array() ): string {
		$statistic_type = is_string( $statistic_type ) ? $statistic_type : 'total_events';
		$filters = is_array( $filters ) ? $filters : array();
		
		// Start building the cache key
		$key_parts = array( 'gatherpress_stats', $statistic_type );
		
		if ( ! empty( $filters['event_query'] ) && in_array( $filters['event_query'], array( 'upcoming', 'past' ), true ) ) {
			$key_parts[] = sanitize_key( $filters['event_query'] );
		}
		
		// Add a hash of ALL filters (including event_query) to ensure uniqueness
		if ( ! empty( $filters ) ) {
			$key_parts[] = md5( wp_json_encode( $filters ) );
		}
		
		// Join parts with underscores to create the final cache key
		return implode( '_', $key_parts );
	}

	/**
	 * Get cache expiration time in seconds.
	 *
	 * Returns the number of seconds to cache statistics. Default is 12 hours (43200 seconds).
	 * Developers can modify this using the 'gatherpress_statistics_cache_expiration' filter.
	 *
	 * Example usage to set cache to 6 hours:
	 *
	 * ```php
	 * add_filter( 'gatherpress_statistics_cache_expiration', function( $expiration ) {
	 *     return 6 * HOUR_IN_SECONDS; // 6 hours
	 * } );
	 * ```
	 *
	 * Example usage to set cache to 1 hour:
	 *
	 * ```php
	 * add_filter( 'gatherpress_statistics_cache_expiration', function( $expiration ) {
	 *     return HOUR_IN_SECONDS; // 1 hour
	 * } );
	 * ```
	 *
	 * Example usage to set cache to 24 hours:
	 *
	 * ```php
	 * add_filter( 'gatherpress_statistics_cache_expiration', function( $expiration ) {
	 *     return DAY_IN_SECONDS; // 24 hours
	 * } );
	 * ```
	 *
	 * @since 0.1.0
	 *
	 * @return int Cache expiration time in seconds.
	 */
	protected function get_cache_expiration(): int {
		/**
		 * Filter the cache expiration time for statistics.
		 *
		 * This filter allows developers to modify how long statistics are cached
		 * before they need to be recalculated. The default is 12 hours.
		 *
		 * @since 0.1.0
		 *
		 * @param int $expiration Cache expiration time in seconds. Default 12 hours (43200).
		 */
		$expiration = apply_filters(
			'gatherpress_statistics_cache_expiration',
			12 * HOUR_IN_SECONDS
		);
		
		// Ensure the value is a positive integer
		if ( ! is_numeric( $expiration ) || $expiration < 1 ) {
			$expiration = 12 * HOUR_IN_SECONDS;
		}
		
		return absint( $expiration );
	}

	/**
	 * Calculate statistics based on type and filters.
	 *
	 * This is the main calculation dispatcher that routes to specific calculation
	 * functions based on the statistic type requested. All heavy lifting happens
	 * here and the results are cached for performance.
	 *
	 * IMPORTANT: This function checks if the requested statistic type is supported
	 * before performing any calculations.
	 *
	 * @since 0.1.0
	 *
	 * @param string               $statistic_type The type of statistic to calculate.
	 * @param array<string, mixed> $filters        Filters to apply (varies by statistic type).
	 * @return int Calculated statistic value, always non-negative.
	 */
	protected function calculate( string $statistic_type, array $filters = array() ): int {
		if ( ! $this->has_supported_post_types() ) {
			return 0;
		}
		
		if ( ! $this->is_statistic_type_supported( $statistic_type ) ) {
			return 0;
		}
		
		$statistic_type = is_string( $statistic_type ) ? $statistic_type : 'total_events';
		$filters = is_array( $filters ) ? $filters : array();
		
		if ( empty( $filters['event_query'] ) || ! in_array( $filters['event_query'], array( 'upcoming', 'past' ), true ) ) {
			return 0;
		}
		
		$result = 0;
		
		// Route to the appropriate calculation function based on type
		switch ( $statistic_type ) {
			case 'total_events':
				$result = $this->count_events( $filters );
				break;
				
			case 'events_per_taxonomy':
				$result = $this->count_events( $filters );
				break;
				
			case 'events_multi_taxonomy':
				$result = $this->count_events( $filters );
				break;
				
			case 'total_taxonomy_terms':
				$result = $this->count_terms( $filters );
				break;
				
			case 'taxonomy_terms_by_taxonomy':
				$result = $this->terms_by_taxonomy( $filters );
				break;
				
			case 'total_attendees':
				$result = $this->count_attendees( $filters );
				break;
		}
		
		// Ensure result is always a non-negative integer
		$result = is_numeric( $result ) ? absint( $result ) : 0;

		/**
		 * Filter calculated statistics before caching.
		 *
		 * This filter allows developers to modify calculated statistics before they
		 * are cached. Different filters are available for each statistic type:
		 *
		 * - gatherpress_stats_calculate_total_events
		 * - gatherpress_stats_calculate_events_per_taxonomy
		 * - gatherpress_stats_calculate_events_multi_taxonomy
		 * - gatherpress_stats_calculate_total_taxonomy_terms
		 * - gatherpress_stats_calculate_taxonomy_terms_by_taxonomy
		 * - gatherpress_stats_calculate_total_attendees
		 *
		 * Example usage to round count values:
		 *
		 * ```php
		 * // Round counts to nearest 10 when over 50
		 * add_filter( 'gatherpress_stats_calculate_total_events', function( $count, $filters ) {
		 *     if ( $count > 50 ) {
		 *         return round( $count / 10 ) * 10;
		 *     }
		 *     return $count;
		 * }, 10, 2 );
		 * ```
		 *
		 * Example usage to round based on value ranges:
		 *
		 * ```php
		 * add_filter( 'gatherpress_stats_calculate_total_attendees', function( $count, $filters ) {
		 *     // Round to nearest 5 if between 10-50
		 *     if ( $count >= 10 && $count <= 50 ) {
		 *         return round( $count / 5 ) * 5;
		 *     }
		 *     // Round to nearest 10 if between 50-100
		 *     if ( $count > 50 && $count <= 100 ) {
		 *         return round( $count / 10 ) * 10;
		 *     }
		 *     // Round to nearest 50 if over 100
		 *     if ( $count > 100 ) {
		 *         return round( $count / 50 ) * 50;
		 *     }
		 *     return $count;
		 * }, 10, 2 );
		 * ```
		 *
		 * Example usage to add a custom multiplier:
		 *
		 * ```php
		 * // Apply a 1.5x multiplier to all event counts
		 * add_filter( 'gatherpress_stats_calculate_total_events', function( $count, $filters ) {
		 *     return round( $count * 1.5 );
		 * }, 10, 2 );
		 * ```
		 *
		 * @since 0.1.0
		 *
		 * @param int   $count   The calculated statistic value.
		 * @param array $filters The filters applied to this statistic.
		 */
		return apply_filters( 'gatherpress_stats_calculate_' . $statistic_type, $result, $filters );
	}

	/**
	 * Count events with optional taxonomy filters and event query type.
	 *
	 * Performs a WP_Query to count published events from supported post types.
	 * Supports filtering by multiple taxonomy terms across different taxonomies
	 * using an AND relationship, as well as filtering by event query type
	 * (upcoming or past).
	 *
	 * Filter structure example:
	 * array(
	 *     'taxonomy' => 'gatherpress_topic',
	 *     'term_id' => 5,
	 *     'taxonomy_terms' => array(
	 *         'gatherpress_topic' => array( 1, 2, 3 ),  // Topic IDs
	 *         '_gatherpress_venue' => array( 4, 5 ),     // Venue IDs
	 *     ),
	 *     'event_query' => 'upcoming', // Required: 'upcoming' or 'past'
	 * )
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $filters {
	 *     Query filters.
	 *
	 *     @type string $taxonomy Single taxonomy slug (alternative to taxonomy_terms).
	 *     @type int    $term_id Single term ID (used with taxonomy).
	 *     @type array<string, array<int, int>>  $taxonomy_terms {
	 *         Associative array of taxonomy => term IDs.
	 *
	 *         @type array<int, int> $taxonomy_slug Array of term IDs for this taxonomy.
	 *     }
	 *     @type string $event_query GatherPress event query type: 'upcoming' or 'past' (required).
	 * }
	 * @return int Number of events matching the filters.
	 */
	protected function count_events( array $filters = array() ): int {
		$post_types = $this->get_supported_post_types();
		
		if ( empty( $post_types ) ) {
			return 0;
		}
		
		$filters = is_array( $filters ) ? $filters : array();
		
		$args = array(
			#'post_type'      => 'gatherpress_event',
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);
		
		if ( isset( $filters['event_query'] ) && is_string( $filters['event_query'] ) ) {
			$event_query = sanitize_key( $filters['event_query'] );
			if ( in_array( $event_query, array( 'upcoming', 'past' ), true ) ) {
				$args['gatherpress_event_query'] = $event_query;
			}
		}
		
		// Handle single taxonomy filter (taxonomy + term_id)
		if ( ! empty( $filters['taxonomy'] ) && ! empty( $filters['term_id'] ) ) {
			if ( taxonomy_exists( $filters['taxonomy'] ) ) {
				$args['tax_query'] = array(
					array(
						'taxonomy' => sanitize_key( $filters['taxonomy'] ),
						'field'    => 'term_id',
						'terms'    => absint( $filters['term_id'] ),
					),
				);
			}
		}
		// Handle multiple taxonomy filters (taxonomy_terms)
		else if ( ! empty( $filters['taxonomy_terms'] ) && is_array( $filters['taxonomy_terms'] ) ) {
			// Use AND relation so events must match ALL specified taxonomies
			$tax_query = array( 'relation' => 'AND' );
			
			// Loop through each taxonomy and its terms
			foreach ( $filters['taxonomy_terms'] as $taxonomy => $term_ids ) {
				// Validate that we have term IDs and the taxonomy exists
				if ( ! empty( $term_ids ) && is_array( $term_ids ) && taxonomy_exists( $taxonomy ) ) {
					$tax_query[] = array(
						'taxonomy' => sanitize_key( $taxonomy ),
						'field'    => 'term_id',
						'terms'    => array_map( 'absint', $term_ids ),
					);
				}
			}
			
			// Only add tax_query if we have at least one taxonomy condition
			if ( count( $tax_query ) > 1 ) {
				$args['tax_query'] = $tax_query;
			}
		}
		
		$query = new \WP_Query( $args );
		
		return absint( $query->found_posts );
	}

	/**
	 * Count total terms in a taxonomy.
	 *
	 * Counts the number of non-empty terms in a specified taxonomy. Only counts
	 * terms that are actually assigned to at least one published event from
	 * supported post types.
	 *
	 * Example usage:
	 * $filters = array( 'taxonomy' => '_gatherpress_venue' );
	 * $count = count_terms( $filters );
	 * // Returns: 7 (if there are 7 venues with events)
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $filters {
	 *     Filters including taxonomy.
	 *
	 *     @type string $taxonomy The taxonomy slug to count terms from.
	 * }
	 * @return int Number of non-empty terms in the taxonomy.
	 */
	protected function count_terms( array $filters = array() ): int {
		$post_types = $this->get_supported_post_types();
		
		if ( empty( $post_types ) ) {
			return 0;
		}
		
		$filters = is_array( $filters ) ? $filters : array();
		$taxonomy = isset( $filters['taxonomy'] ) && is_string( $filters['taxonomy'] ) ? $filters['taxonomy'] : '';
		
		// Validate taxonomy parameter
		if ( empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
			return 0;
		}
		
		// Get terms, hiding empty ones (terms with no posts)
		$args = array(
			'taxonomy'   => sanitize_key( $taxonomy ),
			'hide_empty' => true,  // Only count terms with posts
			'object_ids' => null,  // Will filter by post type below
		);
		
		// Get all post IDs from supported post types
		$post_query = new \WP_Query(
			array(
				'post_type'      => $post_types,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
			)
		);
		
		if ( ! empty( $post_query->posts ) ) {
			$args['object_ids'] = $post_query->posts;
		}
		
		$terms = \get_terms( $args );
		
		// Handle errors or empty results
		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return 0;
		}
		
		return absint( count( $terms ) );
	}

	/**
	 * Count terms of one taxonomy that have events in another taxonomy.
	 *
	 * This complex function answers questions like "How many venues host Technology
	 * events?" It counts unique terms from one taxonomy (count_taxonomy) that appear
	 * on events filtered by another taxonomy (filter_taxonomy).
	 *
	 * Example scenario with 15 events, 3 topics, 3 venues:
	 * - Events: E1-E15
	 * - Topics: Technology (T1), Workshop (T2), Networking (T3)
	 * - Venues: Downtown Hall (V1), Tech Center (V2), Community Space (V3)
	 *
	 * Query: "How many venues host Technology events?"
	 * $filters = array(
	 *     'count_taxonomy'  => '_gatherpress_venue',    // Count venues
	 *     'filter_taxonomy' => 'gatherpress_topic',     // Filter by topic
	 *     'term_id'         => 1,                        // Topic ID for "Technology"
	 * );
	 * Result: 2 (if Technology events are at V1 and V2 only)
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $filters {
	 *     Filters for cross-taxonomy counting.
	 *
	 *     @type string $count_taxonomy  Taxonomy to count terms from.
	 *     @type string $filter_taxonomy Taxonomy to filter events by.
	 *     @type int    $term_id         Term ID in filter_taxonomy.
	 * }
	 * @return int Number of unique terms from count_taxonomy.
	 */
	protected function terms_by_taxonomy( array $filters = array() ): int {
		$post_types = $this->get_supported_post_types();
		
		if ( empty( $post_types ) ) {
			return 0;
		}
		
		$filters = is_array( $filters ) ? $filters : array();
		
		// Extract parameters
		$count_taxonomy  = isset( $filters['count_taxonomy'] ) && is_string( $filters['count_taxonomy'] ) ? $filters['count_taxonomy'] : '';
		$filter_taxonomy = isset( $filters['filter_taxonomy'] ) && is_string( $filters['filter_taxonomy'] ) ? $filters['filter_taxonomy'] : '';
		$term_id         = isset( $filters['term_id'] ) ? absint( $filters['term_id'] ) : 0;
		
		// Validate required parameters
		if ( empty( $count_taxonomy ) || empty( $filter_taxonomy ) || $term_id === 0 ) {
			return 0;
		}
		
		// Validate taxonomies exist
		if ( ! taxonomy_exists( $count_taxonomy ) || ! taxonomy_exists( $filter_taxonomy ) ) {
			return 0;
		}
		
		// Step 1: Get all events in the filter taxonomy term
		$args = array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'tax_query'      => array(
				array(
					'taxonomy' => sanitize_key( $filter_taxonomy ),
					'field'    => 'term_id',
					'terms'    => $term_id,
				),
			),
		);
		
		$query = new \WP_Query( $args );
		$terms = array();
		
		// Step 2: For each event, get its terms from count_taxonomy
		if ( is_array( $query->posts ) ) {
			foreach ( $query->posts as $post_id ) {
				// Get all terms for this post in the count_taxonomy
				$post_terms = wp_get_post_terms( $post_id, sanitize_key( $count_taxonomy ), array( 'fields' => 'ids' ) );
				
				// Accumulate term IDs
				if ( ! is_wp_error( $post_terms ) && is_array( $post_terms ) && ! empty( $post_terms ) ) {
					$terms = array_merge( $terms, $post_terms );
				}
			}
		}

		// Step 3: Count unique terms
		// array_unique removes duplicates, count gives us the total
		return absint( count( array_unique( $terms ) ) );
	}

	/**
	 * Count total attendees with optional taxonomy filters and event query type.
	 *
	 * Sums the 'gatherpress_attendee_count' post meta across all matching events
	 * from supported post types. Supports filtering by single taxonomy term,
	 * multiple taxonomy terms, and event query type (upcoming/past).
	 *
	 * Example with 15 events:
	 * - Event 1: 25 attendees, Technology topic, upcoming
	 * - Event 2: 30 attendees, Technology topic, upcoming
	 * - Event 3: 15 attendees, Workshop topic, past
	 * - ... etc
	 *
	 * Query 1: Attendees at upcoming events (no taxonomy filter)
	 * $filters = array( 'event_query' => 'upcoming' );
	 * Result: Sum of all attendees at upcoming events
	 *
	 * Query 2: Attendees at upcoming Technology events
	 * $filters = array(
	 *     'taxonomy' => 'gatherpress_topic',
	 *     'term_id'  => 1,  // Technology
	 *     'event_query' => 'upcoming',
	 * );
	 * Result: 55 (25 + 30 from upcoming Technology events)
	 *
	 * Query 3: Attendees at Technology events in Downtown venue
	 * $filters = array(
	 *     'taxonomy_terms' => array(
	 *         'gatherpress_topic' => array( 1 ),     // Technology
	 *         '_gatherpress_venue' => array( 1 ),    // Downtown
	 *     ),
	 *     'event_query' => 'upcoming',
	 * );
	 * Result: Sum of attendees matching both filters
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $filters {
	 *     Query filters.
	 *
	 *     @type string $taxonomy       Single taxonomy slug (alternative to taxonomy_terms).
	 *     @type int    $term_id        Single term ID (used with taxonomy).
	 *     @type array<string, array<int, int>>  $taxonomy_terms Multi-taxonomy filter array.
	 *     @type string $event_query    Required. GatherPress event query type: 'upcoming' or 'past'.
	 * }
	 * @return int Total number of attendees.
	 */
	protected function count_attendees( array $filters = array() ): int {
		$post_types = $this->get_supported_post_types();
		
		if ( empty( $post_types ) ) {
			return 0;
		}
		$filters = is_array( $filters ) ? $filters : array();
		
		$args = array(
			#'post_type'      => 'gatherpress_event',
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);
		
		// Add GatherPress event query parameter with proper validation
		if ( isset( $filters['event_query'] ) && is_string( $filters['event_query'] ) ) {
			$event_query = sanitize_key( $filters['event_query'] );
			if ( in_array( $event_query, array( 'upcoming', 'past' ), true ) ) {
				$args['gatherpress_event_query'] = $event_query;
			}
		}
		
		// Apply single taxonomy filter if provided
		if ( ! empty( $filters['taxonomy'] ) && ! empty( $filters['term_id'] ) ) {
			if ( taxonomy_exists( $filters['taxonomy'] ) ) {
				$args['tax_query'] = array(
					array(
						'taxonomy' => sanitize_key( $filters['taxonomy'] ),
						'field'    => 'term_id',
						'terms'    => absint( $filters['term_id'] ),
					),
				);
			}
		}
		// Apply multiple taxonomy filters if provided
		else if ( ! empty( $filters['taxonomy_terms'] ) && is_array( $filters['taxonomy_terms'] ) ) {
			// Use AND relation so events must match ALL specified taxonomies
			$tax_query = array( 'relation' => 'AND' );
			
			foreach ( $filters['taxonomy_terms'] as $taxonomy => $term_ids ) {
				if ( ! empty( $term_ids ) && is_array( $term_ids ) && taxonomy_exists( $taxonomy ) ) {
					$tax_query[] = array(
						'taxonomy' => sanitize_key( $taxonomy ),
						'field'    => 'term_id',
						'terms'    => array_map( 'absint', $term_ids ),
					);
				}
			}
			
			// Only add tax_query if we have at least one taxonomy condition
			if ( count( $tax_query ) > 1 ) {
				$args['tax_query'] = $tax_query;
			}
		}
		
		// Execute the query to get matching event IDs
		$query = new \WP_Query( $args );
		$total_attendees = 0;
		
		// Sum attendee counts from post meta
		if ( is_array( $query->posts ) && ! empty( $query->posts ) ) {
			foreach ( $query->posts as $post_id ) {
				// Get the attendee count for this event
				$attendee_count = (int) get_post_meta( $post_id, 'gatherpress_attendee_count', true );
				
				// Add to total if it's a valid number
				if ( is_numeric( $attendee_count ) ) {
					$total_attendees += absint( $attendee_count );
				}
			}
		}
		
		return absint( $total_attendees );
	}

	/**
	 * Get statistic with caching.
	 *
	 * This is the main entry point for retrieving statistics. It implements a
	 * caching layer using WordPress transients to avoid expensive recalculations.
	 *
	 * Cache workflow:
	 * 1. Check if cached value exists (transient)
	 * 2. If yes, return cached value
	 * 3. If no, calculate value
	 * 4. Store in cache for 12 hours
	 * 5. Return calculated value
	 *
	 * The cache is automatically invalidated when:
	 * - Events are created, updated, or deleted
	 * - Taxonomy terms are modified
	 * - Post meta (attendee counts) changes
	 *
	 * @since 0.1.0
	 *
	 * @param string               $statistic_type Statistic type to retrieve.
	 * @param array<string, mixed> $filters        Filters to apply to the statistic.
	 * @return int Statistic value (cached or freshly calculated).
	 */
	public function get_cached( string $statistic_type, array $filters = array() ): int {
		if ( ! is_string( $statistic_type ) || empty( $statistic_type ) ) {
			return 0;
		}
		
		if ( ! is_array( $filters ) ) {
			$filters = array();
		}
		
		if ( ! $this->has_supported_post_types() ) {
			return 0;
		}
		
		if ( ! $this->is_statistic_type_supported( $statistic_type ) ) {
			return 0;
		}

		// Get configured cache expiration time
		$expiration = $this->get_cache_expiration();

		// Generate unique cache key for this configuration
		$cache_key = $this->get_cache_key( $statistic_type, $filters );
		
		// Try to get cached value
		$cached = get_transient( $cache_key );
		
		// Validate cached value and return if valid
		if ( false !== $cached && is_numeric( $cached ) ) {
			return absint( $cached );
		}
		
		// Cache miss - calculate the value
		$value = $this->calculate( $statistic_type, $filters );
		
		// Ensure value is integer
		$value = is_numeric( $value ) ? absint( $value ) : 0;

		// Store in cache with configured expiration time
		// Note: Cache is cleared automatically on data changes
		\set_transient( $cache_key, $value, $expiration );
		
		return $value;
	}

	/**
	 * Get all common statistic configurations to pre-generate.
	 *
	 * Generates an array of common statistic configurations that should be
	 * pre-calculated and cached. This is called after cache clearing to ensure
	 * frequently-used statistics are immediately available.
	 *
	 * This function respects the statistic type support configuration
	 * and only generates configurations for enabled types.
	 *
	 * This function respects the taxonomy exclusion filter and will not generate
	 * statistics for excluded taxonomies. It generates statistics ONLY for both
	 * upcoming and past events (never for all events combined).
	 *
	 * Example output for a setup with 3 topics, 3 venues, 15 events:
	 *
	 * array(
	 *     // Basic counts - upcoming and past
	 *     array( 'type' => 'total_events', 'filters' => array( 'event_query' => 'upcoming' ) ),
	 *     array( 'type' => 'total_events', 'filters' => array( 'event_query' => 'past' ) ),
	 *     array( 'type' => 'total_attendees', 'filters' => array( 'event_query' => 'upcoming' ) ),
	 *     array( 'type' => 'total_attendees', 'filters' => array( 'event_query' => 'past' ) ),
	 *
	 *     // Per-term statistics (3 topics × 2 stat types × 2 event queries = 12 entries)
	 *     array( 'type' => 'events_per_taxonomy', 'filters' => array( 'taxonomy' => 'gatherpress_topic', 'term_id' => 1, 'event_query' => 'upcoming' ) ),
	 *     array( 'type' => 'events_per_taxonomy', 'filters' => array( 'taxonomy' => 'gatherpress_topic', 'term_id' => 1, 'event_query' => 'past' ) ),
	 *     // ... etc
	 * )
	 *
	 * Total: ~80-120 pre-generated cache entries for this example setup
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, array{type: string, filters: array<string, mixed>}> Array of configuration arrays.
	 */
	protected function get_common_configs(): array {
		$configs = array();
		
		$supported_types = $this->get_supported_statistic_types();
		
		if ( empty( $supported_types ) ) {
			return array();
		}
		
		// Event query types to generate statistics for (ONLY upcoming and past, never empty/all)
		$event_queries = array( 'upcoming', 'past' );
		
		// Basic statistics for each event query type (only if supported)
		foreach ( $event_queries as $event_query ) {
			if ( in_array( 'total_events', $supported_types, true ) ) {
				$configs[] = array(
					'type'    => 'total_events',
					'filters' => array( 'event_query' => $event_query ),
				);
			}
		}
		
		// Total attendees should only be pre-generated for PAST events
		if ( in_array( 'total_attendees', $supported_types, true ) ) {
			$configs[] = array(
				'type'    => 'total_attendees',
				'filters' => array( 'event_query' => 'past' ),
			);
		}
		
		// Get filtered taxonomies (respects exclusion filter)
		$taxonomies = $this->get_filtered_taxonomies();
		
		if ( empty( $taxonomies ) || ! is_array( $taxonomies ) ) {
			return $configs;
		}
		
		// Generate configurations for each taxonomy
		foreach ( $taxonomies as $taxonomy ) {
			if ( ! isset( $taxonomy->name ) ) {
				continue;
			}
			
			// Total terms count for this taxonomy (only if supported)
			if ( in_array( 'total_taxonomy_terms', $supported_types, true ) ) {
				$configs[] = array(
					'type'    => 'total_taxonomy_terms',
					'filters' => array( 'taxonomy' => $taxonomy->name ),
				);
			}
			
			// Get all terms in this taxonomy
			$terms = \get_terms(
				array(
					'taxonomy'   => $taxonomy->name,
					'hide_empty' => false,
				)
			);
			
			if ( ! is_wp_error( $terms ) && is_array( $terms ) && ! empty( $terms ) ) {
				// Generate statistics for each term with each event query type
				foreach ( $terms as $term ) {
					if ( ! isset( $term->term_id ) ) {
						continue;
					}
					
					foreach ( $event_queries as $event_query ) {
						$filters = array(
							'taxonomy'    => $taxonomy->name,
							'term_id'     => $term->term_id,
							'event_query' => $event_query,
						);
						
						// Events in this term (only if supported)
						if ( in_array( 'events_per_taxonomy', $supported_types, true ) ) {
							$configs[] = array(
								'type'    => 'events_per_taxonomy',
								'filters' => $filters,
							);
						}
					}
					
					// Attendees at events in this term - ONLY for PAST events
					if ( in_array( 'total_attendees', $supported_types, true ) ) {
						$configs[] = array(
							'type'    => 'total_attendees',
							'filters' => array(
								'taxonomy'    => $taxonomy->name,
								'term_id'     => $term->term_id,
								'event_query' => 'past',
							),
						);
					}
				}
			}
		}

		// Generate cross-taxonomy configurations if we have multiple taxonomies
		// and if taxonomy_terms_by_taxonomy is supported
		if ( in_array( 'taxonomy_terms_by_taxonomy', $supported_types, true ) 
			&& is_array( $taxonomies ) 
			&& count( $taxonomies ) > 1 ) {
			$taxonomy_array = array_values( $taxonomies );
			
			// Loop through each pair of taxonomies
			for ( $i = 0; $i < count( $taxonomy_array ); $i++ ) {
				for ( $j = 0; $j < count( $taxonomy_array ); $j++ ) {
					// Skip if same taxonomy
					if ( $i !== $j ) {
						$filter_tax = $taxonomy_array[ $i ];
						$count_tax  = $taxonomy_array[ $j ];
						
						if ( ! isset( $filter_tax->name ) || ! isset( $count_tax->name ) ) {
							continue;
						}
						
						// Get up to 10 terms from filter taxonomy to keep pre-generation manageable
						$terms = \get_terms(
							array(
								'taxonomy'   => $filter_tax->name,
								'hide_empty' => false,
								'number'     => 10,
							)
						);
						
						if ( ! is_wp_error( $terms ) && is_array( $terms ) && ! empty( $terms ) ) {
							foreach ( $terms as $term ) {
								if ( ! isset( $term->term_id ) ) {
									continue;
								}
								
								// "How many X taxonomy terms have events in Y term?"
								$configs[] = array(
									'type'    => 'taxonomy_terms_by_taxonomy',
									'filters' => array(
										'count_taxonomy'  => $count_tax->name,
										'filter_taxonomy' => $filter_tax->name,
										'term_id'         => $term->term_id,
									),
								);
							}
						}
					}
				}
			}
		}
		
		return $configs;
	}

	/**
	 * Pre-generate common statistics after cache clear.
	 *
	 * This function runs as a scheduled cron job to regenerate commonly-used
	 * statistics. This ensures that frequently accessed statistics are available
	 * without delay when visitors load the site, while preventing resource-heavy
	 * operations during bulk edits.
	 *
	 * Only generates statistics for:
	 * - non-excluded taxonomies by using the filtered taxonomy list
	 * - upcoming and past events, never for all events.
	 * - enabled statistic types.
	 *
	 * Process:
	 * 1. Get list of common configurations
	 * 2. For each configuration:
	 *    a. Calculate the statistic
	 *    b. Store in transient cache
	 * 3. All done in background via cron, doesn't block user requests
	 *
	 * Example execution time for 15 events, 3 topics, 3 venues:
	 * - ~80-120 statistics to pre-generate (only upcoming/past variants)
	 * - Each calculation: 0.005-0.02 seconds
	 * - Total: 0.4-2.4 seconds (runs in background via cron)
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function pregenerate_cache(): void {
		// Only proceed if any post types support statistics
		if ( ! $this->has_supported_post_types() ) {
			return;
		}
		
		// Get array of common statistic configurations (filtered by supported types)
		$configs = $this->get_common_configs();
		
		if ( ! is_array( $configs ) ) {
			return;
		}

		// Get configured cache expiration time
		$expiration = $this->get_cache_expiration();

		// Loop through each configuration and pre-generate
		foreach ( $configs as $config ) {
			// Validate configuration has required keys
			if ( ! isset( $config['type'] ) || ! isset( $config['filters'] ) ) {
				continue;
			}
			
			// Generate the cache key
			$cache_key = $this->get_cache_key(
				$config['type'],
				$config['filters']
			);
			
			// Calculate the statistic value
			$value = $this->calculate(
				$config['type'],
				$config['filters']
			);
			
			// Ensure value is a non-negative integer
			$value = is_numeric( $value ) ? absint( $value ) : 0;
			
			// Store in cache with configured expiration time
			\set_transient( $cache_key, $value, $expiration );
		}
	}

	/**
	 * Clear all statistics caches and schedule regeneration.
	 *
	 * Removes all transients related to GatherPress statistics from the database.
	 * Instead of immediately regenerating (which can be resource-intensive during
	 * bulk edits), this function schedules a single-run cron job to regenerate
	 * the cache 60 seconds after the trigger.
	 *
	 * This approach prevents:
	 * - Multiple regenerations during bulk operations
	 * - Resource exhaustion during large updates
	 * - Slow response times during admin operations
	 *
	 * Technical details:
	 * - Uses direct database query for efficiency
	 * - Deletes both the transient and its timeout record
	 * - Transient naming pattern: '_transient_gatherpress_stats_*'
	 * - Schedules one-time cron event with 60-second delay
	 * - Subsequent calls within 60 seconds won't schedule duplicate jobs
	 *
	 * @since 0.1.0
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 * @return void
	 */
	public function clear_cache(): void {
		global $wpdb;
		
		// Delete all statistics transients from options table immediately
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_gatherpress_stats_%' 
			OR option_name LIKE '_transient_timeout_gatherpress_stats_%'"
		);
		
		// Check if a regeneration job is already scheduled
		$scheduled = wp_next_scheduled( 'gatherpress_statistics_regenerate_cache' );
		
		// Only schedule if not already scheduled
		if ( ! $scheduled ) {
			// Schedule regeneration to run once, 60 seconds from now
			// This prevents multiple regenerations during bulk operations
			wp_schedule_single_event(
				time() + 60,
				'gatherpress_statistics_regenerate_cache'
			);
		}
	}

	/**
	 * Check if term changes require cache clearing.
	 *
	 * Determines whether a taxonomy term change should trigger cache clearing by checking
	 * if any statistic types that depend on term data are currently supported.
	 *
	 * The following statistic types require cache clearing for term changes:
	 * - events_per_taxonomy: Counts events in specific terms
	 * - events_multi_taxonomy: Filters by multiple terms
	 * - total_taxonomy_terms: Counts total terms
	 * - taxonomy_terms_by_taxonomy: Counts cross-taxonomy relationships
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if any term-dependent statistic types are supported, false otherwise.
	 */
	protected function should_clear_cache_for_term_changes(): bool {
		$supported_types = $this->get_supported_statistic_types();
		
		if ( empty( $supported_types ) ) {
			return false;
		}
		
		// Statistic types that require cache clearing when terms change
		$term_dependent_types = array(
			'events_per_taxonomy',
			'events_multi_taxonomy',
			'total_taxonomy_terms',
			'taxonomy_terms_by_taxonomy',
		);
		
		// Check if any term-dependent types are supported
		foreach ( $term_dependent_types as $type ) {
			if ( in_array( $type, $supported_types, true ) ) {
				return true;
			}
		}
		
		return false;
	}
}
