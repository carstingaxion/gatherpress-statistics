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
		add_action( 'registered_post_type_gatherpress_event', array( $this, 'register_post_type_support' ) );
		add_action( 'init', array( $this, 'block_init' ) );
		add_action( 'init', array( $this, 'create_archive_table' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );
		add_action( 'gatherpress_statistics_regenerate_cache', array( $this, 'pregenerate_cache' ) );
		add_action( 'gatherpress_statistics_monthly_archive', array( $this, 'archive_monthly_statistics' ) );
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
		
		$sql = "CREATE TABLE IF NOT EXISTS {$table_name} (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			statistic_type varchar(100) NOT NULL,
			statistic_year year(4) NOT NULL,
			statistic_month tinyint(2) NOT NULL,
			filters_hash varchar(32) NOT NULL,
			filters_data longtext NOT NULL,
			statistic_value bigint(20) NOT NULL DEFAULT 0,
			archived_at datetime NOT NULL,
			PRIMARY KEY  (id),
			KEY statistic_lookup (statistic_type, statistic_year, statistic_month),
			KEY filters_lookup (filters_hash)
		) {$charset_collate};";
		
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
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

	/**
	 * Register admin menu page.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function register_admin_page(): void {
		add_dashboard_page(
			__( 'GatherPress Statistics Archive', 'gatherpress-statistics' ),
			__( 'Statistics Archive', 'gatherpress-statistics' ),
			'manage_options',
			'gatherpress-statistics-archive',
			array( $this, 'render_admin_page' )
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
		
		// Get filter parameters
		$selected_year = isset( $_GET['year'] ) ? absint( $_GET['year'] ) : null;
		$selected_month = isset( $_GET['month'] ) ? absint( $_GET['month'] ) : null;
		$selected_type = isset( $_GET['type'] ) ? sanitize_text_field( $_GET['type'] ) : null;
		
		// Get available years
		$years = $wpdb->get_col( "SELECT DISTINCT statistic_year FROM {$table_name} ORDER BY statistic_year DESC" );
		
		// Get available types
		$types = $wpdb->get_col( "SELECT DISTINCT statistic_type FROM {$table_name} ORDER BY statistic_type" );
		
		// Build query
		$where_clauses = array();
		$query_params = array();
		
		if ( $selected_year ) {
			$where_clauses[] = 'statistic_year = %d';
			$query_params[] = $selected_year;
		}
		
		if ( $selected_month ) {
			$where_clauses[] = 'statistic_month = %d';
			$query_params[] = $selected_month;
		}
		
		if ( $selected_type ) {
			$where_clauses[] = 'statistic_type = %s';
			$query_params[] = $selected_type;
		}
		
		$where_sql = ! empty( $where_clauses ) ? 'WHERE ' . implode( ' AND ', $where_clauses ) : '';
		
		// Get statistics
		$query = "SELECT * FROM {$table_name} {$where_sql} ORDER BY statistic_year DESC, statistic_month DESC, statistic_type";
		
		if ( ! empty( $query_params ) ) {
			$query = $wpdb->prepare( $query, $query_params );
		}
		
		$statistics = $wpdb->get_results( $query );
		
		// Render page
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			
			<div class="tablenav top">
				<form method="get">
					<input type="hidden" name="page" value="gatherpress-statistics-archive" />
					
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
					
					<select name="type">
						<option value=""><?php esc_html_e( 'All Types', 'gatherpress-statistics' ); ?></option>
						<?php foreach ( $types as $type ) : ?>
							<option value="<?php echo esc_attr( $type ); ?>" <?php selected( $selected_type, $type ); ?>>
								<?php echo esc_html( ucwords( str_replace( '_', ' ', $type ) ) ); ?>
							</option>
						<?php endforeach; ?>
					</select>
					
					<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'gatherpress-statistics' ); ?>" />
				</form>
			</div>
			
			<?php if ( empty( $statistics ) ) : ?>
				<p><?php esc_html_e( 'No archived statistics found.', 'gatherpress-statistics' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php esc_html_e( 'Year', 'gatherpress-statistics' ); ?></th>
							<th><?php esc_html_e( 'Month', 'gatherpress-statistics' ); ?></th>
							<th><?php esc_html_e( 'Type', 'gatherpress-statistics' ); ?></th>
							<th><?php esc_html_e( 'Value', 'gatherpress-statistics' ); ?></th>
							<th><?php esc_html_e( 'Archived', 'gatherpress-statistics' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $statistics as $stat ) : ?>
							<tr>
								<td><?php echo esc_html( $stat->statistic_year ); ?></td>
								<td><?php echo esc_html( date_i18n( 'F', mktime( 0, 0, 0, $stat->statistic_month, 1 ) ) ); ?></td>
								<td><?php echo esc_html( ucwords( str_replace( '_', ' ', $stat->statistic_type ) ) ); ?></td>
								<td><?php echo esc_html( number_format_i18n( $stat->statistic_value ) ); ?></td>
								<td><?php echo esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $stat->archived_at ) ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Archive monthly statistics.
	 *
	 * @since 0.1.0
	 *
	 * @global \wpdb $wpdb WordPress database abstraction object.
	 * @return void
	 */
	public function archive_monthly_statistics(): void {
		global $wpdb;
		
		$table_name = $wpdb->prefix . 'gatherpress_statistics_archive';
		$current_time = current_time( 'mysql' );
		$current_year = (int) date( 'Y' );
		$current_month = (int) date( 'n' );
		
		// Get all common configs
		$configs = $this->get_common_configs();
		
		foreach ( $configs as $config ) {
			if ( ! isset( $config['type'] ) || ! isset( $config['filters'] ) ) {
				continue;
			}
			
			$value = $this->calculate( $config['type'], $config['filters'] );
			$filters_hash = md5( wp_json_encode( $config['filters'] ) );
			
			$wpdb->insert(
				$table_name,
				array(
					'statistic_type'  => $config['type'],
					'statistic_year'  => $current_year,
					'statistic_month' => $current_month,
					'filters_hash'    => $filters_hash,
					'filters_data'    => wp_json_encode( $config['filters'] ),
					'statistic_value' => $value,
					'archived_at'     => $current_time,
				),
				array( '%s', '%d', '%d', '%s', '%s', '%d', '%s' )
			);
		}
	}

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
	 * @return \WP_REST_Response List of supported types.
	 */
	public function get_supported_types_endpoint(): \WP_REST_Response {
		$supported_types = $this->get_supported_statistic_types();
		
		return new \WP_REST_Response( $supported_types, 200 );
	}

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
				$this->clear_cache();
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
		if ( 'gatherpress_attendees_count' === $meta_key && $this->is_supported_post( $post_id ) ) {
			$this->clear_cache();
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
		if ( 'gatherpress_attendees_count' === $meta_key && $this->is_supported_post( $post_id ) ) {
			$this->clear_cache();
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
		
		$supported_taxonomies = $this->get_filtered_taxonomies();

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
			$this->clear_cache();
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
		if ( $this->is_supported_post( $object_id ) ) {
			$this->clear_cache();
		}
	}

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
	 * Get all taxonomies registered for supported post types.
	 *
	 * @since 0.1.0
	 *
	 * @return array<int, \WP_Taxonomy> Array of taxonomy objects.
	 */
	public function get_taxonomies(): array {
		$post_types = $this->get_supported_post_types();
		
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
	 * Calculate statistics based on type and filters.
	 *
	 * @since 0.1.0
	 *
	 * @param string               $statistic_type The type of statistic to calculate.
	 * @param array<string, mixed> $filters        Filters to apply.
	 * @return int Calculated statistic value.
	 */
	public function calculate( string $statistic_type, array $filters = array() ): int {
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
		
		$result = is_numeric( $result ) ? absint( $result ) : 0;
		
		return apply_filters( 'gatherpress_stats_calculate_' . $statistic_type, $result, $filters );
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
		$post_types = $this->get_supported_post_types();
		
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
		$post_types = $this->get_supported_post_types();
		
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
		$post_types = $this->get_supported_post_types();
		
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
		$post_types = $this->get_supported_post_types();
		
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
				$attendee_count = (int) get_post_meta( $post_id, 'gatherpress_attendees_count', true );
				
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
		
		if ( ! $this->has_supported_post_types() ) {
			return 0;
		}
		
		if ( ! $this->is_statistic_type_supported( $statistic_type ) ) {
			return 0;
		}

		$expiration = $this->get_cache_expiration();

		$cache_key = $this->get_cache_key( $statistic_type, $filters );
		
		$cached = get_transient( $cache_key );
		
		if ( false !== $cached && is_numeric( $cached ) ) {
			return absint( $cached );
		}
		
		$value = $this->calculate( $statistic_type, $filters );
		
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
		
		$supported_types = $this->get_supported_statistic_types();
		
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
		
		$taxonomies = $this->get_filtered_taxonomies();
		
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
		if ( ! $this->has_supported_post_types() ) {
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
			
			$value = $this->calculate(
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

	/**
	 * Check if term changes require cache clearing.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if cache should be cleared.
	 */
	public function should_clear_cache_for_term_changes(): bool {
		$supported_types = $this->get_supported_statistic_types();
		
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

/**
 * Plugin activation hook.
 *
 * @since 0.1.0
 *
 * @return void
 */
function activate_plugin(): void {
	Plugin::get_instance()->create_archive_table();
	
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
	return Plugin::get_instance()->get_cached( $statistic_type, $filters );
}

/**
 * Clear cache - convenience wrapper.
 *
 * @since 0.1.0
 *
 * @return void
 */
function clear_cache(): void {
	Plugin::get_instance()->clear_cache();
}

/**
 * Pre-generate cache - convenience wrapper.
 *
 * @since 0.1.0
 *
 * @return void
 */
function pregenerate_cache(): void {
	Plugin::get_instance()->pregenerate_cache();
}

// Initialize the plugin
Plugin::get_instance();