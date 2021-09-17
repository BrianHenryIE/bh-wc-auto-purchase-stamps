<?php
/**
 * The plugin settings.
 *
 * A facade over the WooCommerce settings, additional settings for the logger, and some helper functions.
 *
 * @link       https://BrianHenryIE.com
 * @since      1.0.0
 *
 * @package    brianhenryie/wc-auto-purchase-stamps
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\API;

use BrianHenryIE\WC_Auto_Purchase_Stamps\Admin\Plugins_Page;
use BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce\Order_Status;
use BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce\Shop_Order_Admin_List;
use BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce_Shipping_Stamps\Stamps_Plugin_API;
use BrianHenryIE\WC_Auto_Purchase_Stamps\WP_Logger\API\Logger_Settings_Interface;
use BrianHenryIE\WC_Auto_Purchase_Stamps\WP_Logger\WooCommerce\WooCommerce_Logger_Interface;
use Psr\Log\LogLevel;

/**
 * The plugin's required settings, plus the logger's required settings, plus a marker interface so the logger knows it's a WooCommerce plugin.
 */
class Settings implements Settings_Interface, Logger_Settings_Interface, WooCommerce_Logger_Interface {

	const AUTO_PURCHASE_IS_ENABLED_OPTION_NAME         = 'bh_wc_auto_purchase_stamps_enabled';
	const ORDER_STATUS_AFTER_PURCHASE_OPTION_NAME      = 'bh_wc_auto_purchase_stamps_set_purchased_status';
	const ORDER_STATUS_AFTER_BULK_PRINTING_OPTION_NAME = 'bh_wc_auto_purchase_stamps_set_printed_status';
	const LOG_LEVEL_OPTION_NAME                        = 'bh_wc_auto_purchase_stamps_log_level';

	/**
	 * Is auto-purchasing enabled?
	 *
	 * Configured via a checkbox in the settings UI.
	 *
	 * @used-by Order::schedule_purchase_stamps_on_payment_complete()
	 * @used-by Order::on_bulk_order_processing()
	 * @used-by Order::maybe_schedule_purchase_stamps_action()
	 *
	 * @return bool
	 */
	public function is_auto_purchase_enabled(): bool {
		return 'yes' === get_option( self::AUTO_PURCHASE_IS_ENABLED_OPTION_NAME ) && is_plugin_active( 'woocommerce-shipping-stamps/woocommmerce-shipping-stamps.php' );
	}

	/**
	 * Returns what order status should be set after shipping labels are purchased.
	 * When auto-purchased, set to Shipping Label Purchased.
	 * When bulk printed, set to Printed.
	 *
	 * Auto-disables on production when "samples only" is enabled.
	 *
	 * @return string
	 */
	public function get_order_status_after_purchase(): ?string {

		// Do not change order status on production if the labels are not real.
		$samples_only = 'yes' === get_option( Stamps_Plugin_API::SAMPLES_ONLY_OPTION_NAME );
		if ( $samples_only && 'production' === wp_get_environment_type() ) {
			return null;
		}

		$status = get_option( self::ORDER_STATUS_AFTER_PURCHASE_OPTION_NAME, Order_Status::SHIPPING_LABEL_PURCHASED_STATUS );

		return $status;
	}

	/**
	 * The order status to set after printing.
	 *
	 * Auto-disables on production when "samples only" is enabled.
	 *
	 * @used-by Shop_Order_Admin_List::generate_print_4x6_stamps_labels_pdf()
	 *
	 * @return ?string
	 */
	public function get_order_status_after_bulk_printing(): ?string {

		// Do not change order status on production if the labels are not real.
		$samples_only = ( 'yes' === get_option( Stamps_Plugin_API::SAMPLES_ONLY_OPTION_NAME ) );
		if ( $samples_only && 'production' === wp_get_environment_type() ) {
			return null;
		}

		$status = get_option( self::ORDER_STATUS_AFTER_BULK_PRINTING_OPTION_NAME, Order_Status::PRINTED_STATUS );

		return $status;
	}

	/**
	 * The plugin name, used for logger admin notices.
	 *
	 * @used-by Logger_Settings_Interface
	 *
	 * @return string
	 */
	public function get_plugin_name(): string {
		return 'Auto Purchase Stamps.com';
	}

	/**
	 * The plugin slug - used for logger filenames.
	 *
	 * @used-by Logger_Settings_Interface
	 *
	 * @return string
	 */
	public function get_plugin_slug(): string {
		return 'bh-wc-auto-purchase-stamps';
	}

	/**
	 * A PSR log level (or "none"/any other string for none). How detailed logs should be.
	 *
	 * @see LogLevel
	 *
	 * @used-by Logger_Settings_Interface
	 *
	 * @return string
	 */
	public function get_log_level(): string {
		return get_option( self::LOG_LEVEL_OPTION_NAME, LogLevel::NOTICE );
	}

	/**
	 * The plugin basename
	 *
	 * @used-by Plugins_Page::row_meta()
	 * @used-by Plugins_Page::action_links()
	 *
	 * @used-by Logger_Settings_Interface
	 *
	 * @return string
	 */
	public function get_plugin_basename(): string {
		return 'bh-wc-auto-purchase-stamps/bh-wc-auto-purchase-stamps.php';
	}

}
