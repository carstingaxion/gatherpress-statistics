<?php
/**
 * Dashboard page for browsing and generating archived statistics.
 *
 * @package GatherPressStatistics
 */

namespace GatherPressStatistics;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit; // @codeCoverageIgnore

/**
 * Admin page class.
 *
 * @since 0.1.0
 */
class Admin_Page {
	/**
	 * Class instance.
	 *
	 * @since 0.1.0
	 * @var Admin_Page|null
	 */
	private static $instance = null;

	/**
	 * Get class instance.
	 *
	 * @since 0.1.0
	 *
	 * @return Admin_Page
	 */
	public static function get_instance(): Admin_Page {
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

		$year  = isset( $_POST['archive_year'] ) ? absint( $_POST['archive_year'] ) : 0;
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
		$post_types   = Support::get_instance()->get_supported_post_types();
		$post_type    = ! empty( $post_types ) ? $post_types[0] : 'gatherpress_event';
		$plural_label = Support::get_instance()->get_post_type_plural_label( $post_type );

		$labels = array(
			'total_events'               => sprintf( __( 'Total %s', 'gatherpress-statistics' ), $plural_label ),
			'events_per_taxonomy'        => sprintf( __( '%s per Taxonomy', 'gatherpress-statistics' ), $plural_label ),
			'events_multi_taxonomy'      => sprintf( __( '%s (Multiple Taxonomies)', 'gatherpress-statistics' ), $plural_label ),
			'total_taxonomy_terms'       => __( 'Total Taxonomy Terms', 'gatherpress-statistics' ),
			'taxonomy_terms_by_taxonomy' => __( 'Taxonomy Terms by Taxonomy', 'gatherpress-statistics' ),
			'total_attendees'            => __( 'Total Attendees', 'gatherpress-statistics' ),
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
		$all_dates    = array();

		foreach ( $statistics as $stat ) {
			$filters                = json_decode( $stat->filters_data, true );
			$date_key               = sprintf( '%d-%02d', $stat->statistic_year, $stat->statistic_month );
			$all_dates[ $date_key ] = true;

			$term_label = 'All';
			$term_id    = 0;

			if ( isset( $filters['term_id'] ) && $filters['term_id'] > 0 ) {
				$term = get_term( $filters['term_id'] );
				if ( $term && ! is_wp_error( $term ) ) {
					$term_label = $term->name;
					$term_id    = $term->term_id;
				}
			}

			if ( ! isset( $data_by_term[ $term_id ] ) ) {
				$data_by_term[ $term_id ] = array(
					'label' => $term_label,
					'data'  => array(),
				);
			}

			$data_by_term[ $term_id ]['data'][ $date_key ] = $stat->statistic_value;
		}

		$dates = array_keys( $all_dates );
		sort( $dates );

		$datasets    = array();
		$colors      = array(
			'#3366CC',
			'#DC3912',
			'#FF9900',
			'#109618',
			'#990099',
			'#3B3EAC',
			'#0099C6',
			'#DD4477',
			'#66AA00',
			'#B82E2E',
		);
		$color_index = 0;

		foreach ( $data_by_term as $term_id => $term_data ) {
			$values = array();
			foreach ( $dates as $date ) {
				$values[] = isset( $term_data['data'][ $date ] ) ? $term_data['data'][ $date ] : 0;
			}

			$color = $colors[ $color_index % count( $colors ) ];
			++$color_index;

			$datasets[] = array(
				'label'           => $term_data['label'],
				'data'            => $values,
				'termId'          => $term_id,
				'borderColor'     => $color,
				'backgroundColor' => $color . '33',
				'tension'         => 0.4,
			);
		}

		$labels = array_map(
			function ( $date ) {
				[ $year, $month ] = explode( '-', $date );
				return date_i18n( 'M Y', mktime( 0, 0, 0, (int) $month, 1, (int) $year ) );
			},
			$dates 
		);

		return array(
			'labels'   => $labels,
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
		
		$selected_year     = isset( $_GET['year'] ) ? absint( $_GET['year'] ) : null;
		$selected_month    = isset( $_GET['month'] ) ? absint( $_GET['month'] ) : null;
		$selected_taxonomy = isset( $_GET['taxonomy'] ) ? sanitize_text_field( $_GET['taxonomy'] ) : null;
		$selected_term     = isset( $_GET['term_id'] ) ? absint( $_GET['term_id'] ) : null;
		$current_tab       = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : '';
		$order_by          = isset( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'statistic_year';
		$order             = isset( $_GET['order'] ) && $_GET['order'] === 'asc' ? 'ASC' : 'DESC';
		
		$supported_types = Support::get_instance()->get_supported_statistic_types();
		
		if ( empty( $current_tab ) && ! empty( $supported_types ) ) {
			$current_tab = $supported_types[0];
		}
		
		$years = $wpdb->get_col( "SELECT DISTINCT statistic_year FROM {$table_name} ORDER BY statistic_year DESC" );
		
		$taxonomies  = array();
		$all_filters = $wpdb->get_col( "SELECT DISTINCT filters_data FROM {$table_name}" );
		foreach ( $all_filters as $filters_json ) {
			$filters = json_decode( $filters_json, true );
			if ( isset( $filters['taxonomy'] ) && ! in_array( $filters['taxonomy'], $taxonomies, true ) ) {
				$taxonomies[] = $filters['taxonomy'];
			}
		}
		sort( $taxonomies );
		
		$where_clauses = array();
		$query_params  = array();
		
		if ( ! empty( $current_tab ) ) {
			$where_clauses[] = 'statistic_type = %s';
			$query_params[]  = $current_tab;
		}
		
		if ( $selected_year ) {
			$where_clauses[] = 'statistic_year = %d';
			$query_params[]  = $selected_year;
		}
		
		if ( $selected_month ) {
			$where_clauses[] = 'statistic_month = %d';
			$query_params[]  = $selected_month;
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
		
		$current_year  = (int) date( 'Y' );
		$current_month = (int) date( 'n' );
		
		$base_url = add_query_arg(
			array(
				'page'     => 'gatherpress-statistics-archive',
				'tab'      => $current_tab,
				'year'     => $selected_year,
				'month'    => $selected_month,
				'taxonomy' => $selected_taxonomy,
				'term_id'  => $selected_term,
			),
			admin_url( 'index.php' ) 
		);
		
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
									<?php for ( $y = $current_year; $y >= 2020; $y-- ) { ?>
										<option value="<?php echo esc_attr( $y ); ?>"><?php echo esc_html( $y ); ?></option>
									<?php } ?>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="archive_month"><?php esc_html_e( 'Month', 'gatherpress-statistics' ); ?></label>
							</th>
							<td>
								<select name="archive_month" id="archive_month" required>
									<?php for ( $m = 1; $m <= 12; $m++ ) { ?>
										<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $m, $current_month ); ?>>
											<?php echo esc_html( date_i18n( 'F', mktime( 0, 0, 0, $m, 1 ) ) ); ?>
										</option>
									<?php } ?>
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
				<?php foreach ( $supported_types as $type ) { ?>
					<a href="<?php echo esc_url( add_query_arg( 'tab', $type, remove_query_arg( array( 'orderby', 'order' ), $base_url ) ) ); ?>" 
						class="nav-tab <?php echo $type === $current_tab ? 'nav-tab-active' : ''; ?>">
						<?php echo esc_html( $this->get_statistic_type_label( $type ) ); ?>
					</a>
				<?php } ?>
			</h2>
			
			<?php if ( ! empty( $chart_data ) ) { ?>
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
			<?php } ?>
			
			<div class="tablenav top">
				<form method="get">
					<input type="hidden" name="page" value="gatherpress-statistics-archive" />
					<input type="hidden" name="tab" value="<?php echo esc_attr( $current_tab ); ?>" />
					
					<select name="year">
						<option value=""><?php esc_html_e( 'All Years', 'gatherpress-statistics' ); ?></option>
						<?php foreach ( $years as $year ) { ?>
							<option value="<?php echo esc_attr( $year ); ?>" <?php selected( $selected_year, $year ); ?>>
								<?php echo esc_html( $year ); ?>
							</option>
						<?php } ?>
					</select>
					
					<select name="month">
						<option value=""><?php esc_html_e( 'All Months', 'gatherpress-statistics' ); ?></option>
						<?php for ( $m = 1; $m <= 12; $m++ ) { ?>
							<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $selected_month, $m ); ?>>
								<?php echo esc_html( date_i18n( 'F', mktime( 0, 0, 0, $m, 1 ) ) ); ?>
							</option>
						<?php } ?>
					</select>
					
					<select name="taxonomy">
						<option value=""><?php esc_html_e( 'All Taxonomies', 'gatherpress-statistics' ); ?></option>
						<?php
						foreach ( $taxonomies as $tax_slug ) { 
							$tax_obj = get_taxonomy( $tax_slug );
							if ( $tax_obj ) {
								?>
							<option value="<?php echo esc_attr( $tax_slug ); ?>" <?php selected( $selected_taxonomy, $tax_slug ); ?>>
								<?php echo esc_html( $tax_obj->labels->name ); ?>
							</option>
							<?php } ?>
						<?php } ?>
					</select>
					
					<?php
					if ( $selected_taxonomy ) { 
						$terms = get_terms(
							array(
								'taxonomy'   => $selected_taxonomy,
								'hide_empty' => false,
							) 
						);
						if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
							?>
						<select name="term_id">
							<option value=""><?php esc_html_e( 'All Terms', 'gatherpress-statistics' ); ?></option>
							<?php foreach ( $terms as $term ) { ?>
								<option value="<?php echo esc_attr( $term->term_id ); ?>" <?php selected( $selected_term, $term->term_id ); ?>>
									<?php echo esc_html( $term->name ); ?>
								</option>
							<?php } ?>
						</select>
						<?php } ?>
					<?php } ?>
					
					<input type="submit" class="button" value="<?php esc_attr_e( 'Filter', 'gatherpress-statistics' ); ?>" />
				</form>
			</div>
			
			<?php if ( empty( $statistics ) ) { ?>
				<p><?php esc_html_e( 'No archived statistics found.', 'gatherpress-statistics' ); ?></p>
			<?php } else { ?>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th>
								<a href="
								<?php
								echo esc_url(
									add_query_arg(
										array(
											'orderby' => 'statistic_year',
											'order'   => $next_order,
										),
										$base_url 
									) 
								);
								?>
											">
									<?php esc_html_e( 'Year', 'gatherpress-statistics' ); ?>
									<?php if ( $order_by === 'statistic_year' ) { ?>
										<span class="dashicons dashicons-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></span>
									<?php } ?>
								</a>
							</th>
							<th>
								<a href="
								<?php
								echo esc_url(
									add_query_arg(
										array(
											'orderby' => 'statistic_month',
											'order'   => $next_order,
										),
										$base_url 
									) 
								);
								?>
											">
									<?php esc_html_e( 'Month', 'gatherpress-statistics' ); ?>
									<?php if ( $order_by === 'statistic_month' ) { ?>
										<span class="dashicons dashicons-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></span>
									<?php } ?>
								</a>
							</th>
							<th class="sortable-column">
								<a href="
								<?php
								echo esc_url(
									add_query_arg(
										array(
											'orderby' => 'taxonomy',
											'order'   => $next_order,
										),
										$base_url 
									) 
								);
								?>
											">
									<?php esc_html_e( 'Taxonomy', 'gatherpress-statistics' ); ?>
									<?php if ( $order_by === 'taxonomy' ) { ?>
										<span class="dashicons dashicons-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></span>
									<?php } ?>
								</a>
							</th>
							<th class="sortable-column">
								<a href="
								<?php
								echo esc_url(
									add_query_arg(
										array(
											'orderby' => 'term',
											'order'   => $next_order,
										),
										$base_url 
									) 
								);
								?>
											">
									<?php esc_html_e( 'Term', 'gatherpress-statistics' ); ?>
									<?php if ( $order_by === 'term' ) { ?>  
										<span class="dashicons dashicons-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></span>
									<?php } ?>
								</a>
							</th>
							<th>
								<a href="
								<?php
								echo esc_url(
									add_query_arg(
										array(
											'orderby' => 'statistic_value',
											'order'   => $next_order,
										),
										$base_url 
									) 
								);
								?>
								">
									<?php esc_html_e( 'Value', 'gatherpress-statistics' ); ?>
									<?php if ( $order_by === 'statistic_value' ) { ?>
										<span class="dashicons dashicons-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></span>
									<?php } ?>
								</a>
							</th>
							<th>
								<a href="
								<?php
								echo esc_url(
									add_query_arg(
										array(
											'orderby' => 'archived_at',
											'order'   => $next_order,
										),
										$base_url 
									) 
								);
								?>
								">
									<?php esc_html_e( 'Archived', 'gatherpress-statistics' ); ?>
									<?php if ( $order_by === 'archived_at' ) { ?>
										<span class="dashicons dashicons-arrow-<?php echo $order === 'ASC' ? 'up' : 'down'; ?>"></span>
									<?php } ?>
								</a>
							</th>
						</tr>
					</thead>
					<tbody>
						<?php 
						if ( in_array( $order_by, array( 'taxonomy', 'term' ), true ) ) {
							usort(
								$statistics,
								function ( $a, $b ) use ( $order_by, $order ) {
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
								} 
							);
						}
						
						foreach ( $statistics as $stat ) { 
							$filters       = json_decode( $stat->filters_data, true );
							$taxonomy_name = '';
							$term_name     = '';
							
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
						<?php } ?>
					</tbody>
				</table>
			<?php } ?>
		</div>
		<?php
	}
}
