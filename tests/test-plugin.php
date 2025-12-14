<?php
/**
 * Tests for plugin.php.
 *
 * @package gatherpress_statistics
 */

use Yoast\WPTestUtils\WPIntegration\TestCase;

/**
 * Tests for plugin.php.
 */
class Test_Plugin extends TestCase {

	/**
	 * Test bootstrap.
	 */
	public function test_bootstrap() {
		$this->assertTrue( defined( 'gatherpress_statistics_VERSION' ) );
		$this->assertTrue( defined( 'gatherpress_statistics_PLUGIN_FILE' ) );
		$this->assertTrue( defined( 'gatherpress_statistics_PLUGIN_DIR' ) );

		// $this->assertTrue( class_exists( 'WP_Web_App_Manifest' ) );
		// $this->assertTrue( class_exists( 'WP_Service_Workers' ) );
	}
}
