<?php

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\API;

use BrianHenryIE\WC_Auto_Purchase_Stamps\WP_Logger\API\Logger_Settings_Interface;
use Psr\Log\LogLevel;
use BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce\Order_Status;

class Settings implements Settings_Interface, Logger_Settings_Interface {

	public function is_enabled(): bool {
		return ( 'yes' === get_option( 'bh_auto_purchase_stamps_enabled' ) ) ? true : false;
	}

	/**
	 *
	 * Auto-disables when "samples only" is enabled.
	 *
	 * @return string
	 */
	public function order_status_after_purchase(): ?string {
		$status = get_option( self::ORDER_STATUS_AFTER_PURCHASE_OPTION_NAME );

		$samples_only = ( 'yes' === get_option( 'wc_settings_stamps_sample_only' ) );

		// Do not change order status on production if the labels are not real.
		if ( $samples_only && 'production' === wp_get_environment_type() ) {
			return null;
		}

		// Default.
		if ( empty( $status ) ) {
			$status = Order_Status::SHIPPING_LABEL_PURCHASED_STATUS;
		}

		return $status;
	}

	public function order_status_after_bulk_printing(): ?string {
		$status = get_option( self::ORDER_STATUS_AFTER_BULK_PRINTING_OPTION_NAME );

		$samples_only = ( 'yes' === get_option( 'wc_settings_stamps_sample_only' ) );

		// Do not change order status on production if the labels are not real.
		if ( $samples_only && 'production' === wp_get_environment_type() ) {
			return null;
		}

		if ( empty( $status ) ) {
			$status = 'complete';
		}

		return $status;

	}

	public function get_plugin_name(): string {
		return 'Auto Purchase Stamps.com';
	}

	/**
	 * @return string
	 */
	public function get_plugin_slug(): string {
		return 'bh-wc-auto-purchase-stamps';
	}

	/**
	 * @return string
	 */
	public function get_log_level(): string {
		return get_option( 'bh_auto_purchase_stamps_log_level', LogLevel::NOTICE );
	}

	public function get_plugin_basename(): string {
		return 'bh-wc-auto-purchase-stamps/bh-wc-auto-purchase-stamps.php';
	}

	public function get_plugin_version(): string {
		return '1.1.0';
	}
}
