<?php
/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    BH_WC_Auto_Purchase_Stamps
 * @subpackage BH_WC_Auto_Purchase_Stamps/includes
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\Includes;

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    BH_WC_Auto_Purchase_Stamps
 * @subpackage BH_WC_Auto_Purchase_Stamps/includes
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */
class I18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'bh-wc-auto-purchase-stamps',
			false,
			plugin_basename( dirname( __FILE__, 2 ) ) . '/languages/'
		);

	}



}
