<?php
/**
 * CLI access to the main plugin functions.
 *
 * @link       https://BrianHenryIE.com
 * @since      1.0.0
 *
 * @package    brianhenryie/wc-auto-purchase-stamps
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\API;

use WC_Order;
use WP_CLI;

class CLI {

	/** @var API_Interface */
	public API_Interface $api;

	public function __construct( API_Interface $api ) {
		$this->api = $api;
	}

	/**
	 * wp stamps purchase 2265
	 *
	 * TODO: Add force argument
	 *
	 * @param string[] $args The command line arguments.
	 */
	public function purchase( array $args ): void {

		$order_id = intval( $args[0] );

		if ( 0 === $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );

		if ( ! ( $order instanceof WC_Order ) ) {

			$message = 'No order found ' . $order_id;
			// $this->logger->debug( $message, $context );

			return;
		}

		WP_CLI::log( 'purchase_stamps_for_order ' . $order_id );

		$result = $this->api->purchase_stamps_for_order( $order );

		// label purchased with tracking 123 and 4x6 pdf at /relative path...
	}

}
