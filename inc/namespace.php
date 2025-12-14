<?php
/**
 * Figuren_Theater gatherpress_statistics.
 *
 * @package figuren-theater/gatherpress-statistics
 */

namespace Figuren_Theater\gatherpress_statistics;

use Altis;

/**
 * Register module.
 *
 * @return void
 */
function register() :void {

	$default_settings = [
		'enabled' => true, // Needs to be set.
	];
	$options = [
		'defaults' => $default_settings,
	];

	Altis\register_module(
		'gatherpress-statistics',
		DIRECTORY,
		'gatherpress_statistics',
		$options,
		__NAMESPACE__ . '\\bootstrap'
	);
}

/**
 * Bootstrap module, when enabled.
 *
 * @return void
 */
function bootstrap() :void {

	/**
	 * Automatically load Plugins.
	 *
	 * @example NameSpace\bootstrap();
	 */

	/**
	 * Load 'Best practices'.
	 *
	 * @example NameSpace\bootstrap();
	 */
}
