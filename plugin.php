<?php
/**
 * Plugin Name:       GatherPress Statistics
 * Description:       Display dynamically calculated statistics about your GatherPress events with beautiful, cached counters.
 * Version:           0.1.0
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * Requires Plugins:  gatherpress
 * Author:            WordPress Telex
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gatherpress-statistics
 *
 * @package GatherPressStatistics
 */

namespace GatherPressStatistics;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GATHERPRESS_STATISTICS_VERSION', '0.1.0' );
define( 'GATHERPRESS_STATISTICS_CORE_PATH', __DIR__ );

// ============================================================================
// FILE: includes/core/class-plugin.php
// ============================================================================

/**
 * Main plugin class using singleton pattern.
 *
 * @since 0.1.0
 */
class Plugin {
	/**
	 * Plugin instance.
	 *
	 * @since 0.1.0
	 * @var Plugin|null
	 */
	private static $instance = null;

	/**
	 * Get plugin instance.
	 *
	 * @since 0.1.0
	 *
	 * @return Plugin
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {
		$this->setup_hooks();
	}

	/**
	 * Setup WordPress hooks.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	private function setup_hooks(): void {
		add_action( 'registered_post_type_gatherpress_event', array( Setup::get_instance(), 'register_post_type_support' ) );
		add_action( 'init', array( Setup::get_instance(), 'block_init' ) );
		add_action( 'init', array( Database::get_instance(), 'create_archive_table' ) );
		add_action( 'rest_api_init', array( RestApi::get_instance(), 'register_rest_routes' ) );
		add_action( 'admin_menu', array( AdminPage::get_instance(), 'register_admin_page' ) );
		add_action( 'admin_init', array( AdminPage::get_instance(), 'handle_manual_archive_generation' ) );
		add_action( 'admin_enqueue_scripts', array( AdminPage::get_instance(), 'enqueue_admin_assets' ) );
		add_action( 'gatherpress_statistics_regenerate_cache', array( Cache::get_instance(), 'pregenerate_cache' ) );
		add_action( 'gatherpress_statistics_monthly_archive', array( Archive::get_instance(), 'archive_monthly_statistics' ) );
		add_action( 'transition_post_status', array( CacheInvalidation::get_instance(), 'clear_cache_on_status_change' ), 10, 3 );
		add_action( 'updated_post_meta', array( CacheInvalidation::get_instance(), 'clear_cache_on_meta_update' ), 10, 3 );
		add_action( 'added_post_meta', array( CacheInvalidation::get_instance(), 'clear_cache_on_meta_update' ), 10, 3 );
		add_action( 'deleted_post_meta', array( CacheInvalidation::get_instance(), 'clear_cache_on_meta_delete' ), 10, 3 );
		add_action( 'create_term', array( CacheInvalidation::get_instance(), 'clear_cache_on_term_change' ), 10, 3 );
		add_action( 'edit_term', array( CacheInvalidation::get_instance(), 'clear_cache_on_term_change' ), 10, 3 );
		add_action( 'delete_term', array( CacheInvalidation::get_instance(), 'clear_cache_on_term_change' ), 10, 3 );
		add_action( 'set_object_terms', array( CacheInvalidation::get_instance(), 'clear_cache_on_term_relationship' ), 10, 3 );
		add_filter( 'posts_where', array( QueryFilters::get_instance(), 'filter_gatherpress_event_dates' ), 10, 2 );
	}
}

// ============================================================================
// FILE: includes/core/class-setup.php
// ============================================================================

/**
 * Setup class for plugin initialization.
 *
 * @since 0.1.0
 */
class Setup {
	/**
	 * Class instance.
	 *
	 * @since 0.1.0
	 * @var Setup|null
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @since 0.1.0
	 *
	 * @return Setup
	 */
	public static function get_instance(): Setup {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {}

	/**
	 * Register post type support for gatherpress_statistics.
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
		
		$config = apply_filters( 'gatherpress_statistics_support_config', $default_config );
		
		add_post_type_support( 'gatherpress_event', 'gatherpress_statistics', $config );
	}

	/**
	 * Registers the block.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function block_init(): void {
		register_block_type( __DIR__ . '/build/' );
	}
}

// ============================================================================
// FILE: includes/core/class-database.php
// ============================================================================

/**
 * Database operations class.
 *
 * @since 0.1.0
 */
class Database {
	/**
	 * Class instance.
	 *
	 * @since 0.1.0
	 * @var Database|null
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @since 0.1.0
	 *
	 * @return Database
	 */
	public static function get_instance(): Database {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {}

	/**
	 * Create database table for archival statistics.
	 *
	 * @since 0.1.0
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 * @return void
	 */
	public function create_archive_table(): void {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'gatherpress_statistics_archive';
		$charset_collate = $wpdb->get_charset_collate();
		
		$sql = 
				"CREATE TABLE {$table_name} (
				id mediumint(10) UNSIGNED NOT NULL AUTO_INCREMENT,
				post_type varchar(20) NOT NULL DEFAULT 'gatherpress_event',
				statistic_type varchar(100) NOT NULL,
				statistic_year smallint UNSIGNED NOT NULL,
				statistic_month tinyint UNSIGNED NOT NULL,
				filters_hash char(32) NOT NULL,
				filters_data longtext NOT NULL,
				statistic_value bigint(20) NOT NULL DEFAULT 0,
				archived_at datetime NULL DEFAULT NULL,
				PRIMARY KEY  (id),
				KEY post_type (post_type),
				KEY statistic_lookup (statistic_type, statistic_year, statistic_month),
				KEY statistic_year_month (statistic_year, statistic_month),
				KEY filters_lookup (filters_hash)
				) {$charset_collate};";
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}

// ============================================================================
// FILE: includes/core/class-support.php
// ============================================================================

/**
 * Post type support management class.
 *
 * @since 0.1.0
 */
class Support {
	/**
	 * Class instance.
	 *
	 * @since 0.1.0
	 * @var Support|null
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @since 0.1.0
	 *
	 * @return Support
	 */
	public static function get_instance(): Support {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {}

	/**
	 * Get the statistics support configuration for a post type.
	 *
	 * @since 0.1.0
	 *
	 * @param string $post_type Post type slug.
	 * @return array<string, bool> Configuration array.
	 */
	public function get_support_config( string $post_type = 'gatherpress_event' ): array {
		if ( ! post_type_supports( $post_type, 'gatherpress_statistics' ) ) {
			return array();
		}
		
		$supports = get_all_post_type_supports( $post_type );
		
		if ( isset( $supports['gatherpress_statistics'] ) && is_array( $supports['gatherpress_statistics'] ) ) {
			return reset( $supports['gatherpress_statistics'] );
		}
		
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
	public function is_statistic_type_supported( string $statistic_type, string $post_type = 'gatherpress_event' ): bool {
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
	public function get_supported_statistic_types( string $post_type = 'gatherpress_event' ): array {
		$config = $this->get_support_config( $post_type );
		
		if ( empty( $config ) ) {
			return array();
		}
		
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
	 * @since 0.1.0
	 *
	 * @return array<int, string> Array of post type slugs.
	 */
	public function get_supported_post_types(): array {
		$post_types = get_post_types_by_support( 'gatherpress_statistics' );
		
		if ( empty( $post_types ) || ! is_array( $post_types ) ) {
			return array();
		}
		
		return $post_types;
	}

	/**
	 * Check if any post types support gatherpress_statistics.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if at least one post type supports statistics.
	 */
	public function has_supported_post_types(): bool {
		$post_types = $this->get_supported_post_types();
		return ! empty( $post_types );
	}

	/**
	 * Check if a specific post is supported for statistics.
	 *
	 * @since 0.1.0
	 *
	 * @param int $post_id Post ID to check.
	 * @return bool True if supported.
	 */
	public function is_supported_post( int $post_id ) : bool {
		$post = get_post( $post_id );
		
		return post_type_supports( $post->post_type, 'gatherpress_statistics' ) 
			&& $post->post_status === 'publish';
	}

	/**
	 * Get singular label for a post type.
	 *
	 * @since 0.1.0
	 *
	 * @param string $post_type Post type slug.
	 * @return string Singular label.
	 */
	public function get_post_type_singular_label( string $post_type ): string {
		$post_type_object = get_post_type_object( $post_type );
		
		if ( $post_type_object && isset( $post_type_object->labels->singular_name ) ) {
			return $post_type_object->labels->singular_name;
		}
		
		return __( 'Item', 'gatherpress-statistics' );
	}

	/**
	 * Get plural label for a post type.
	 *
	 * @since 0.1.0
	 *
	 * @param string $post_type Post type slug.
	 * @return string Plural label.
	 */
	public function get_post_type_plural_label( string $post_type ): string {
		$post_type_object = get_post_type_object( $post_type );
		
		if ( $post_type_object && isset( $post_type_object->labels->name ) ) {
			return $post_type_object->labels->name;
		}
		
		return __( 'Items', 'gatherpress-statistics' );
	}
}

// ============================================================================
// FILE: includes/core/class-taxonomy.php
// ============================================================================

/**
 * Taxonomy management class.
 *
 * @since 0.1.0
 */
class Taxonomy {
	/**
	 * Class instance.
	 *
	 * @since 0.1.0
	 * @var Taxonomy|null
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @since 0.1.0
	 *
	 * @return Taxonomy
	 */
	public static function get_instance(): Taxonomy {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {}

	/**
	 * Get all taxonomies registered for supported post types.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, \WP_Taxonomy> Array of taxonomy objects.
	 */
	public function get_taxonomies(): array {
		$post_types = Support::get_instance()->get_supported_post_types();
		
		if ( empty( $post_types ) ) {
			return array();
		}
		
		$all_taxonomies = array();
		
		foreach ( $post_types as $post_type ) {
			if ( ! post_type_exists( $post_type ) ) {
				continue;
			}
			
			$taxonomies = \get_object_taxonomies( $post_type, 'objects' );
			
			if ( ! empty( $taxonomies ) && is_array( $taxonomies ) ) {
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
	 * Get filtered taxonomies.
	 *
	 * @since 0.1.0
	 *
	 * @param bool $for_editor Optional. Whether this is for editor selection.
	 * @return array<int, \WP_Taxonomy> Array of taxonomy objects.
	 */
	public function get_filtered_taxonomies( bool $for_editor = false ): array {
		$taxonomies = $this->get_taxonomies();
		
		if ( empty( $taxonomies ) || ! is_array( $taxonomies ) ) {
			return array();
		}
		
		$excluded_taxonomies = apply_filters(
			'gatherpress_statistics_excluded_taxonomies',
			array( '_gatherpress_venue' ),
			$for_editor
		);
		
		if ( ! is_array( $excluded_taxonomies ) ) {
			$excluded_taxonomies = array();
		}
		
		$filtered_taxonomies = array();
		foreach ( $taxonomies as $taxonomy ) {
			if ( ! isset( $taxonomy->name ) ) {
				continue;
			}
			
			if ( in_array( $taxonomy->name, $excluded_taxonomies, true ) ) {
				continue;
			}
			
			$filtered_taxonomies[] = $taxonomy;
		}
		
		return $filtered_taxonomies;
	}
}

// ============================================================================
// FILE: includes/core/class-cache.php
// ============================================================================

/**
 * Cache management class.
 *
 * @since 0.1.0
 */
class Cache {
	/**
	 * Class instance.
	 *
	 * @since 0.1.0
	 * @var Cache|null
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @since 0.1.0
	 *
	 * @return Cache
	 */
	public static function get_instance(): Cache {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {}

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
		$filters = is_array( $filters ) ? $filters : array();
		
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

// ============================================================================
// FILE: includes/core/class-statistics.php
// ============================================================================

/**
 * Statistics calculation class.
 *
 * @since 0.1.0
 */
class Statistics {
	/**
	 * Class instance.
	 *
	 * @since 0.1.0
	 * @var Statistics|null
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @since 0.1.0
	 *
	 * @return Statistics
	 */
	public static function get_instance(): Statistics {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {}

	/**
	 * Calculate statistics based on type and filters.
	 *
	 * @since 0.1.0
	 *
	 * @param string               $statistic_type The type of statistic to calculate.
	 * @param array<string, mixed> $filters        Filters to apply.
	 * @return int Calculated statistic value.
	 */
	public function calculate( string $statistic_type, array $filters = array() ): int {
		if ( ! Support::get_instance()->has_supported_post_types() ) {
			return 0;
		}
		
		if ( ! Support::get_instance()->is_statistic_type_supported( $statistic_type ) ) {
			return 0;
		}

		$statistic_type = is_string( $statistic_type ) ? $statistic_type : 'total_events';
		$filters = is_array( $filters ) ? $filters : array();
		
		if ( empty( $filters['event_query'] ) || ! in_array( $filters['event_query'], array( 'upcoming', 'past' ), true ) ) {
			return 0;
		}
		
		$result = 0;
		
		switch ( $statistic_type ) {
			case 'total_events':
				$result = Query::get_instance()->count_events( $filters );
				break;
				
			case 'events_per_taxonomy':
				$result = Query::get_instance()->count_events( $filters );
				break;
				
			case 'events_multi_taxonomy':
				$result = Query::get_instance()->count_events( $filters );
				break;
				
			case 'total_taxonomy_terms':
				$result = Query::get_instance()->count_terms( $filters );
				break;
				
			case 'taxonomy_terms_by_taxonomy':
				$result = Query::get_instance()->terms_by_taxonomy( $filters );
				break;
				
			case 'total_attendees':
				$result = Query::get_instance()->count_attendees( $filters );
				break;
		}
		
		$result = is_numeric( $result ) ? absint( $result ) : 0;
		
		return apply_filters( 'gatherpress_stats_calculate_' . $statistic_type, $result, $filters );
	}
}

// ============================================================================
// FILE: includes/core/class-query.php
// ============================================================================

/**
 * Query class for database operations.
 *
 * @since 0.1.0
 */
class Query {
	/**
	 * Class instance.
	 *
	 * @since 0.1.0
	 * @var Query|null
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @since 0.1.0
	 *
	 * @return Query
	 */
	public static function get_instance(): Query {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {}

	/**
	 * Build date query arguments from filters.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $filters Query filters.
	 * @return array<string, mixed> Date query arguments.
	 */
	private function build_date_query( array $filters ): array {
		$date_query = array();
		
		if ( ! empty( $filters['year'] ) ) {
			$year = absint( $filters['year'] );
			if ( $year > 0 ) {
				$date_query['year'] = $year;
			}
		}
		
		if ( ! empty( $filters['month'] ) ) {
			$month = absint( $filters['month'] );
			if ( $month >= 1 && $month <= 12 ) {
				$date_query['month'] = $month;
			}
		}
		
		return $date_query;
	}

	/**
	 * Count events with filters.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $filters Query filters.
	 * @return int Number of events.
	 */
	public function count_events( array $filters = array() ): int {
		$post_types = Support::get_instance()->get_supported_post_types();
		
		if ( empty( $post_types ) ) {
			return 0;
		}
		
		$filters = is_array( $filters ) ? $filters : array();
		
		$args = array(
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
		
		$date_query = $this->build_date_query( $filters );
		if ( ! empty( $date_query ) ) {
			$args['date_query'] = array( $date_query );
		}
		
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
		else if ( ! empty( $filters['taxonomy_terms'] ) && is_array( $filters['taxonomy_terms'] ) ) {
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
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $filters Filters including taxonomy.
	 * @return int Number of terms.
	 */
	public function count_terms( array $filters = array() ): int {
		$post_types = Support::get_instance()->get_supported_post_types();
		
		if ( empty( $post_types ) ) {
			return 0;
		}
		
		$filters = is_array( $filters ) ? $filters : array();
		$taxonomy = isset( $filters['taxonomy'] ) && is_string( $filters['taxonomy'] ) ? $filters['taxonomy'] : '';
		
		if ( empty( $taxonomy ) || ! taxonomy_exists( $taxonomy ) ) {
			return 0;
		}
		
		$args = array(
			'taxonomy'   => sanitize_key( $taxonomy ),
			'hide_empty' => true,
			'object_ids' => null,
		);
		
		$query_args = array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);
		
		$date_query = $this->build_date_query( $filters );
		if ( ! empty( $date_query ) ) {
			$query_args['date_query'] = array( $date_query );
		}
		
		$post_query = new \WP_Query( $query_args );
		
		if ( ! empty( $post_query->posts ) ) {
			$args['object_ids'] = $post_query->posts;
		}
		
		$terms = \get_terms( $args );
		
		if ( is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return 0;
		}
		
		return absint( count( $terms ) );
	}

	/**
	 * Count terms of one taxonomy that have events in another taxonomy.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $filters Filters for cross-taxonomy counting.
	 * @return int Number of unique terms.
	 */
	public function terms_by_taxonomy( array $filters = array() ): int {
		$post_types = Support::get_instance()->get_supported_post_types();
		
		if ( empty( $post_types ) ) {
			return 0;
		}
		
		$filters = is_array( $filters ) ? $filters : array();
		
		$count_taxonomy  = isset( $filters['count_taxonomy'] ) && is_string( $filters['count_taxonomy'] ) ? $filters['count_taxonomy'] : '';
		$filter_taxonomy = isset( $filters['filter_taxonomy'] ) && is_string( $filters['filter_taxonomy'] ) ? $filters['filter_taxonomy'] : '';
		$term_id         = isset( $filters['term_id'] ) ? absint( $filters['term_id'] ) : 0;
		
		if ( empty( $count_taxonomy ) || empty( $filter_taxonomy ) || $term_id === 0 ) {
			return 0;
		}
		
		if ( ! taxonomy_exists( $count_taxonomy ) || ! taxonomy_exists( $filter_taxonomy ) ) {
			return 0;
		}
		
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
		
		$date_query = $this->build_date_query( $filters );
		if ( ! empty( $date_query ) ) {
			$args['date_query'] = array( $date_query );
		}
		
		$query = new \WP_Query( $args );
		$terms = array();
		
		if ( is_array( $query->posts ) ) {
			foreach ( $query->posts as $post_id ) {
				$post_terms = wp_get_post_terms( $post_id, sanitize_key( $count_taxonomy ), array( 'fields' => 'ids' ) );
				
				if ( ! is_wp_error( $post_terms ) && is_array( $post_terms ) && ! empty( $post_terms ) ) {
					$terms = array_merge( $terms, $post_terms );
				}
			}
		}
		
		return absint( count( array_unique( $terms ) ) );
	}

	/**
	 * Count total attendees with filters.
	 *
	 * @since 0.1.0
	 *
	 * @param array<string, mixed> $filters Query filters.
	 * @return int Total number of attendees.
	 */
	public function count_attendees( array $filters = array() ): int {
		$post_types = Support::get_instance()->get_supported_post_types();
		
		if ( empty( $post_types ) ) {
			return 0;
		}
		$filters = is_array( $filters ) ? $filters : array();
		
		$args = array(
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
		
		$date_query = $this->build_date_query( $filters );
		if ( ! empty( $date_query ) ) {
			$args['date_query'] = array( $date_query );
		}
		
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
		else if ( ! empty( $filters['taxonomy_terms'] ) && is_array( $filters['taxonomy_terms'] ) ) {
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
			
			if ( count( $tax_query ) > 1 ) {
				$args['tax_query'] = $tax_query;
			}
		}
		
		$query = new \WP_Query( $args );
		$total_attendees = 0;
		
		if ( is_array( $query->posts ) && ! empty( $query->posts ) ) {
			foreach ( $query->posts as $post_id ) {
				$attendee_count = (int) get_post_meta( $post_id, 'gatherpress_attendee_count', true );
				
				if ( is_numeric( $attendee_count ) ) {
					$total_attendees += absint( $attendee_count );
				}
			}
		}
		
		return absint( $total_attendees );
	}
}

// ============================================================================
// FILE: includes/core/class-query-filters.php
// ============================================================================

/**
 * Query filters class for modifying SQL queries.
 *
 * @since 0.1.0
 */
class QueryFilters {
	/**
	 * Class instance.
	 *
	 * @since 0.1.0
	 * @var QueryFilters|null
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @since 0.1.0
	 *
	 * @return QueryFilters
	 */
	public static function get_instance(): QueryFilters {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {}

	/**
	 * Filter SQL WHERE clause to use GatherPress event dates instead of post dates.
	 *
	 * @since 0.1.0
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 * @param string    $where The WHERE clause of the query.
	 * @param \WP_Query $query The WP_Query instance.
	 * @return string Modified WHERE clause.
	 */
	public function filter_gatherpress_event_dates( string $where, \WP_Query $query ): string {
		global $wpdb;

		if ( empty( $query->query_vars['date_query'] ) ) {
			return $where;
		}

		$post_type = $query->get( 'post_type' );
		if ( 'gatherpress_event' !== $post_type && ! in_array( 'gatherpress_event', (array) $post_type, true ) ) {
			return $where;
		}

		$date_query = $query->query_vars['date_query'];
		if ( ! is_array( $date_query ) || empty( $date_query ) ) {
			return $where;
		}

		$date_filter = is_array( $date_query[0] ) ? $date_query[0] : array();

		$date_conditions = array();

		if ( ! empty( $date_filter['year'] ) ) {
			$year = absint( $date_filter['year'] );
			$date_conditions[] = $wpdb->prepare( 'YEAR(ge.datetime_start_gmt) = %d', $year );
		}

		if ( ! empty( $date_filter['month'] ) ) {
			$month = absint( $date_filter['month'] );
			$date_conditions[] = $wpdb->prepare( 'MONTH(ge.datetime_start_gmt) = %d', $month );
		}

		if ( empty( $date_conditions ) ) {
			return $where;
		}

		$where = preg_replace(
			'/AND\s*\(\s*\(\s*YEAR\(\s*[^)]+\s*\)\s*=\s*\d+(?:\s+AND\s+MONTH\(\s*[^)]+\s*\)\s*=\s*\d+)?\s*\)\s*\)/',
			'',
			$where
		);

		$events_table = $wpdb->prefix . 'gatherpress_events';
		$date_where = implode( ' AND ', $date_conditions );

		$where .= " AND {$wpdb->posts}.ID IN (
			SELECT ge.post_id 
			FROM {$events_table} ge 
			WHERE {$date_where}
		)";

		return $where;
	}
}

// ============================================================================
// FILE: includes/core/class-cache-invalidation.php
// ============================================================================

/**
 * Cache invalidation class.
 *
 * @since 0.1.0
 */
class CacheInvalidation {
	/**
	 * Class instance.
	 *
	 * @since 0.1.0
	 * @var CacheInvalidation|null
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @since 0.1.0
	 *
	 * @return CacheInvalidation
	 */
	public static function get_instance(): CacheInvalidation {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {}

	/**
	 * Clear cache when event post status changes.
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
		
		if ( 'publish' === $new_status || 'publish' === $old_status ) {
			if ( $new_status !== $old_status ) {
				Cache::get_instance()->clear_cache();
			}
		}
	}

	/**
	 * Clear cache when attendee count post meta is updated.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $meta_id  ID of updated metadata entry.
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Meta key that was updated.
	 * @return void
	 */
	public function clear_cache_on_meta_update( int $meta_id, int $post_id, string $meta_key ): void {
		if ( 'gatherpress_attendee_count' === $meta_key && Support::get_instance()->is_supported_post( $post_id ) ) {
			Cache::get_instance()->clear_cache();
		}
	}

	/**
	 * Clear cache when attendee count post meta is deleted.
	 *
	 * @since 0.1.0
	 *
	 * @param array<int>|int $meta_ids Meta ID or array of meta IDs.
	 * @param int            $post_id  Post ID.
	 * @param string         $meta_key Meta key.
	 * @return void
	 */
	public function clear_cache_on_meta_delete( $meta_ids, int $post_id, string $meta_key ): void {
		if ( 'gatherpress_attendee_count' === $meta_key && Support::get_instance()->is_supported_post( $post_id ) ) {
			Cache::get_instance()->clear_cache();
		}
	}

	/**
	 * Clear cache when taxonomy terms are modified.
	 *
	 * @since 0.1.0
	 *
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy slug.
	 * @return void
	 */
	public function clear_cache_on_term_change( int $term_id, int $tt_id, string $taxonomy ): void {
		if ( ! $this->should_clear_cache_for_term_changes() ) {
			return;
		}
		
		$supported_taxonomies = Taxonomy::get_instance()->get_filtered_taxonomies();

		if ( empty( $supported_taxonomies ) || ! is_array( $supported_taxonomies ) ) {
			return;
		}

		$taxonomy_slugs = array();
		foreach ( $supported_taxonomies as $tax_obj ) {
			if ( isset( $tax_obj->name ) ) {
				$taxonomy_slugs[] = $tax_obj->name;
			}
		}

		if ( in_array( $taxonomy, $taxonomy_slugs, true ) ) {
			Cache::get_instance()->clear_cache();
		}
	}

	/**
	 * Clear cache when term relationships change.
	 *
	 * @since 0.1.0
	 *
	 * @param int                 $object_id Object ID.
	 * @param array<int, int>     $terms     Term IDs.
	 * @param array<int, int>     $tt_ids    Term taxonomy IDs.
	 * @return void
	 */
	public function clear_cache_on_term_relationship( int $object_id, array $terms, array $tt_ids ): void {
		if ( Support::get_instance()->is_supported_post( $object_id ) ) {
			Cache::get_instance()->clear_cache();
		}
	}

	/**
	 * Check if term changes require cache clearing.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if cache should be cleared.
	 */
	private function should_clear_cache_for_term_changes(): bool {
		$supported_types = Support::get_instance()->get_supported_statistic_types();
		
		if ( empty( $supported_types ) ) {
			return false;
		}
		
		$term_dependent_types = array(
			'events_per_taxonomy',
			'events_multi_taxonomy',
			'total_taxonomy_terms',
			'taxonomy_terms_by_taxonomy',
		);
		
		foreach ( $term_dependent_types as $type ) {
			if ( in_array( $type, $supported_types, true ) ) {
				return true;
			}
		}
		
		return false;
	}
}

// ============================================================================
// FILE: includes/admin/class-rest-api.php
// ============================================================================

/**
 * REST API endpoints class.
 *
 * @since 0.1.0
 */
class RestApi {
	/**
	 * Class instance.
	 *
	 * @since 0.1.0
	 * @var RestApi|null
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @since 0.1.0
	 *
	 * @return RestApi
	 */
	public static function get_instance(): RestApi {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {}

	/**
	 * Register REST API routes.
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
	 * REST API endpoint to get filtered taxonomies.
	 *
	 * @since 0.1.0
	 *
	 * @return \WP_REST_Response List of taxonomies.
	 */
	public function get_taxonomies_endpoint(): \WP_REST_Response {
		$taxonomies = Taxonomy::get_instance()->get_filtered_taxonomies( true );
		
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
	 * @return \WP_REST_Response List of supported types.
	 */
	public function get_supported_types_endpoint(): \WP_REST_Response {
		$supported_types = Support::get_instance()->get_supported_statistic_types();
		
		return new \WP_REST_Response( $supported_types, 200 );
	}
}

// ============================================================================
// FILE: includes/admin/class-admin-page.php
// ============================================================================

/**
 * Admin page class.
 *
 * @since 0.1.0
 */
class AdminPage {
	/**
	 * Class instance.
	 *
	 * @since 0.1.0
	 * @var AdminPage|null
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @since 0.1.0
	 *
	 * @return AdminPage
	 */
	public static function get_instance(): AdminPage {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {}

	/**
	 * Enqueue admin assets.
	 *
	 * @since 0.1.0
	 *
	 * @param string $hook Current admin page hook.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook ): void {
		if ( 'dashboard_page_gatherpress-statistics-archive' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'chartjs',
			'https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js',
			array(),
			'4.4.1',
			true
		);
	}

	/**
	 * Register admin menu page.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_admin_page(): void {
		add_dashboard_page(
			__( 'Statistics Archive', 'gatherpress-statistics' ),
			__( 'Statistics Archive', 'gatherpress-statistics' ),
			'manage_options',
			'gatherpress-statistics-archive',
			array( $this, 'render_admin_page' )
		);
	}

	/**
	 * Handle manual archive generation form submission.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function handle_manual_archive_generation(): void {
		if ( ! isset( $_POST['gatherpress_generate_archive'] ) ) {
			return;
		}

		if ( ! isset( $_POST['gatherpress_archive_nonce'] ) || 
		     ! wp_verify_nonce( $_POST['gatherpress_archive_nonce'], 'gatherpress_generate_archive' ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$year = isset( $_POST['archive_year'] ) ? absint( $_POST['archive_year'] ) : 0;
		$month = isset( $_POST['archive_month'] ) ? absint( $_POST['archive_month'] ) : 0;

		if ( ! $year || ! $month || $month < 1 || $month > 12 ) {
			add_settings_error(
				'gatherpress_statistics',
				'invalid_date',
				__( 'Invalid year or month selected.', 'gatherpress-statistics' ),
				'error'
			);
			return;
		}

		$result = Archive::get_instance()->archive_statistics_for_month( $year, $month );

		if ( $result ) {
			add_settings_error(
				'gatherpress_statistics',
				'archive_generated',
				__( 'Archive statistics generated successfully.', 'gatherpress-statistics' ),
				'success'
			);
		} else {
			add_settings_error(
				'gatherpress_statistics',
				'archive_failed',
				__( 'Failed to generate archive statistics.', 'gatherpress-statistics' ),
				'error'
			);
		}
	}

	/**
	 * Get human-readable label for statistic type.
	 *
	 * @since 0.1.0
	 *
	 * @param string $type Statistic type slug.
	 * @return string Human-readable label.
	 */
	private function get_statistic_type_label( string $type ): string {
		$post_types = Support::get_instance()->get_supported_post_types();
		$post_type = ! empty( $post_types ) ? $post_types[0] : 'gatherpress_event';
		$plural_label = Support::get_instance()->get_post_type_plural_label( $post_type );

		$labels = array(
			'total_events'                => sprintf( __( 'Total %s', 'gatherpress-statistics' ), $plural_label ),
			'events_per_taxonomy'         => sprintf( __( '%s per Taxonomy', 'gatherpress-statistics' ), $plural_label ),
			'events_multi_taxonomy'       => sprintf( __( '%s (Multiple Taxonomies)', 'gatherpress-statistics' ), $plural_label ),
			'total_taxonomy_terms'        => __( 'Total Taxonomy Terms', 'gatherpress-statistics' ),
			'taxonomy_terms_by_taxonomy'  => __( 'Taxonomy Terms by Taxonomy', 'gatherpress-statistics' ),
			'total_attendees'             => __( 'Total Attendees', 'gatherpress-statistics' ),
		);

		return isset( $labels[ $type ] ) ? $labels[ $type ] : ucwords( str_replace( '_', ' ', $type ) );
	}

	/**
	 * Prepare chart data from statistics.
	 *
	 * @since 0.1.0
	 *
	 * @param array  $statistics Array of statistics from database.
	 * @param string $type       Current statistic type.
	 * @return array Chart data structure.
	 */
	private function prepare_chart_data( array $statistics, string $type ): array {
		if ( empty( $statistics ) ) {
			return array();
		}

		$data_by_term = array();
		$all_dates = array();

		foreach ( $statistics as $stat ) {
			$filters = json_decode( $stat->filters_data, true );
			$date_key = sprintf( '%d-%02d', $stat->statistic_year, $stat->statistic_month );
			$all_dates[ $date_key ] = true;

			$term_label = 'All';
			$term_id = 0;

			if ( isset( $filters['term_id'] ) && $filters['term_id'] > 0 ) {
				$term = get_term( $filters['term_id'] );
				if ( $term && ! is_wp_error( $term ) ) {
					$term_label = $term->name;
					$term_id = $term->term_id;
				}
			}

			if ( ! isset( $data_by_term[ $term_id ] ) ) {
				$data_by_term[ $term_id ] = array(
					'label' => $term_label,
					'data' => array(),
				);
			}

			$data_by_term[ $term_id ]['data'][ $date_key ] = $stat->statistic_value;
		}

		$dates = array_keys( $all_dates );
		sort( $dates );

		$datasets = array();
		$colors = array(
			'#3366CC', '#DC3912', '#FF9900', '#109618', '#990099',
			'#3B3EAC', '#0099C6', '#DD4477', '#66AA00', '#B82E2E',
		);
		$color_index = 0;

		foreach ( $data_by_term as $term_id => $term_data ) {
			$values = array();
			foreach ( $dates as $date ) {
				$values[] = isset( $term_data['data'][ $date ] ) ? $term_data['data'][ $date ] : 0;
			}

			$color = $colors[ $color_index % count( $colors ) ];
			$color_index++;

			$datasets[] = array(
				'label' => $term_data['label'],
				'data' => $values,
				'termId' => $term_id,
				'borderColor' => $color,
				'backgroundColor' => $color . '33',
				'tension' => 0.4,
			);
		}

		$labels = array_map( function( $date ) {
			list( $year, $month ) = explode( '-', $date );
			return date_i18n( 'M Y', mktime( 0, 0, 0, (int) $month, 1, (int) $year ) );
		}, $dates );

		return array(
			'labels' => $labels,
			'datasets' => $datasets,
		);
	}

	/**
	 * Render admin page.
	 *
	 * @since 0.1.0
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 * @return void
	 */
	public function render_admin_page(): void {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'gatherpress_statistics_archive';
		
		$selected_year = isset( $_GET['year'] ) ? absint( $_GET['year'] ) : null;
		$selected_month = isset( $_GET['month'] ) ? absint( $_GET['month'] ) : null;
		$selected_taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_text_field( $_GET['taxonomy'] ) : null;
		$selected_term = isset( $_GET['term_id'] ) ? absint( $_GET['term_id'] ) : null;
		$current_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : '';
		$order_by = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'statistic_year';
		$order = isset( $_GET['order'] ) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';
		
		$supported_types = Support::get_instance()->get_supported_statistic_types();
		
		if ( empty( $current_tab ) && ! empty( $supported_types ) ) {
			$current_tab = $supported_types[0];
		}
		
		$years = $wpdb->get_col( "SELECT DISTINCT statistic_year FROM {$table_name} ORDER BY statistic_year DESC" );
		
		$taxonomies = array();
		$all_filters = $wpdb->get_col( "SELECT DISTINCT filters_data FROM {$table_name}" );
		foreach ( $all_filters as $filters_json ) {
			$filters = json_decode( $filters_json, true );
			if ( isset( $filters['taxonomy'] ) && ! in_array( $filters['taxonomy'], $taxonomies, true ) ) {
				$taxonomies[] = $filters['taxonomy'];
			}
		}
		sort( $taxonomies );
		
		$where_clauses = array();
		$query_params = array();
		
		if ( ! empty( $current_tab ) ) {
			$where_clauses[] = 'statistic_type = %s';
			$query_params[] = $current_tab;
		}
		
		if ( $selected_year ) {
			$where_clauses[] = 'statistic_year = %d';
			$query_params[] = $selected_year;
		}
		
		if ( $selected_month ) {
			$where_clauses[] = 'statistic_month = %d';
			$query_params[] = $selected_month;
		}
		
		if ( $selected_taxonomy || $selected_term ) {
			$json_conditions = array();
			if ( $selected_taxonomy ) {
				$json_conditions[] = "filters_data LIKE '%\"taxonomy\":\"" . $wpdb->esc_like( $selected_taxonomy ) . "\"%'";
			}
			if ( $selected_term ) {
				$json_conditions[] = "filters_data LIKE '%\"term_id\":{$selected_term}%'";
			}
			if ( ! empty( $json_conditions ) ) {
				$where_clauses[] = '(' . implode( ' AND ', $json_conditions ) . ')';
			}
		}
		
		$where_sql = ! empty( $where_clauses ) ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';
		
		$allowed_order_by = array( 'statistic_year', 'statistic_month', 'statistic_value', 'archived_at', 'taxonomy', 'term' );
		if ( ! in_array( $order_by, $allowed_order_by, true ) ) {
			$order_by = 'statistic_year';
		}
		
		$order_clause = "ORDER BY {$order_by} {$order}, statistic_month {$order}";
		
		$query = "SELECT * FROM {$table_name} {$where_sql} {$order_clause}";
		
		if ( ! empty( $query_params ) ) {
			$query = $wpdb->prepare( $query, $query_params );
		}
		
		$statistics = $wpdb->get_results( $query );
		
		$current_year = (int) date( 'Y' );
		$current_month = (int) date( 'n' );
		
		$base_url = add_query_arg( array(
			'page' => 'gatherpress-statistics-archive',
			'tab' => $current_tab,
			'year' => $selected_year,
			'month' => $selected_month,
			'taxonomy' => $selected_taxonomy,
			'term_id' => $selected_term,
		), admin_url( 'index.php' ) );
		
		$next_order = ( $order === 'ASC' ) ? 'desc' : 'asc';
		
		$chart_data = $this->prepare_chart_data( $statistics, $current_tab );
		
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<?php settings_errors( 'gatherpress_statistics' ); ?>
			
			<div class="card">
				<h2><?php esc_html_e( 'Generate Archive Statistics', 'gatherpress-statistics' ); ?></h2>
				<p><?php esc_html_e( 'Manually generate archive statistics for a specific month. This will calculate all configured statistics and store them in the archive.', 'gatherpress-statistics' ); ?></p>
				
				<form method="post" action="">
					<?php wp_nonce_field( 'gatherpress_generate_archive', 'gatherpress_archive_nonce' ); ?>
					
					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="archive_year"><?php esc_html_e( 'Year', 'gatherpress-statistics' ); ?></label>
							</th>
							<td>
								<select name="archive_year" id="archive_year" required>
									<?php for ( $y = $current_year; $y >= 2020; $y-- ) : ?>
										<option value="<?php echo esc_attr( $y ); ?>"><?php echo esc_html( $y ); ?></option>
									<?php endfor; ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="archive_month"><?php esc_html_e( 'Month', 'gatherpress-statistics' ); ?></label>
							</th>
							<td>
								<select name="archive_month" id="archive_month" required>
									<?php for ( $m = 1; $m <= 12; $m++ ) : ?>
										<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $m, $current_month ); ?>>
											<?php echo esc_html( date_i18n( 'F', mktime( 0, 0, 0, $m, 1 ) ) ); ?>
										</option>
									<?php endfor; ?>
								</select>
							</td>
						</tr>
					</table>
					
					<p class="submit">
						<input type="submit" name="gatherpress_generate_archive" class="button button-primary" value="<?php esc_attr_e( 'Generate Archive', 'gatherpress-statistics' ); ?>" />
					</p>
				</form>
			</div>
			
			<hr />
			
			<h2><?php esc_html_e( 'Archived Statistics', 'gatherpress-statistics' ); ?></h2>
			
			<h2 class="nav-tab-wrapper">
				<?php foreach ( $supported_types as $type ) : ?>
					<a href="<?php echo esc_url( add_query_arg( 'tab', $type, remove_query_arg( array( 'orderby', 'order' ), $base_url ) ) ); ?>" 
					   class="nav-tab <?php echo $type === $current_tab ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $this->get_statistic_type_label( $type ) ); ?>
					</a>
				<?php endforeach; ?>
			</h2>
			
			<?php if ( ! empty( $chart_data ) ) : ?>
				<div class="gatherpress-stats-chart-container">
					<div class="gatherpress-stats-chart-controls">
						<h3><?php esc_html_e( 'Visual Comparison', 'gatherpress-statistics' ); ?></h3>
						<div id="gatherpress-term-toggles"></div>
					</div>
					<canvas id="gatherpress-stats-chart" width="400" height="150"></canvas>
				</div>
				<style>
					.gatherpress-stats-chart-container {
						margin: 20px 0;
						padding: 20px;
						background: #fff;
						border: 1px solid #ccd0d4;
						box-shadow: 0 1px 1px rgba(0,0,0,.04);
					}
					.gatherpress-stats-chart-controls {
						margin-bottom: 20px;
					}
					.gatherpress-stats-chart-controls h3 {
						margin: 0 0 10px 0;
						font-size: 14px;
						font-weight: 600;
					}
					#gatherpress-term-toggles {
						display: flex;
						flex-wrap: wrap;
						gap: 10px;
					}
					.term-toggle {
						display: inline-flex;
						align-items: center;
						gap: 5px;
						padding: 5px 10px;
						border: 1px solid #ddd;
						border-radius: 3px;
						cursor: pointer;
						background: #f7f7f7;
						transition: all 0.2s;
					}
					.term-toggle:hover {
						background: #e9e9e9;
					}
					.term-toggle.active {
						background: #fff;
						border-color: #0073aa;
					}
					.term-color-box {
						width: 16px;
						height: 16px;
						border-radius: 2px;
					}
					.sortable-column a {
						text-decoration: none;
					}
					.sortable-column .dashicons {
						width: 14px;
						height: 14px;
						font-size: 14px;
					}
				</style>
				<script type="text/javascript">
					var gatherpressChartData = <?php echo wp_json_encode( $chart_data ); ?>;
					
					jQuery(document).ready(function($) {
						if (typeof Chart === 'undefined' || !gatherpressChartData) {
							return;
						}

						var ctx = document.getElementById('gatherpress-stats-chart');
						if (!ctx) return;

						var chartConfig = {
							type: 'line',
							data: {
								labels: gatherpressChartData.labels,
								datasets: gatherpressChartData.datasets
							},
							options: {
								responsive: true,
								maintainAspectRatio: true,
								plugins: {
									legend: {
										display: false
									},
									tooltip: {
										mode: 'index',
										intersect: false
									}
								},
								scales: {
									y: {
										beginAtZero: true,
										ticks: {
											precision: 0,
											stepSize: 1
										}
									}
								}
							}
						};

						var chart = new Chart(ctx, chartConfig);

						var togglesContainer = document.getElementById('gatherpress-term-toggles');
						if (!togglesContainer) return;

						gatherpressChartData.datasets.forEach(function(dataset, index) {
							var toggle = document.createElement('div');
							toggle.className = 'term-toggle active';
							toggle.setAttribute('data-index', index);

							var colorBox = document.createElement('div');
							colorBox.className = 'term-color-box';
							colorBox.style.backgroundColor = dataset.borderColor;

							var label = document.createElement('span');
							label.textContent = dataset.label;

							toggle.appendChild(colorBox);
							toggle.appendChild(label);
							togglesContainer.appendChild(toggle);

							toggle.addEventListener('click', function() {
								var meta = chart.getDatasetMeta(index);
								meta.hidden = !meta.hidden;
								toggle.classList.toggle('active');
								chart.update();
							});
						});
					});
				</script>
			<?php endif; ?>
			
			<div class="tablenav top">
				<form method="get">
					<input type="hidden" name="page" value="gatherpress-statistics-archive" />
					<input type="hidden" name="tab" value="<?php echo esc_attr( $current_tab ); ?>" />
					
					<select name="year">
						<option value=""><?php esc_html_e( 'All Years', 'gatherpress-statistics' ); ?></option>
						<?php foreach ( $years as $year ) : ?>
							<option value="<?php echo esc_attr( $year ); ?>" <?php selected( $selected_year, $year ); ?>>
								<?php echo esc_html( $year ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					
					<select name="month">
						<option value=""><?php esc_html_e( 'All Months', 'gatherpress-statistics' ); ?></option>
						<?php for ( $m = 1; $m <= 12; $m++ ) : ?>
							<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $selected_month, $m ); ?>>
								<?php echo esc_html( date_i18n( 'F', mktime( 0, 0, 0, $m, 1 ) ) ); ?>
							</option>
						<?php endfor; ?>
					</select>
					
					<select name="taxonomy">
						<option value=""><?php esc_html_e( 'All Taxonomies', 'gatherpress-statistics' ); ?></option>
						<?php foreach ( $taxonomies as $tax_slug ) : 
							$tax_obj = get_taxonomy( $tax_slug );
							if ( $tax_obj ) :
						?>
							<option value="<?php echo esc_attr( $tax_slug ); ?>" <?php selected( $selected_taxonomy, $tax_slug ); ?>>
								<?php echo esc_html( $tax_obj->labels->name ); ?>
							</option>
							<?php endif; ?>
						<?php endforeach; ?>
					</select>
					
					<?php if ( $selected_taxonomy ) : 
						$terms = get_terms( array(
							'taxonomy' => $selected_taxonomy,
							'hide_empty' => false,
						) );
						if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) :
					?>
						<select name="term_id">
							<option value=""><?php esc_html_e( 'All Terms', 'gatherpress-statistics' ); ?></option>
							<?php foreach ( $terms as $term ) : ?>
								<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $selected_term, $term->term_id ); ?>>
									<?php echo esc_html( $term->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<?php endif; ?>
					<?php endif; ?>
					
					<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'gatherpress-statistics' ); ?>" />
				</form>
			</div>
			
			<?php if ( empty( $statistics ) ) : ?>
				<p><?php esc_html_e( 'No archived statistics found.', 'gatherpress-statistics' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>
								<a href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'statistic_year', 'order' => $next_order ), $base_url ) ); ?>">
									<?php esc_html_e( 'Year', 'gatherpress-statistics' ); ?>
									<?php if ( $order_by === 'statistic_year' ) : ?>
										<span class="dashicons dashicons-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></span>
									<?php endif; ?>
								</a>
							</th>
							<th>
								<a href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'statistic_month', 'order' => $next_order ), $base_url ) ); ?>">
									<?php esc_html_e( 'Month', 'gatherpress-statistics' ); ?>
									<?php if ( $order_by === 'statistic_month' ) : ?>
										<span class="dashicons dashicons-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></span>
									<?php endif; ?>
								</a>
							</th>
							<th class="sortable-column">
								<a href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'taxonomy', 'order' => $next_order ), $base_url ) ); ?>">
									<?php esc_html_e( 'Taxonomy', 'gatherpress-statistics' ); ?>
									<?php if ( $order_by === 'taxonomy' ) : ?>
										<span class="dashicons dashicons-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></span>
									<?php endif; ?>
								</a>
							</th>
							<th class="sortable-column">
								<a href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'term', 'order' => $next_order ), $base_url ) ); ?>">
									<?php esc_html_e( 'Term', 'gatherpress-statistics' ); ?>
									<?php if ( $order_by === 'term' ) : ?>
										<span class="dashicons dashicons-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></span>
									<?php endif; ?>
								</a>
							</th>
							<th>
								<a href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'statistic_value', 'order' => $next_order ), $base_url ) ); ?>">
									<?php esc_html_e( 'Value', 'gatherpress-statistics' ); ?>
									<?php if ( $order_by === 'statistic_value' ) : ?>
										<span class="dashicons dashicons-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></span>
									<?php endif; ?>
								</a>
							</th>
							<th>
								<a href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'archived_at', 'order' => $next_order ), $base_url ) ); ?>">
									<?php esc_html_e( 'Archived', 'gatherpress-statistics' ); ?>
									<?php if ( $order_by === 'archived_at' ) : ?>
										<span class="dashicons dashicons-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></span>
									<?php endif; ?>
								</a>
							</th>
						</tr>
					</thead>
					<tbody>
						<?php 
						if ( in_array( $order_by, array( 'taxonomy', 'term' ), true ) ) {
							usort( $statistics, function( $a, $b ) use ( $order_by, $order ) {
								$filters_a = json_decode( $a->filters_data, true );
								$filters_b = json_decode( $b->filters_data, true );
								
								if ( 'taxonomy' === $order_by ) {
									$val_a = isset( $filters_a['taxonomy'] ) ? $filters_a['taxonomy'] : '';
									$val_b = isset( $filters_b['taxonomy'] ) ? $filters_b['taxonomy'] : '';
								} else {
									$val_a = '';
									$val_b = '';
									
									if ( isset( $filters_a['term_id'] ) ) {
										$term = get_term( $filters_a['term_id'] );
										if ( $term && ! is_wp_error( $term ) ) {
											$val_a = $term->name;
										}
									}
									
									if ( isset( $filters_b['term_id'] ) ) {
										$term = get_term( $filters_b['term_id'] );
										if ( $term && ! is_wp_error( $term ) ) {
											$val_b = $term->name;
										}
									}
								}
								
								$result = strcasecmp( $val_a, $val_b );
								return ( 'ASC' === $order ) ? $result : -$result;
							} );
						}
						
						foreach ( $statistics as $stat ) : 
							$filters = json_decode( $stat->filters_data, true );
							$taxonomy_name = '';
							$term_name = '';
							
							if ( isset( $filters['taxonomy'] ) ) {
								$tax_obj = get_taxonomy( $filters['taxonomy'] );
								if ( $tax_obj ) {
									$taxonomy_name = $tax_obj->labels->singular_name;
								}
							}
							
							if ( isset( $filters['term_id'] ) ) {
								$term = get_term( $filters['term_id'] );
								if ( $term && ! is_wp_error( $term ) ) {
									$term_name = $term->name;
								}
							}
						?>
							<tr>
								<td><?php echo esc_html( $stat->statistic_year ); ?></td>
								<td><?php echo esc_html( date_i18n( 'F', mktime( 0, 0, 0, $stat->statistic_month, 1 ) ) ); ?></td>
								<td><?php echo esc_html( $taxonomy_name ); ?></td>
								<td><?php echo esc_html( $term_name ); ?></td>
								<td><strong><?php echo esc_html( number_format_i18n( $stat->statistic_value ) ); ?></strong></td>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $stat->archived_at ) ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}
}

// ============================================================================
// FILE: includes/admin/class-archive.php
// ============================================================================

/**
 * Archive management class.
 *
 * @since 0.1.0
 */
class Archive {
	/**
	 * Class instance.
	 *
	 * @since 0.1.0
	 * @var Archive|null
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @since 0.1.0
	 *
	 * @return Archive
	 */
	public static function get_instance(): Archive {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Constructor.
	 *
	 * @since 0.1.0
	 */
	private function __construct() {}

	/**
	 * Archive monthly statistics.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function archive_monthly_statistics(): void {
		$current_year = (int) date( 'Y' );
		$current_month = (int) date( 'n' );
		
		$this->archive_statistics_for_month( $current_year, $current_month );
	}

	/**
	 * Generate archive statistics for a specific month.
	 *
	 * @since 0.1.0
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 * @param int $year  Year to generate statistics for.
	 * @param int $month Month to generate statistics for.
	 * @return bool True on success, false on failure.
	 */
	public function archive_statistics_for_month( int $year, int $month ): bool {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'gatherpress_statistics_archive';
		$current_time = current_time( 'mysql' );
		
		$configs = Cache::get_instance()->get_common_configs();
		
		if ( empty( $configs ) ) {
			return false;
		}

		$success_count = 0;
		$post_types = Support::get_instance()->get_supported_post_types();
		$post_type = ! empty( $post_types ) ? $post_types[0] : 'gatherpress_event';
		
		foreach ( $configs as $config ) {
			if ( ! isset( $config['type'] ) || ! isset( $config['filters'] ) ) {
				continue;
			}
			
			$config['filters']['event_query'] = 'past';
			
			$filters_with_date = array_merge( $config['filters'], array(
				'year'  => $year,
				'month' => $month,
			) );
			
			$value = Statistics::get_instance()->calculate( $config['type'], $filters_with_date );
			$filters_hash = md5( wp_json_encode( $config['filters'] ) );
			
			$exists = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$table_name} 
					 WHERE post_type = %s
					 AND statistic_type = %s 
					 AND statistic_year = %d 
					 AND statistic_month = %d 
					 AND filters_hash = %s",
					$post_type,
					$config['type'],
					$year,
					$month,
					$filters_hash
				)
			);

			if ( $exists ) {
				$result = $wpdb->update(
					$table_name,
					array(
						'statistic_value' => $value,
						'archived_at'     => $current_time,
					),
					array( 'id' => $exists ),
					array( '%d', '%s' ),
					array( '%d' )
				);
			} else {
				$result = $wpdb->insert(
					$table_name,
					array(
						'post_type'       => $post_type,
						'statistic_type'  => $config['type'],
						'statistic_year'  => $year,
						'statistic_month' => $month,
						'filters_hash'    => $filters_hash,
						'filters_data'    => wp_json_encode( $config['filters'] ),
						'statistic_value' => $value,
						'archived_at'     => $current_time,
					),
					array( '%s', '%s', '%d', '%d', '%s', '%s', '%d', '%s' )
				);
			}

			if ( false !== $result ) {
				$success_count++;
			}
		}

		return $success_count > 0;
	}
}

// ============================================================================
// FILE: includes/functions.php
// ============================================================================

/**
 * Plugin activation hook.
 *
 * @since 0.1.0
 *
 * @return void
 */
function activate_plugin(): void {
	Database::get_instance()->create_archive_table();
	
	if ( ! wp_next_scheduled( 'gatherpress_statistics_regenerate_cache' ) ) {
		wp_schedule_single_event(
			time() + 5,
			'gatherpress_statistics_regenerate_cache'
		);
	}
	
	if ( ! wp_next_scheduled( 'gatherpress_statistics_monthly_archive' ) ) {
		wp_schedule_event(
			strtotime( 'first day of next month midnight' ),
			'monthly',
			'gatherpress_statistics_monthly_archive'
		);
	}
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\activate_plugin' );

/**
 * Plugin deactivation hook.
 *
 * @since 0.1.0
 *
 * @global \wpdb $wpdb WordPress database object.
 * @return void
 */
function deactivate_plugin(): void {
	global $wpdb;
	
	$wpdb->query(
		"DELETE FROM {$wpdb->options} 
		WHERE option_name LIKE '_transient_gatherpress_stats_%' 
		OR option_name LIKE '_transient_timeout_gatherpress_stats_%'"
	);
	
	$scheduled = wp_next_scheduled( 'gatherpress_statistics_regenerate_cache' );
	if ( $scheduled ) {
		wp_unschedule_event( $scheduled, 'gatherpress_statistics_regenerate_cache' );
	}
	
	$scheduled_monthly = wp_next_scheduled( 'gatherpress_statistics_monthly_archive' );
	if ( $scheduled_monthly ) {
		wp_unschedule_event( $scheduled_monthly, 'gatherpress_statistics_monthly_archive' );
	}
}
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\deactivate_plugin' );

/**
 * Get statistic with caching - convenience wrapper.
 *
 * @since 0.1.0
 *
 * @param string               $statistic_type Statistic type to retrieve.
 * @param array<string, mixed> $filters        Filters to apply.
 * @return int Statistic value.
 */
function get_cached( string $statistic_type, array $filters = array() ): int {
	return Cache::get_instance()->get_cached( $statistic_type, $filters );
}

/**
 * Clear cache - convenience wrapper.
 *
 * @since 0.1.0
 *
 * @return void
 */
function clear_cache(): void {
	Cache::get_instance()->clear_cache();
}

/**
 * Pre-generate cache - convenience wrapper.
 *
 * @since 0.1.0
 *
 * @return void
 */
function pregenerate_cache(): void {
	Cache::get_instance()->pregenerate_cache();
}

// Initialize the plugin
Plugin::get_instance();