<?php
/**
 * The plugin page output of the plugin.
 *
 * @link
 * @since      1.0.0
 *
 * @package    brianhenryie/wc-auto-purchase-stamps
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\Admin;

/**
 * This class adds a `Settings` link on the plugins.php page, when possible.
 */
class Plugins_Page {

	/**
	 * Add link to Settings page in plugins.php list.
	 *
	 * @hooked plugin_action_links_{basename}
	 *
	 * @param array<int|string, string>  $links_array The existing plugin links (usually "Deactivate"). May or may not be indexed with a string.
	 * @param string                     $plugin_file The plugin basename.
	 * @param array<string, string|bool> $plugin_data The parsed plugin header data.
	 * @param string                     $context 'all'|'active'|'inactive'...
	 * @return array<int|string, string> The links to display below the plugin name on plugins.php.
	 */
	public function action_links( array $links_array, string $plugin_file, array $plugin_data, string $context ): array {

		// This plugin's settings are appended to the Stamps.com Integration's settings, so we can't open them
		// if that plugin is not available.
		if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) || ! is_plugin_active( 'woocommerce-shipping-stamps/woocommmerce-shipping-stamps.php' ) ) {
			return $links_array;
		}

		$settings_url = admin_url( 'admin.php?page=wc-settings&tab=stamps' );

		array_unshift( $links_array, '<a href="' . $settings_url . '">Settings</a>' );

		return $links_array;
	}

	/**
	 * Add a link to login to stamps
	 *
	 * @hooked plugin_row_meta
	 *
	 * @see https://rudrastyh.com/wordpress/plugin_action_links-plugin_row_meta.html
	 *
	 * @param array<int|string, string>  $plugin_meta The meta information/links displayed by the plugin description.
	 * @param string                     $plugin_file_name The plugin filename to match when filtering.
	 * @param array<string, string|bool> $plugin_data Associative array including PluginURI, slug, Author, Version.
	 * @param string                     $status The plugin status, e.g. 'Inactive'.
	 *
	 * @return array<int|string, string> The filtered $plugin_meta.
	 */
	public function row_meta( array $plugin_meta, string $plugin_file_name, array $plugin_data, string $status ):array {

		if ( 'bh-wc-auto-purchase-stamps/bh-wc-auto-purchase-stamps.php' !== $plugin_file_name ) {
			return $plugin_meta;
		}

		if ( is_plugin_active( 'woocommerce-shipping-stamps/woocommmerce-shipping-stamps.php' ) ) {
			$plugin_meta[] = '<a target="_blank" href="' . admin_url( '?stamps_redirect=WebClientHome' ) . '">Stamps.com</a>';
		} else {
			$plugin_meta[] = '<a target="_blank" href="https://print.stamps.com/">Stamps.com</a>';
		}

		return $plugin_meta;
	}
}
