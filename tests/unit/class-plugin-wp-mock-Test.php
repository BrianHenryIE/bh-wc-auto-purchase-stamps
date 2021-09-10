<?php
/**
 * Tests for the root plugin file.
 *
 * @package BH_WC_Auto_Purchase_Stamps
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps;

use BrianHenryIE\WC_Auto_Purchase_Stamps\includes\BH_WC_Auto_Purchase_Stamps;

/**
 * Class Plugin_WP_Mock_Test
 */
class Plugin_WP_Mock_Test extends \Codeception\Test\Unit {

	protected function _before() {
		\WP_Mock::setUp();
	}

	/**
	 * Verifies the plugin initialization.
	 */
	public function test_plugin_include() {

		// Should probably move these test to WP Unit...
		$this->markTestSkipped();

		$plugin_root_dir = dirname( __DIR__, 2 ) . '/src';

		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => $plugin_root_dir . '/',
			)
		);

		\WP_Mock::userFunction(
			'register_activation_hook'
		);

		\WP_Mock::userFunction(
			'register_deactivation_hook'
		);

		require_once $plugin_root_dir . '/bh-wc-auto-purchase-stamps.php';

		$this->assertArrayHasKey( 'bh_wc_auto_purchase_stamps', $GLOBALS );

		$this->assertInstanceOf( BH_WC_Auto_Purchase_Stamps::class, $GLOBALS['bh_wc_auto_purchase_stamps'] );

	}


	/**
	 * Verifies the plugin does not output anything to screen.
	 */
	public function test_plugin_include_no_output() {

		$this->markTestSkipped();

		$plugin_root_dir = dirname( __DIR__, 2 ) . '/src';

		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => $plugin_root_dir . '/',
			)
		);

		\WP_Mock::userFunction(
			'register_activation_hook'
		);

		\WP_Mock::userFunction(
			'register_deactivation_hook'
		);

		ob_start();

		require_once $plugin_root_dir . '/bh-wc-auto-purchase-stamps.php';

		$printed_output = ob_get_contents();

		ob_end_clean();

		$this->assertEmpty( $printed_output );

	}

}
