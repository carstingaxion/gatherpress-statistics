<?php
/**
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

/**
 * Extract and sanitize block attributes.
 *
 * @var array{
 *   statisticType?: string,
 *   labelSingular?: string,
 *   labelPlural?: string,
 *   selectedTerm?: int,
 *   selectedTaxonomy?: string,
 *   selectedTaxonomyTerms?: array<string, mixed>,
 *   countTaxonomy?: string,
 *   filterTaxonomy?: string,
 *   useContextTerm?: bool,
 *   contextTaxonomies?: array<int, string>,
 *   eventQuery?: string,
 *   showLabel?: bool,
 *   prefixDefault?: string,
 *   suffixDefault?: string,
 *   prefixConditional?: string,
 *   suffixConditional?: string,
 *   conditionalThreshold?: int,
 * } $attributes
 */
$statistic_type = isset( $attributes['statisticType'] ) ? $attributes['statisticType'] : 'total_events';
$label_singular = isset( $attributes['labelSingular'] ) ? $attributes['labelSingular'] : __( 'Event', 'gatherpress-statistics' );
$label_plural   = isset( $attributes['labelPlural'] ) ? $attributes['labelPlural'] : __( 'Events', 'gatherpress-statistics' );
$selected_term  = isset( $attributes['selectedTerm'] ) ? intval( $attributes['selectedTerm'] ) : 0;
$event_query    = isset( $attributes['eventQuery'] ) ? $attributes['eventQuery'] : 'past';
$show_label     = isset( $attributes['showLabel'] ) ? $attributes['showLabel'] : true;

// Context-awareness: derive term(s) from the post this block is placed on
// (a Single Event/Venue template, or the current Query Loop item) instead
// of a manually picked term.
$use_context_term   = isset( $attributes['useContextTerm'] ) ? (bool) $attributes['useContextTerm'] : false;
$context_taxonomies = isset( $attributes['contextTaxonomies'] ) && is_array( $attributes['contextTaxonomies'] )
	? $attributes['contextTaxonomies']
	: array();

$context_post_id = 0;
if ( isset( $block ) && $block instanceof WP_Block && ! empty( $block->context['postId'] ) ) {
	$context_post_id = absint( $block->context['postId'] );
} else {
	$context_post_id = absint( get_queried_object_id() );
}

// Single-taxonomy path: swap the manually selected term for the one found
// on the context post, for whichever taxonomy this statistic type actually
// filters by.
if ( $use_context_term && $context_post_id > 0 ) {
	$context_taxonomy = '';

	if ( 'taxonomy_terms_by_taxonomy' === $statistic_type && ! empty( $attributes['filterTaxonomy'] ) ) {
		$context_taxonomy = $attributes['filterTaxonomy'];
	} elseif ( ! empty( $attributes['selectedTaxonomy'] ) ) {
		$context_taxonomy = $attributes['selectedTaxonomy'];
	}

	if ( ! empty( $context_taxonomy ) ) {
		$resolved_term = gatherpress_statistics_resolve_context_term( $context_post_id, $context_taxonomy );
		if ( $resolved_term > 0 ) {
			$selected_term = $resolved_term;
		} else {
			// No term found on the context post for this taxonomy: nothing to filter by.
			$selected_term = 0;
		}
	}
}

// Prefix and suffix settings
$prefix_default        = isset( $attributes['prefixDefault'] ) ? $attributes['prefixDefault'] : '';
$suffix_default        = isset( $attributes['suffixDefault'] ) ? $attributes['suffixDefault'] : '';
$prefix_conditional    = isset( $attributes['prefixConditional'] ) ? $attributes['prefixConditional'] : '';
$suffix_conditional    = isset( $attributes['suffixConditional'] ) ? $attributes['suffixConditional'] : '';
$conditional_threshold = isset( $attributes['conditionalThreshold'] ) ? intval( $attributes['conditionalThreshold'] ) : 10;

// CRITICAL: For total_attendees, always force eventQuery to 'past'
if ( 'total_attendees' === $statistic_type ) {
	$event_query = 'past';
} else {
	// For other types, validate event_query - only 'upcoming' or 'past' allowed
	if ( empty( $event_query ) || ! in_array( $event_query, array( 'upcoming', 'past' ), true ) ) {
		// Default to 'past' if invalid or empty
		$event_query = 'past';
	}
}

// Build filters array
$filters = array();

// CRITICAL: Add event query filter FIRST - this must be present for cache key generation
// Only 'upcoming' or 'past' are valid values
$filters['event_query'] = sanitize_key( $event_query );

if ( $selected_term > 0 ) {
	$filters['term_id'] = $selected_term;
}

// Get selected taxonomy for single taxonomy operations
if ( ! empty( $attributes['selectedTaxonomy'] ) ) {
	$filters['taxonomy'] = $attributes['selectedTaxonomy'];
}

// Get count and filter taxonomies for cross-taxonomy operations
if ( ! empty( $attributes['countTaxonomy'] ) ) {
	$filters['count_taxonomy'] = $attributes['countTaxonomy'];
}

if ( ! empty( $attributes['filterTaxonomy'] ) ) {
	$filters['filter_taxonomy'] = $attributes['filterTaxonomy'];
}

// Handle multiple taxonomy selection
if ( 'events_multi_taxonomy' === $statistic_type ) {
	$taxonomy_terms = array();
	
	if ( ! empty( $attributes['selectedTaxonomyTerms'] ) ) {
		foreach ( $attributes['selectedTaxonomyTerms'] as $taxonomy => $term_ids ) {
			if ( ! empty( $term_ids ) && is_array( $term_ids ) ) {
				$taxonomy_terms[ $taxonomy ] = $term_ids;
			}
		}
	}

	// Any taxonomy listed in contextTaxonomies gets its term resolved from
	// the context post instead of (or in addition to) a manual selection.
	if ( $context_post_id > 0 ) {
		foreach ( $context_taxonomies as $context_taxonomy_slug ) {
			if ( ! is_string( $context_taxonomy_slug ) || empty( $context_taxonomy_slug ) ) {
				continue;
			}

			$resolved_term = gatherpress_statistics_resolve_context_term( $context_post_id, $context_taxonomy_slug );

			if ( $resolved_term > 0 ) {
				$taxonomy_terms[ $context_taxonomy_slug ] = array( $resolved_term );
			} else {
				// No term found on the context post: don't filter by this taxonomy.
				unset( $taxonomy_terms[ $context_taxonomy_slug ] );
			}
		}
	}
	
	if ( ! empty( $taxonomy_terms ) ) {
		$filters['taxonomy_terms'] = $taxonomy_terms;
	}
}

// Get cached statistic
$count = gatherpress_statistics_get_cached( $statistic_type, $filters );

// Don't display if count is 0.
if ( $count === 0 || ! is_int( $count ) ) {
	return;
}

// Determine which prefix/suffix to use based on threshold.
$use_conditional = $count > $conditional_threshold;
$display_prefix  = ( $use_conditional && ! empty( $prefix_conditional ) ) ? $prefix_conditional : $prefix_default;
$display_suffix  = ( $use_conditional && ! empty( $suffix_conditional ) ) ? $suffix_conditional : $suffix_default;

// Determine which label to use based on count (singular for 1, plural for everything else).
$display_label = ( 1 === $count ) ? $label_singular : $label_plural;
?>
<figure <?php echo get_block_wrapper_attributes(); ?>>
	<data class="gatherpress-stats-value" value="<?php echo esc_attr( (string) $count ); ?>">
		<?php 
		if ( ! empty( $display_prefix ) ) {
			?>
			<span class="gatherpress-stats-prefix"><?php echo esc_html( $display_prefix ); ?></span> 
			<?php
		}
		?>
		<span class="gatherpress-stats-number"><?php echo esc_html( number_format_i18n( $count ) ); ?></span>
		<?php
		if ( ! empty( $display_suffix ) ) {
			?>
			<span class="gatherpress-stats-suffix"><?php echo esc_html( $display_suffix ); ?></span>
			<?php
		}
		?>
	</data>
	<?php if ( $show_label && ! empty( $display_label ) ) { ?>
		<figcaption class="gatherpress-stats-label">
			<?php echo esc_html( $display_label ); ?>
		</figcaption>
	<?php } ?>
</figure>