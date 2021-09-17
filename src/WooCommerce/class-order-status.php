<?php

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce;

class Order_Status {

	/**
	 *
	 * Max length 20 characters: wc-shippingpurchased.
	 *
	 * @see https://developer.wordpress.org/reference/functions/register_post_status/#user-contributed-notes
	 */
	const SHIPPING_LABEL_PURCHASED_STATUS = 'shippingpurchased';

	const PRINTED_STATUS = 'printed';

	/**
	 * Register the order/post status with WordPress.
	 *
	 * Seems to be no harm registering the post status multiple times.
	 *
	 * @hooked woocommerce_init
	 * @see WooCommerce::init()
	 */
	public function register_status(): void {

		register_post_status(
			'wc-' . self::SHIPPING_LABEL_PURCHASED_STATUS,
			array(
				'label'                     => 'Shipping Label Purchased',
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Shipping Label Purchased <span class="count">(%s)</span>', 'Shipping Labels Purchased <span class="count">(%s)</span>' ),
			)
		);

		register_post_status(
			'wc-' . self::PRINTED_STATUS,
			array(
				'label'                     => 'Printed',
				'public'                    => true,
				'exclude_from_search'       => false,
				'show_in_admin_all_list'    => true,
				'show_in_admin_status_list' => true,
				'label_count'               => _n_noop( 'Printed <span class="count">(%s)</span>', 'Printed <span class="count">(%s)</span>' ),
			)
		);
	}

	/**
	 * Add "wc-shippingpurchased" to WooCommerce's list of statuses.
	 * Add "wc-printed" to WooCommerce's list of statuses.
	 *
	 * Adds the new order statuses after "Processing".
	 *
	 * @hooked wc_order_statuses
	 * @see wc_get_order_statuses()
	 *
	 * @param string[] $order_statuses WooCommerce order statuses.
	 * @return string[]
	 */
	public function add_order_status_to_woocommerce( $order_statuses ): array {

		$new_order_statuses = array();

		foreach ( $order_statuses as $key => $status ) {
			$new_order_statuses[ $key ] = $status;
			if ( 'wc-processing' === $key ) {
				$new_order_statuses[ 'wc-' . self::SHIPPING_LABEL_PURCHASED_STATUS ] = 'Shipping Label Purchased';
				$new_order_statuses[ 'wc-' . self::PRINTED_STATUS ]                  = 'Printed';
			}
		}
		return $new_order_statuses;
	}

	/**
	 * Add the status to the list considered "paid" when considered by WooCommerce and other plugins.
	 *
	 * @hooked woocommerce_order_is_paid_statuses
	 * @see wc_get_is_paid_statuses()
	 *
	 * @param string[] $statuses ['processing', completed'] and other custom statuses that apply to paid orders.
	 * @return string[]
	 */
	public function add_to_paid_status_list( $statuses ): array {
		$statuses[] = self::SHIPPING_LABEL_PURCHASED_STATUS;
		$statuses[] = self::PRINTED_STATUS;
		return $statuses;
	}


	/**
	 * WooCommerce's reports do not respect wc_get_is_paid_statuses() so we need to add the status here too.
	 *
	 * @hooked woocommerce_reports_order_statuses
	 * @see \WC_Admin_Report::get_order_report_data()
	 * @see wp-admin/admin.php?page=wc-reports
	 *
	 * @param false|string[] $order_status
	 *
	 * @return false|string[]
	 */
	public function add_to_reports_status_list( $order_status ) {

		// In the refund report it is false.
		if ( false === $order_status || ! is_array( $order_status ) ) {
			return $order_status;
		}

		// In all paid scenarios, there are at least 'completed', 'processing', 'on-hold' already in the list.
		if ( ! ( in_array( 'completed', $order_status, true )
				&& in_array( 'processing', $order_status, true )
				&& in_array( 'on-hold', $order_status, true )
		) ) {
			return $order_status;
		}

		// $this->logger->debug( 'Adding order status to reports status list', array( 'hooked' => 'woocommerce_reports_order_statuses' ) );

		$order_status[] = self::SHIPPING_LABEL_PURCHASED_STATUS;
		$order_status[] = self::PRINTED_STATUS;

		return $order_status;
	}
}
