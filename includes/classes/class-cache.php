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
		$statistic_type = is_string( $statistic_type ) ? $statistic_type : 'total_events';
		$filters        = is_array( $filters ) ? $filters : array();
		
		$key_parts = array( 'gatherpress_stats', $statistic_type );
		
		if ( ! empty( $filters['event_query'] ) && in_array( $filters['event_query'], array( 'upcoming', 'past' ), true ) ) {
			$key_parts[] = sanitize_key( $filters['event_query'] );
		}
		
		if ( ! empty( $filters ) ) {
			$key_parts[] = md5( wp_json_encode( $filters ) );
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
		$expiration = apply_filters(
			'gatherpress_statistics_cache_expiration',
			12 * HOUR_IN_SECONDS
		);
		
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
		if ( ! is_string( $statistic_type ) || empty( $statistic_type ) ) {
			return 0;
		}
		
		if ( ! is_array( $filters ) ) {
			$filters = array();
		}
		
		if ( ! Support::get_instance()->has_supported_post_types() ) {
			return 0;
		}
		
		if ( ! Support::get_instance()->is_statistic_type_supported( $statistic_type ) ) {
			return 0;
		}
		
		// Check if archive is enabled and we have year/month filters
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
		
		$value = is_numeric( $value ) ? absint( $value ) : 0;
		
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
		
		if ( empty( $taxonomies ) || ! is_array( $taxonomies ) ) {
			return $configs;
		}
		
		foreach ( $taxonomies as $taxonomy ) {
			if ( ! isset( $taxonomy->name ) ) {
				continue;
			}
			
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
			
			if ( ! is_wp_error( $terms ) && is_array( $terms ) && ! empty( $terms ) ) {
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
			&& is_array( $taxonomies ) 
			&& count( $taxonomies ) > 1 ) {
			$taxonomy_array = array_values( $taxonomies );
			
			for ( $i = 0; $i < count( $taxonomy_array ); $i++ ) {
				for ( $j = 0; $j < count( $taxonomy_array ); $j++ ) {
					if ( $i !== $j ) {
						$filter_tax = $taxonomy_array[ $i ];
						$count_tax  = $taxonomy_array[ $j ];
						
						if ( ! isset( $filter_tax->name ) || ! isset( $count_tax->name ) ) {
							continue;
						}
						
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
		
		if ( ! is_array( $configs ) ) {
			return;
		}

		$expiration = $this->get_cache_expiration();

		foreach ( $configs as $config ) {
			if ( ! isset( $config['type'] ) || ! isset( $config['filters'] ) ) {
				continue;
			}
			
			$cache_key = $this->get_cache_key(
				$config['type'],
				$config['filters']
			);
			
			$value = Statistics::get_instance()->calculate(
				$config['type'],
				$config['filters']
			);
			
			$value = is_numeric( $value ) ? absint( $value ) : 0;
			
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
		global $wpdb;
		
		$wpdb->query(
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
