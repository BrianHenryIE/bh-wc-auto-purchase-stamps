<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    BH_WC_Auto_Purchase_Stamps
 * @subpackage BH_WC_Auto_Purchase_Stamps/admin
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\admin;

use BrianHenryIE\WC_Auto_Purchase_Stamps\api\Settings_Interface;
use BrianHenryIE\WC_Auto_Purchase_Stamps\WPTRT\AdminNotices\Notices;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    BH_WC_Auto_Purchase_Stamps
 * @subpackage BH_WC_Auto_Purchase_Stamps/admin
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */
class Admin {


	/**
	 * @var Notices $notices Utility by WordPress Theme Review Team for adding admin notices.
	 */
	protected $notices;

	protected Settings_Interface $settings;

	/**
	 * Admin constructor.
	 *
	 * @param $plugin_name
	 * @param $version
	 * @param null        $notices
	 */
	public function __construct( Settings_Interface $settings, $notices = null ) {

		$this->settings = $settings;
		$this->notices  = isset( $notices ) ? $notices : new Notices();

		$this->notices->boot();
	}

	/**
	 * If the plugin private-uploads is not installed, show a notice in the admin UI.
	 *
	 * @hooked admin_init
	 */
	public function check_required_plugins_active() {

		// TODO: WooCommerce?

		// Note the misspelling.
		if ( ! is_plugin_active( 'woocommerce-shipping-stamps/woocommmerce-shipping-stamps.php' ) ) {

			if ( is_plugin_inactive( 'woocommerce-shipping-stamps/woocommmerce-shipping-stamps.php' ) ) {

				// If the plugin is installed but inactive, link to a search for it on plugins.php.
				$shipment_tracking_url = admin_url(
					add_query_arg(
						array(
							's' => rawurlencode( 'Stamps.com API integration' ),
						),
						'plugins.php'
					)
				);

			} else {

				// If the plugin is not installed, link to WooCommerce.com to purchase it.
				$shipment_tracking_url = 'https://woocommerce.com/products/shipment-tracking/';
				$shipment_tracking_url = add_query_arg(
					array(
						'wccom-site'          => site_url(),
						'wccom-back'          => rawurlencode( '/wp-admin/admin.php?page=' . $this->get_plugin_name() ),
						'wccom-woo-version'   => WC_VERSION,
						'wccom-connect-nonce' => wp_create_nonce( 'connect' ),
					),
					$shipment_tracking_url
				);
			}

			// TODO: Translation string is wrong.
			$this->notices->add(
				'woocommerce_shipping_stamps_missing',
				false,
				__( '<em>Missing plugin WooCommerce Stamps.com API integration</em> <a href="' . $shipment_tracking_url . '">required</a> to purchase shipping the labels with Auto Purchase Stamps.', 'bh-wc-auto-purchase-stamps' ),
				array(
					'scope'         => 'user',
					'type'          => 'warning',
					'option_prefix' => 'bh_wc_auto_purchase_stamps',
					'capability'    => 'activate_plugins',
					'screens'       => array( 'plugins' ),
				)
			);
		}

		$private_uploads_plugin_slug = 'private-uploads/private-uploads.php';
		if ( ! is_plugin_active( $private_uploads_plugin_slug ) ) {

			if ( file_exists( WP_PLUGIN_DIR . '/' . $private_uploads_plugin_slug ) ) {

				// If the plugin is installed but inactive, link to a search for it on plugins.php.
				$required_url = admin_url(
					add_query_arg(
						array(
							's' => rawurlencode( 'Private Uploads' ),
						),
						'plugins.php'
					)
				);

			} else {

				$required_url = admin_url( 'plugin-install.php?s=Private+Uploads&tab=search&type=term&open-plugin-details-modal=private-uploads' );
			}
			// Scoped to user so all admins have to acknowledge the problem.
			// TODO: Add a link around private-uploads.
			// TODO: Figure out which `'screens' => [],` are appropriate.

			$this->notices->add(
				'private_uploads_missing',
				false,
				__( 'Missing plugin <em>Private Uploads</em> should be <a href="' . $required_url . '">installed</a> so shipping labels auto-purchased and saved by <em>Auto Purchase Stamps</em> are not publicly downloadable.', 'bh-wc-auto-purchase-stamps' ),
				array(
					'scope'         => 'user',
					'type'          => 'warning',
					'option_prefix' => 'bh_wc_auto_purchase_stamps',
					'capability'    => 'activate_plugins',
					'screens'       => array( 'plugins' ),
				)
			);

		}
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		// wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/bh-wc-auto-purchase-stamps-admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		// TODO: only needed on plugin-install.php when open-plugin-details-modal is present.
		if ( ! isset( $_GET['open-plugin-details-modal'] ) ) {
			return;
		}

		wp_enqueue_script( $this->settings->get_plugin_slug(), plugin_dir_url( __FILE__ ) . 'js/bh-wc-auto-purchase-stamps-admin.js', array( 'jquery' ), $this->settings->get_plugin_version(), false );
	}

}
