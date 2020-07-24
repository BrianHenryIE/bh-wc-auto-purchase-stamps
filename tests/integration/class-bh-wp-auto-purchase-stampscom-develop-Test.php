<?php
/**
 * Class Plugin_Test. Tests the root plugin setup.
 *
 * @package BH_WP_Auto_Purchase_Stampscom
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_Auto_Purchase_Stampscom;

use BH_WP_Auto_Purchase_Stampscom\includes\BH_WP_Auto_Purchase_Stampscom;

/**
 * Verifies the plugin has been instantiated and added to PHP's $GLOBALS variable.
 */
class Plugin_Develop_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Test the main plugin object is added to PHP's GLOBALS and that it is the correct class.
	 */
	public function test_plugin_instantiated() {

		$this->assertArrayHasKey( 'bh_wp_auto_purchase_stampscom', $GLOBALS );

		$this->assertInstanceOf( BH_WP_Auto_Purchase_Stampscom::class, $GLOBALS['bh_wp_auto_purchase_stampscom'] );
	}

}
