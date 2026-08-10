<?php
/**
 * Main plugin bootstrap class.
 *
 * @package GatherPressStatistics
 */

namespace GatherPressStatistics;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

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
		add_action( 'rest_api_init', array( Rest_Api::get_instance(), 'register_rest_routes' ) );
		add_action( 'gatherpress_statistics_regenerate_cache', array( Cache::get_instance(), 'pregenerate_cache' ) );
		add_action( 'transition_post_status', array( Cache_Invalidation::get_instance(), 'clear_cache_on_status_change' ), 10, 3 );
		add_action( 'updated_post_meta', array( Cache_Invalidation::get_instance(), 'clear_cache_on_meta_update' ), 10, 3 );
		add_action( 'added_post_meta', array( Cache_Invalidation::get_instance(), 'clear_cache_on_meta_update' ), 10, 3 );
		add_action( 'deleted_post_meta', array( Cache_Invalidation::get_instance(), 'clear_cache_on_meta_delete' ), 10, 3 );
		add_action( 'create_term', array( Cache_Invalidation::get_instance(), 'clear_cache_on_term_change' ), 10, 3 );
		add_action( 'edit_term', array( Cache_Invalidation::get_instance(), 'clear_cache_on_term_change' ), 10, 3 );
		add_action( 'delete_term', array( Cache_Invalidation::get_instance(), 'clear_cache_on_term_change' ), 10, 3 );
		add_action( 'set_object_terms', array( Cache_Invalidation::get_instance(), 'clear_cache_on_term_relationship' ), 10, 3 );
		add_filter( 'posts_where', array( Query_Filters::get_instance(), 'filter_gatherpress_event_dates' ), 10, 2 );
		
		// Only setup archive functionality if enabled
		if ( $this->is_archive_enabled() ) {
			add_action( 'init', array( Database::get_instance(), 'create_archive_table' ) );
			add_action( 'admin_menu', array( Admin_Page::get_instance(), 'register_admin_page' ) );
			add_action( 'admin_init', array( Admin_Page::get_instance(), 'handle_manual_archive_generation' ) );
			add_action( 'admin_enqueue_scripts', array( Admin_Page::get_instance(), 'enqueue_admin_assets' ) );
			add_action( 'gatherpress_statistics_monthly_archive', array( Archive::get_instance(), 'archive_monthly_statistics' ) );
		}
	}
	
	/**
	 * Check if archive functionality is enabled.
	 *
	 * @since 0.1.0
	 *
	 * @return bool True if archive is enabled.
	 */
	public function is_archive_enabled(): bool {
		/**
		 * Filter whether archive functionality is enabled.
		 *
		 * @since 0.1.0
		 *
		 * @param bool $enabled Whether archive is enabled. Default true.
		 */
		return (bool) apply_filters( 'gatherpress_statistics_enable_archive', true );
	}
}
