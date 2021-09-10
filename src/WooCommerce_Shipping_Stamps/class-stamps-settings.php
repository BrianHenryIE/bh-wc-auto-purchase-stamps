<?php


namespace BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce_Shipping_Stamps;

use BrianHenryIE\WC_Auto_Purchase_Stamps\API\Settings;
use Psr\Log\LogLevel;

class Stamps_Settings {

	/**
	 * Append the settings for this plugin at the bottom of the Stamps.com settings page.
	 *
	 * @hooked wc_settings_tab_stamps
	 *
	 * @see \WC_Stamps_Settings
	 * @see /wp-admin/admin.php?page=wc-settings&tab=stamps
	 *
	 * @param array $setting_fields The existing Stamps.com plugin's settings.
	 *
	 * @return array
	 */
	public function add_plugin_settings( $setting_fields ) {

		// Mailtype: Priority mail
		// Add-ons:

		$setting_fields['bh_auto_purchase'] = array(
			'name' => __( 'Auto Purchasing Stamps', 'bh-wc-auto-purchase-stamps' ),
			'type' => 'title',
			'desc' => __( 'When an order is marked processing, its shipping label will be purchased.', 'bh-wc-auto-purchase-stamps' ),
		);

		// get_option('wc_settings_stamps_auto_purchase' );

		$log_levels        = array( 'none', LogLevel::ERROR, LogLevel::WARNING, LogLevel::NOTICE, LogLevel::INFO, LogLevel::DEBUG );
		$log_levels_option = array();
		foreach ( $log_levels as $log_level ) {
			$log_levels_option[ $log_level ] = ucfirst( $log_level );
		}

		$setting_fields['bh_auto_purchase_stamps_enabled'] = array(
			'title'   => __( 'Enable auto-purchase', 'bh-wc-auto-purchase-stamps' ),
			'type'    => 'checkbox',
			'desc'    => __( 'When an order is marked processing, purchase its shipping label.', 'bh-wc-auto-purchase-stamps' ),
			// 'desc_tip' => __( 'Labels purchased follow the settings above.', 'bh-wc-auto-purchase-stamps' ),
			'id'      => 'bh_auto_purchase_stamps_enabled',
			'default' => 'no',
		);

		// Tell users tracking numbers will not be added if Create Samples only is chosen

		$paid_statuses                = array();
		$paid_statuses['donotchange'] = 'Do not change status';

		$order_status_names = wc_get_order_statuses();
		foreach ( wc_get_is_paid_statuses() as $status ) {
			if ( isset( $order_status_names[ "wc-{$status}" ] ) ) {
				$paid_statuses[ $status ] = $order_status_names[ "wc-{$status}" ];
			}
		}

		$setting_fields[ Settings::ORDER_STATUS_AFTER_PURCHASE_OPTION_NAME ] = array(
			'name'    => __( 'Set order status after auto-purchase', 'bh-wc-auto-purchase-stamps' ),
			'desc'    => __( '', 'bh-wc-auto-purchase-stamps' ),
			'id'      => Settings::ORDER_STATUS_AFTER_PURCHASE_OPTION_NAME,
			'type'    => 'select',
			'default' => 'shippingpurchased',
			'options' => $paid_statuses,
		);

		$setting_fields[ Settings::ORDER_STATUS_AFTER_BULK_PRINTING_OPTION_NAME ] = array(
			'name'    => __( 'Set order status after bulk printing', 'bh-wc-auto-purchase-stamps' ),
			'desc'    => __( 'Labels can be printed from the Bulk Actions menu on the orders screen.', 'bh-wc-auto-purchase-stamps' ),
			'id'      => Settings::ORDER_STATUS_AFTER_BULK_PRINTING_OPTION_NAME,
			'type'    => 'select',
			'default' => 'completed',
			'options' => $paid_statuses,
		);

		// Tell users it will not happen if Create Samples only is chosen

		$setting_fields['bh_auto_purchase_stamps_log_level'] = array(
			'title'    => __( 'Log Level (for auto-purchase)', 'bh-wc-auto-purchase-stamps' ),
			'label'    => __( 'Enable Logging', 'bh-wc-auto-purchase-stamps' ),
			'type'     => 'select',
			'options'  => $log_levels_option,
			'desc'     => __( 'Increasing levels of logs.', 'bh-wc-auto-purchase-stamps' ),
			'desc_tip' => true,
			'default'  => 'notice',
			'id'       => 'bh_auto_purchase_stamps_log_level',
		);

		// TODO: Add a link to the logs.

		// TODO: It would be nice to have rules for shipping options here, similar to the rules in Conditional Shipping and Payments.

		$setting_fields['bh_auto_purchase_stamps_settings_end'] = array(
			'type' => 'sectionend',
		);

		return $setting_fields;
	}
}
