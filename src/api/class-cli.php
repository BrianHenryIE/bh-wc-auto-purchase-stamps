<?php

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\api;

use WP_CLI;

class CLI {

	/** @var API_Interface */
	public static $api;

	/**
	 * wp stamps purchase 2265
	 */
	public function purchase( $args ) {

		$order_id = intval( $args[0] );

		if ( 0 === $order_id ) {
			return;
		}

		WP_CLI::log( 'purchase_stamps_for_order ' . $order_id );

		self::$api->purchase_stamps_for_order( $order_id );
	}

}
