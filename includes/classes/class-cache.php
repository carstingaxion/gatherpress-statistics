<?php
/**
 * Transient caching and cache pre-generation for statistics.
 *
 * @package GatherPressStatistics
 */

namespace GatherPressStatistics;

use GatherPress\Core;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Cache management class.
 *
 * @since 0.1.0
 */
class Cache {

	use Core\Traits\Singleton;

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	protected function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Set up hooks for various purposes.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	protected function setup_hooks(): void {
		add_action( 'gatherpress_statistics_regenerate_cache', array( $this, 'pregenerate_cache' ) );
	}

	/**
	 * Get cache key for a specific statistic configuration.
	 *
	 * @since 0.1.0
	 *
	 * @param string               $statistic_type The type of statistic.
	 * @param array<string, mixed> $filters        Additional filters.
	 * @return string Cache key.
	 */
	public function get_cache_key( string $statistic_type, array $filters = array() ): string {
		$statistic_type = ! empty( $statistic_type ) ? $statistic_type : 'total_events';
		
		$key_parts = array( 'gatherpress_stats', $statistic_type );
		
		if ( ! empty( $filters['event_query'] ) && in_array( $filters['event_query'], array( 'upcoming', 'past' ), true ) ) {
			$key_parts[] = sanitize_key( $filters['event_query'] );
		}
		
		if ( ! empty( $filters ) ) {
			$key_parts[] = md5( (string) wp_json_encode( $filters ) );
		}
		
		return implode( '_', $key_parts );
	}

	/**
	 * Get cache expiration time in seconds.
	 *
	 * @since 0.1.0
	 *
	 * @return int Cache expiration time in seconds.
	 */
	public function get_cache_expiration(): int {
		/**
		 * Filters the statistics cache expiration time.
		 *
		 * @since 0.1.0
		 *
		 * @param int $expiration Cache expiration time in seconds. Default: `12 * HOUR_IN_SECONDS`.
		 *
		 * @example
		 * ```php
		 * // Cache for 6 hours instead of the 12-hour default.
		 * add_filter( 'gatherpress_statistics_cache_expiration', function ( int $expiration ): int {
		 *     return 6 * HOUR_IN_SECONDS;
		 * } );
		 * ```
		 */
		$expiration = apply_filters(
			'gatherpress_statistics_cache_expiration',
			12 * HOUR_IN_SECONDS
		);

		// @phpstan-ignore-next-line
		if ( ! is_numeric( $expiration ) || $expiration < 1 ) {
			$expiration = 12 * HOUR_IN_SECONDS;
		}
		
		return absint( $expiration );
	}

	/**
	 * Get statistic with caching.
	 *
	 * @since 0.1.0
	 *
	 * @param string               $statistic_type Statistic type to retrieve.
	 * @param array<string, mixed> $filters        Filters to apply.
	 * @return int Statistic value.
	 */
	public function get_cached( string $statistic_type, array $filters = array() ): int {
		if ( empty( $statistic_type ) ) {
			return 0;
		}
		
		if ( ! Support::get_instance()->has_supported_post_types() ) {
			return 0;
		}
		
		if ( ! Support::get_instance()->is_statistic_type_supported( $statistic_type ) ) {
			return 0;
		}
		
		// Check if archive is enabled and we have year/month filters.
		if ( Plugin::get_instance()->is_archive_enabled() && ! empty( $filters['year'] ) && ! empty( $filters['month'] ) ) {
			$archive_value = Database::get_instance()->get_archive_statistic( $statistic_type, $filters );
			if ( $archive_value !== null ) {
				return $archive_value;
			}
		}

		$expiration = $this->get_cache_expiration();

		$cache_key = $this->get_cache_key( $statistic_type, $filters );
		
		$cached = get_transient( $cache_key );
		
		if ( false !== $cached && is_numeric( $cached ) ) {
			return absint( $cached );
		}
		
		$value = Statistics::get_instance()->calculate( $statistic_type, $filters );
		
		\set_transient( $cache_key, $value, $expiration );
		
		return $value;
	}

	/**
	 * Get all common statistic configurations to pre-generate.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, array{type: string, filters: array<string, mixed>}> Array of configurations.
	 */
	public function get_common_configs(): array {
		$configs = array();
		
		$supported_types = Support::get_instance()->get_supported_statistic_types();
		
		if ( empty( $supported_types ) ) {
			return array();
		}
		
		$event_queries = array( 'upcoming', 'past' );
		
		foreach ( $event_queries as $event_query ) {
			if ( in_array( 'total_events', $supported_types, true ) ) {
				$configs[] = array(
					'type'    => 'total_events',
					'filters' => array( 'event_query' => $event_query ),
				);
			}
		}
		
		if ( in_array( 'total_attendees', $supported_types, true ) ) {
			$configs[] = array(
				'type'    => 'total_attendees',
				'filters' => array( 'event_query' => 'past' ),
			);
		}
		
		$taxonomies = Taxonomy::get_instance()->get_filtered_taxonomies();
		
		if ( empty( $taxonomies ) ) {
			return $configs;
		}
		
		foreach ( $taxonomies as $taxonomy ) {
			if ( in_array( 'total_taxonomy_terms', $supported_types, true ) ) {
				$configs[] = array(
					'type'    => 'total_taxonomy_terms',
					'filters' => array( 'taxonomy' => $taxonomy->name ),
				);
			}
			
			$terms = \get_terms(
				array(
					'taxonomy'   => $taxonomy->name,
					'hide_empty' => false,
				)
			);
			
			if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
				foreach ( $terms as $term ) {
					foreach ( $event_queries as $event_query ) {
						$filters = array(
							'taxonomy'    => $taxonomy->name,
							'term_id'     => $term->term_id,
							'event_query' => $event_query,
						);
						
						if ( in_array( 'events_per_taxonomy', $supported_types, true ) ) {
							$configs[] = array(
								'type'    => 'events_per_taxonomy',
								'filters' => $filters,
							);
						}
					}
					
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
		
		if ( in_array( 'taxonomy_terms_by_taxonomy', $supported_types, true ) 
			&& count( $taxonomies ) > 1 ) {
			$taxonomy_array = array_values( $taxonomies );
			$taxonomy_count = count( $taxonomy_array );
			
			for ( $i = 0; $i < $taxonomy_count; $i++ ) {
				for ( $j = 0; $j < $taxonomy_count; $j++ ) {
					if ( $i !== $j ) {
						$filter_tax = $taxonomy_array[ $i ];
						$count_tax  = $taxonomy_array[ $j ];

						$terms = get_terms(
							array(
								'taxonomy'   => $filter_tax->name,
								'hide_empty' => false,
								'number'     => 10,
							)
						);
						
						if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
							foreach ( $terms as $term ) {
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
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function pregenerate_cache(): void {
		if ( ! Support::get_instance()->has_supported_post_types() ) {
			return;
		}
		
		$configs = $this->get_common_configs();

		$expiration = $this->get_cache_expiration();

		foreach ( $configs as $config ) {
			$cache_key = $this->get_cache_key(
				$config['type'],
				$config['filters']
			);
			
			$value = Statistics::get_instance()->calculate(
				$config['type'],
				$config['filters']
			);
			
			\set_transient( $cache_key, $value, $expiration );
		}
	}

	/**
	 * Clear all statistics caches and schedule regeneration.
	 *
	 * @since 0.1.0
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 * @return void
	 */
	public function clear_cache(): void {
		/**
		 * Help phpstan understand $wpdb is global.
		 * 
		 * @var \wpdb  $wpdb WordPress database abstraction object.
		 */
		global $wpdb;
		
		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			"DELETE FROM {$wpdb->options} 
			WHERE option_name LIKE '_transient_gatherpress_stats_%' 
			OR option_name LIKE '_transient_timeout_gatherpress_stats_%'"
		);
		
		$scheduled = wp_next_scheduled( 'gatherpress_statistics_regenerate_cache' );
		
		if ( ! $scheduled ) {
			wp_schedule_single_event(
				time() + 60,
				'gatherpress_statistics_regenerate_cache'
			);
		}
	}
}
