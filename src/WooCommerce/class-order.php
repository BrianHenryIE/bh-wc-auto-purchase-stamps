<?php

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce;

use ActionScheduler_Action;
use BrianHenryIE\WC_Auto_Purchase_Stamps\Action_Scheduler\Scheduler;
use BrianHenryIE\WC_Auto_Purchase_Stamps\API\Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;


class Order {

	use LoggerAwareTrait;

	/**
	 * Used to determine if maybe nothing should be done.
	 *
	 * @see Settings_Interface::is_auto_purchase_enabled()
	 *
	 * @var Settings_Interface
	 */
	protected $settings;

	/**
	 * Order constructor.
	 *
	 * @param Settings_Interface $settings The plugin settings.
	 * @param LoggerInterface    $logger A PSR logger.
	 */
	public function __construct( Settings_Interface $settings, LoggerInterface $logger ) {
		$this->settings = $settings;
		$this->setLogger( $logger );
	}

	/**
	 * When an order is marked paid, purchase its shipping label.
	 *
	 * @hooked woocommerce_order_status_changed
	 * @see WC_Order::status_transition()
	 *
	 * @param int    $order_id The WooCommerce order id.
	 * @param string $status_from The previous status.
	 * @param string $status_to The new status.
	 */
	public function schedule_purchase_stamps_on_status_change_to_paid( int $order_id, string $status_from, string $status_to ): void {

		if ( ! $this->settings->is_auto_purchase_enabled() ) {
			return;
		}

		if ( ! in_array( $status_from, wc_get_is_paid_statuses(), true ) && in_array( $status_to, wc_get_is_paid_statuses(), true ) ) {
			$this->maybe_schedule_purchase_stamps_action( $order_id );
		}
	}

	/**
	 * When the payment complete action is fired, schedule the auto-purchase event.
	 *
	 * @hooked woocommerce_payment_complete
	 * @see WC_Order::payment_complete()
	 *
	 * @param int $order_id The WooCommerce order id.
	 */
	public function schedule_purchase_stamps_on_payment_complete( $order_id ): void {

		if ( ! $this->settings->is_auto_purchase_enabled() ) {
			return;
		}

		$this->maybe_schedule_purchase_stamps_action( $order_id );
	}


	/**
	 * When the order status is set to processing as part of a bulk update on the order list page, schedule the auto-purchase event for each order.
	 *
	 * @hooked admin_action_mark_processing
	 * @see \WC_Admin_List_Table_Orders::define_bulk_actions()
	 * @see wp-admin/admin.php
	 *
	 * TODO: Nonce verification.
	 */
	public function on_bulk_order_processing(): void {

		if ( ! $this->settings->is_auto_purchase_enabled() ) {
			return;
		}

		// The bulk update should have an array of post (order) ids.
		if ( ! isset( $_REQUEST['post'] ) || ! is_array( $_REQUEST['post'] ) ) {
			return;
		}

		$order_ids = array_map( 'intval', $_REQUEST['post'] );

		foreach ( $order_ids as $order_id ) {
			$this->maybe_schedule_purchase_stamps_action( $order_id );
		}

	}

	/**
	 * Checks is there already a purchase job scheduled for this order id, and schedules one if there is not.
	 *
	 * @param int $order_id The WooCommerce order id.
	 */
	protected function maybe_schedule_purchase_stamps_action( int $order_id ): void {

		/**
		 * Existing auto-purchase-stamps actions scheduled.
		 *
		 * @var ActionScheduler_Action[] $scheduled_actions
		 */
		$scheduled_actions = as_get_scheduled_actions( array( 'hook' => Scheduler::ORDER_PAID_JOB_ACTION_NAME ) );
		$already_scheduled = array_reduce(
			$scheduled_actions,
			function( bool $carry, ActionScheduler_Action $scheduled_action ) use ( $order_id ) {

				return $carry || $order_id === $scheduled_action->get_args()[0];

			},
			false
		);

		if ( $already_scheduled ) {
			return;
		}

		$args = array( $order_id );
		as_enqueue_async_action( Scheduler::ORDER_PAID_JOB_ACTION_NAME, $args, 'bh_wc_auto_purchase_stamps' );
	}
}
