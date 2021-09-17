<?php
/**
 * Add settings at the bottom of the existing Stamps.com settings page.
 * * Enabled auto-purchase
 * * status after auto-purchase
 * * status after bulk print
 * * log level
 *
 * @see /wp-admin/admin.php?page=wc-settings&tab=stamps
 *
 * TODO:
 * * It would be nice to show what plugins have filters added.
 *
 * @package           brianhenryie/wc-auto-purchase-stamps
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce_Shipping_Stamps;

use BrianHenryIE\WC_Auto_Purchase_Stamps\API\Settings;
use Psr\Log\LogLevel;

/**
 * Hook into wc_settings_tab_stamps to append additional settings.
 *
 * @see \WC_Stamps_Settings
 */
class Stamps_Settings {

	/**
	 * Append the settings for this plugin at the bottom of the Stamps.com settings page.
	 *
	 * @hooked wc_settings_tab_stamps
	 *
	 * @see \WC_Stamps_Settings
	 * @see /wp-admin/admin.php?page=wc-settings&tab=stamps
	 *
	 * @param array<string, mixed> $setting_fields The existing Stamps.com plugin's settings.
	 *
	 * @return array<string, mixed>
	 */
	public function add_plugin_settings( $setting_fields ) {

		$setting_fields['bh_wc_auto_purchase'] = array(
			'name' => __( 'Auto Purchasing Stamps', 'bh-wc-auto-purchase-stamps' ),
			'type' => 'title',
			'desc' => __( 'When an order is marked as paid, its shipping label will be purchased.', 'bh-wc-auto-purchase-stamps' ),
		);

		$log_levels        = array( 'none', LogLevel::ERROR, LogLevel::WARNING, LogLevel::NOTICE, LogLevel::INFO, LogLevel::DEBUG );
		$log_levels_option = array();
		foreach ( $log_levels as $log_level ) {
			$log_levels_option[ $log_level ] = ucfirst( $log_level );
		}

		$administrator_notice = '';
		if ( current_user_can( 'administrator' ) ) {
			global $wp_filter;
			if ( ! array_key_exists( 'bh_wc_auto_purchase_stamps_disable', $wp_filter ) ) {
				$administrator_notice = '<br/>' . __( 'Granular control can be achieved with the <code>bh_wc_auto_purchase_stamps_disable</code> filter.', 'bh-wc-auto-purchase-stamps' );
			} else {
				$administrator_notice = '<br/>' . __( 'This setting is also being controlled via the <code>bh_wc_auto_purchase_stamps_disable</code> filter.', 'bh-wc-auto-purchase-stamps' );
			}
		}

		$setting_fields[ Settings::AUTO_PURCHASE_IS_ENABLED_OPTION_NAME ] = array(
			'title'   => __( 'Enable auto-purchase', 'bh-wc-auto-purchase-stamps' ),
			'type'    => 'checkbox',
			'desc'    => __( 'When an order is marked as paid, purchase its shipping label.', 'bh-wc-auto-purchase-stamps' ) . $administrator_notice,
			'id'      => 'bh_auto_purchase_stamps_enabled',
			'default' => 'no',
		);

		$paid_statuses                = array();
		$paid_statuses['donotchange'] = 'Do not change status';

		$order_status_names = wc_get_order_statuses();
		foreach ( wc_get_is_paid_statuses() as $status ) {
			if ( isset( $order_status_names[ "wc-{$status}" ] ) ) {
				$paid_statuses[ $status ] = $order_status_names[ "wc-{$status}" ];
			}
		}

		$samples_only_note = '';
		if ( 'yes' === get_option( Stamps_Plugin_API::SAMPLES_ONLY_OPTION_NAME ) && 'production' === wp_get_environment_type() ) {
			$samples_only_note = __( 'No status change will be made while <b>Samples only</b> is enabled.', 'bh-wc-auto-purchase-stamps' );
		}

		$setting_fields[ Settings::ORDER_STATUS_AFTER_PURCHASE_OPTION_NAME ] = array(
			'name'    => __( 'Set order status after auto-purchase', 'bh-wc-auto-purchase-stamps' ),
			'desc'    => $samples_only_note,
			'id'      => Settings::ORDER_STATUS_AFTER_PURCHASE_OPTION_NAME,
			'type'    => 'select',
			'default' => 'shippingpurchased',
			'options' => $paid_statuses,
		);

		$orders_url  = admin_url( 'edit.php?post_type=shop_order' );
		$orders_link = "<a href=\"$orders_url\">" . __( 'orders screen', 'bh-wc-auto-purchase-stamps' ) . '</a>';

		$setting_fields[ Settings::ORDER_STATUS_AFTER_BULK_PRINTING_OPTION_NAME ] = array(
			'name'    => __( 'Set order status after bulk printing', 'bh-wc-auto-purchase-stamps' ),
			'desc'    => __( 'Labels can be printed from the Bulk Actions menu on the', 'bh-wc-auto-purchase-stamps' ) . " $orders_link. ",
			'id'      => Settings::ORDER_STATUS_AFTER_BULK_PRINTING_OPTION_NAME,
			'type'    => 'select',
			'default' => 'completed',
			'options' => $paid_statuses,
		);

		$url       = admin_url( 'admin.php?page=bh-wc-auto-purchase-stamps-logs' );
		$logs_link = "<a href=\"$url\">" . __( 'View Logs', 'bh-wc-auto-purchase-stamps' ) . '</a>';

		$setting_fields[ Settings::LOG_LEVEL_OPTION_NAME ] = array(
			'title'   => __( 'Log Level (for auto-purchase)', 'bh-wc-auto-purchase-stamps' ),
			'label'   => __( 'Enable Logging', 'bh-wc-auto-purchase-stamps' ),
			'type'    => 'select',
			'options' => $log_levels_option,
			'desc'    => __( 'Increasingly detailed levels of logs.', 'bh-wc-auto-purchase-stamps' ) . " $logs_link.",
			'default' => 'notice',
			'id'      => 'bh_auto_purchase_stamps_log_level',
		);

		$setting_fields['bh_auto_purchase_stamps_settings_end'] = array(
			'type' => 'sectionend',
		);

		return $setting_fields;
	}

}
