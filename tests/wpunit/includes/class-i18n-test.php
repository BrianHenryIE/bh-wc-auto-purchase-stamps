<?php
/**
 * Tests for I18n. Tests load_plugin_textdomain.
 *
 * @package BH_WP_Auto_Purchase_Stampscom
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_Auto_Purchase_Stampscom\includes;

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
		 * @var BH_WP_Auto_Purchase_Stampscom $bh_wp_auto_purchase_stampscom
		 */
		$bh_wp_auto_purchase_stampscom = $GLOBALS['bh_wp_auto_purchase_stampscom'];
		$i18n         = $bh_wp_auto_purchase_stampscom->i18n;

		$i18n->load_plugin_textdomain();

		$this->assertTrue( $called, 'plugin_locale filter not called within load_plugin_textdomain() suggesting it has not been set by the plugin.' );
		$this->assertEquals( 'bh-wp-auto-purchase-stampscom', $actual_domain );

	}
}
