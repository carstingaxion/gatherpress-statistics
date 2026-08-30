<?php
/**
 * Cache invalidation hooks for statistics-affecting data changes.
 *
 * @package GatherPressStatistics
 */

namespace GatherPressStatistics;

use GatherPress\Core;
use WP_Post;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Cache invalidation class.
 *
 * @since 0.1.0
 */
class Cache_Invalidation {

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
		add_action( 'transition_post_status', array( $this, 'clear_cache_on_status_change' ), 10, 3 );
		add_action( 'updated_post_meta', array( $this, 'clear_cache_on_meta_update' ), 10, 3 );
		add_action( 'added_post_meta', array( $this, 'clear_cache_on_meta_update' ), 10, 3 );
		add_action( 'deleted_post_meta', array( $this, 'clear_cache_on_meta_delete' ), 10, 3 );
		add_action( 'create_term', array( $this, 'clear_cache_on_term_change' ), 10, 3 );
		add_action( 'edit_term', array( $this, 'clear_cache_on_term_change' ), 10, 3 );
		add_action( 'delete_term', array( $this, 'clear_cache_on_term_change' ), 10, 3 );
		add_action( 'set_object_terms', array( $this, 'clear_cache_on_term_relationship' ) );
	}

	/**
	 * Clear cache when event post status changes.
	 *
	 * @since 0.1.0
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
	 * @return void
	 */
	public function clear_cache_on_status_change( string $new_status, string $old_status, WP_Post $post ): void {
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

		if ( empty( $supported_taxonomies ) ) {
			return;
		}

		$taxonomy_slugs = array();
		foreach ( $supported_taxonomies as $tax_obj ) {
			$taxonomy_slugs[] = $tax_obj->name;
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
	 * @param int $object_id Object ID.
	 * @return void
	 */
	public function clear_cache_on_term_relationship( int $object_id ): void {
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
