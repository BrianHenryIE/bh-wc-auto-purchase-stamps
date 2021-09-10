<?php
/**
 * Class Plugin_Test. Tests the root plugin setup.
 *
 * @package BH_WC_Auto_Purchase_Stamps
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps;

use BrianHenryIE\WC_Auto_Purchase_Stamps\api\API;
use BrianHenryIE\WC_Auto_Purchase_Stamps\includes\BH_WC_Auto_Purchase_Stamps;

/**
 * Verifies the plugin has been instantiated and added to PHP's $GLOBALS variable.
 */
class Plugin_Develop_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Test the main plugin object is added to PHP's GLOBALS and that it is the correct class.
	 */
	public function test_plugin_instantiated() {

		$this->assertArrayHasKey( 'bh_wc_auto_purchase_stamps', $GLOBALS );

		$this->assertInstanceOf( API::class, $GLOBALS['bh_wc_auto_purchase_stamps'] );
	}

}
