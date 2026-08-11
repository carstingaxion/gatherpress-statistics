/**
 * External Dependencies
 */
const path = require( 'path' );

/**
 * WordPress Dependencies
 */
const defaultConfig = require( '@wordpress/scripts/config/webpack.config.js' );

/**
 * Extends the default @wordpress/scripts webpack config.
 *
 * The `gatherpress/statistics` block entry point (index.js, view.js,
 * render.php, …) under `src/editor/blocks/statistics/` is picked up
 * automatically via its `block.json`. The admin dashboard page script is
 * not tied to a block, so it needs an explicit entry here.
 */
module.exports = {
	...defaultConfig,
	entry: {
		...defaultConfig.entry(),
		'admin/page/index': path.resolve(
			process.cwd(),
			'src/admin/page',
			'index.js'
		),
	},
};
