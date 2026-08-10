/**
 * "Statistics Archive" dashboard page.
 *
 * Renders the trend chart and term-toggle legend for the archived
 * statistics table. Reads chart data from the `gatherpressChartData`
 * global localized onto this script by Admin_Page::enqueue_admin_assets().
 */

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './style.scss';

jQuery( document ).ready( function ( $ ) {
	if ( typeof Chart === 'undefined' || ! window.gatherpressChartData ) {
		return;
	}

	const gatherpressChartData = window.gatherpressChartData;

	const ctx = document.getElementById( 'gatherpress-stats-chart' );
	if ( ! ctx ) {
		return;
	}

	const chartConfig = {
		type: 'line',
		data: {
			labels: gatherpressChartData.labels,
			datasets: gatherpressChartData.datasets,
		},
		options: {
			responsive: true,
			maintainAspectRatio: true,
			plugins: {
				legend: {
					display: false,
				},
				tooltip: {
					mode: 'index',
					intersect: false,
				},
			},
			scales: {
				y: {
					beginAtZero: true,
					ticks: {
						precision: 0,
						stepSize: 1,
					},
				},
			},
		},
	};

	const chart = new Chart( ctx, chartConfig );

	const togglesContainer = document.getElementById(
		'gatherpress-term-toggles'
	);
	if ( ! togglesContainer ) {
		return;
	}

	gatherpressChartData.datasets.forEach( function ( dataset, index ) {
		const toggle = document.createElement( 'div' );
		toggle.className = 'term-toggle active';
		toggle.setAttribute( 'data-index', index );

		const colorBox = document.createElement( 'div' );
		colorBox.className = 'term-color-box';
		colorBox.style.backgroundColor = dataset.borderColor;

		const label = document.createElement( 'span' );
		label.textContent = dataset.label;

		toggle.appendChild( colorBox );
		toggle.appendChild( label );
		togglesContainer.appendChild( toggle );

		toggle.addEventListener( 'click', function () {
			const meta = chart.getDatasetMeta( index );
			meta.hidden = ! meta.hidden;
			toggle.classList.toggle( 'active' );
			chart.update();
		} );
	} );
} );
