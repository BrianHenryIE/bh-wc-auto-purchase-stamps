<?php

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\woocommerce;

use BrianHenryIE\WC_Auto_Purchase_Stamps\api\API;
use BrianHenryIE\WC_Auto_Purchase_Stamps\api\Settings_Interface;
use Psr\Log\LoggerInterface;


class Order {

	/**
	 * @var Settings_Interface
	 */
	protected $settings;

	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	/**
	 * Order constructor.
	 *
	 * @param Settings_Interface $settings
	 * @param LoggerInterface    $logger
	 */
	public function __construct( $settings, $logger ) {
		$this->settings = $settings;
		$this->logger   = $logger;
	}

	/**
	 * When an order is set to processing, purchase its shipping label.
	 *
	 * do_action( 'woocommerce_order_status_changed', $this->get_id(), $status_transition['from'], $status_transition['to'], $this );
	 *
	 * @hooked woocommerce_order_status_changed
	 *
	 * @param int    $order_id
	 * @param string $status_from
	 * @param string $status_to
	 */
	public function schedule_purchase_stamps_on_paid( $order_id, $status_from, $status_to ) {

		if ( 'processing' === $status_to && 'processing' !== $status_from ) {

			$args = array( array( $order_id ) );

			wp_schedule_single_event( time() - 60, API::ORDER_PAID_CRON_JOB_NAME, $args );
		}
	}


	/**
	 * TODO:
	 *
	 * Fires when the order status is set to processing as part of a bulk update on the order list page.
	 *
	 * @hooked admin_action_mark_processing
	 */
	public function on_bulk_order_processing() {

		// The bulk update should have an array of post (order) ids.
		if ( ! isset( $_REQUEST['post'] ) || ! is_array( $_REQUEST['post'] ) ) {
			return;
		}

		$args = array( $_REQUEST['post'] );

		wp_schedule_single_event( time() - 60, API::ORDER_PAID_CRON_JOB_NAME, $args );

	}
}
