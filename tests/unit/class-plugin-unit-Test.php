<?php
/**
 * Tests for the root plugin file.
 *
 * @package BH_WC_Auto_Purchase_Stamps
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps;

use BrianHenryIE\WC_Auto_Purchase_Stamps\API\API;
use BrianHenryIE\WC_Auto_Purchase_Stamps\API\Settings;
use BrianHenryIE\WC_Auto_Purchase_Stamps\Includes\BH_WC_Auto_Purchase_Stamps;
use Psr\Log\LogLevel;

/**
 * Class Plugin_WP_Mock_Test
 */
class Plugin_Unit_Test extends \Codeception\Test\Unit {

	protected function setup() : void {
		parent::setUp();
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
		parent::tearDown();
	}

	/**
	 * Verifies the plugin initialization.
	 * Verifies the plugin does not output anything to screen.
	 */
	public function test_plugin_include() {

		// Prevents code-coverage counting, and removes the need to define the WordPress functions that are used in that class.
		\Patchwork\redefine(
			array( BH_WC_Auto_Purchase_Stamps::class, '__construct' ),
			function( $api, $settings, $logger ) {}
		);

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

		// For bh-wp-logger.
		\WP_Mock::userFunction(
			'is_admin',
			array(
				'return_arg' => false,
			)
		);

		\WP_Mock::userFunction(
			'get_current_user_id'
		);

		\WP_Mock::userFunction(
			'wp_normalize_path',
			array(
				'return_arg' => true,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'       => array( Settings::LOG_LEVEL_OPTION_NAME, LogLevel::NOTICE ),
				'return_arg' => 1,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'active_plugins' ),
				'return' => array( 'woocommerce/woocommerce.php' ),
				'times'  => 1,
			)
		);

		ob_start();

		include $plugin_root_dir . '/bh-wc-auto-purchase-stamps.php';

		$printed_output = ob_get_contents();

		ob_end_clean();

		$this->assertEmpty( $printed_output );

		$this->assertArrayHasKey( 'bh_wc_auto_purchase_stamps', $GLOBALS );

		$this->assertInstanceOf( API::class, $GLOBALS['bh_wc_auto_purchase_stamps'] );

	}


}
