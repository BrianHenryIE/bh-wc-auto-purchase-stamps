<?php
/**
 * The plugin page output of the plugin.
 *
 * @link
 * @since      1.0.0
 *
 * @package    BH_WC_Auto_Purchase_Stamps
 * @subpackage BH_WC_Auto_Purchase_Stamps/admin
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\Admin;

/**
 * This class adds a `Settings` link on the plugins.php page, when possible.
 */
class Plugins_Page {

	/**
	 * Add link to Settings page in plugins.php list.
	 *
	 * @hooked plugin_action_links_ ...
	 *
	 * @param array $links_array The existing plugin links (usually "Deactivate").
	 *
	 * @return array The links to display below the plugin name on plugins.php.
	 */
	public function action_links( $links_array ) {

		// This plugin's settings are appended to the Stamps.com Integration's settings, so we can't open them
		// if that plugin is not available.
		if ( ! is_plugin_active( 'woocommerce-shipping-stamps/woocommmerce-shipping-stamps.php' ) ) {
			return $links_array;
		}

		$settings_url = admin_url( 'admin.php?page=wc-settings&tab=stamps' );

		array_unshift( $links_array, '<a href="' . $settings_url . '">Settings</a>' );

		return $links_array;
	}

}
