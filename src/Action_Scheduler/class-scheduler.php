<?php
/**
 * Handle events started by Action Scheduler.
 * Define the event name.
 *
 * @package brianhenryie/bh-wc-auto-purchase-stamps
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\Action_Scheduler;

use BrianHenryIE\WC_Auto_Purchase_Stamps\API\API_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Order;

/**
 * Invoke the API from an Action Scheduler event.
 */
class Scheduler {

	use LoggerAwareTrait;

	const ORDER_PAID_JOB_ACTION_NAME = 'bh_wc_auto_purchase_stamps_purchase_for_order';

	/**
	 * An instance of the plugin API to carry out the label purchasing.
	 *
	 * @var API_Interface
	 */
	protected API_Interface $api;

	/**
	 * Scheduler constructor.
	 *
	 * @param API_Interface   $api The plugin's main functions.
	 * @param LoggerInterface $logger A PSR logger.
	 */
	public function __construct( API_Interface $api, LoggerInterface $logger ) {
		$this->setLogger( $logger );
		$this->api = $api;
	}

	/**
	 * This function is hooked to the action used in the scheduler.
	 * When run, it checks is the order id valid, then proceeds to initiate purchasing the shipping label.
	 *
	 * @hooked bh_wc_auto_purchase_stamps_purchase_for_order
	 * @see Scheduler::ORDER_PAID_JOB_ACTION_NAME
	 *
	 * @param int $order_id The WooCommerce order id.
	 */
	public function purchase_stamps_for_order( int $order_id ): void {

		$order = wc_get_order( $order_id );

		if ( ! ( $order instanceof WC_Order ) ) {

			$message = 'No order found ' . $order_id;
			$this->logger->debug( $message, array( 'order_id' => $order_id ) );

			return;
		}

		$this->api->auto_purchase_stamps_for_order( $order );
	}

}


