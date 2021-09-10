<?php
/**
 * Tests for I18n. Tests load_plugin_textdomain.
 *
 * @package BH_WC_Auto_Purchase_Stamps
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\Includes;

/**
 * Class I18n_Test
 *
 * @see I18n
 */
class I18n_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Checks if the filter run by WordPress in the load_plugin_textdomain() function is called.
	 *
	 * @see load_plugin_textdomain()
	 */
	public function test_load_plugin_textdomain_function() {

		$called        = false;
		$actual_domain = null;

		$filter = function( $locale, $domain ) use ( &$called, &$actual_domain ) {

			$called        = true;
			$actual_domain = $domain;

			return $locale;
		};

		add_filter( 'plugin_locale', $filter, 10, 2 );

		/**
		 * Get the main plugin class.
		 *
		 * @var BH_WC_Auto_Purchase_Stamps $bh_wc_auto_purchase_stamps
		 */
		$bh_wc_auto_purchase_stamps = $GLOBALS['bh_wc_auto_purchase_stamps'];
		$i18n                       = $bh_wc_auto_purchase_stamps->i18n;

		$i18n->load_plugin_textdomain();

		$this->assertTrue( $called, 'plugin_locale filter not called within load_plugin_textdomain() suggesting it has not been set by the plugin.' );
		$this->assertEquals( 'bh-wc-auto-purchase-stamps', $actual_domain );

	}
}
