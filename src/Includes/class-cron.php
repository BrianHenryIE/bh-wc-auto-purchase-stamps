<?php
/**
 * This plugin uses cron jobs so the heavy lifting does not slow down page loads
 * for customers. And to allow retrying when jobs do not complete (i.e. if the
 * shipping label cannot automatically be purchased right now).
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\includes;

use BrianHenryIE\WC_Auto_Purchase_Stamps\api\API_Interface;


class Cron {

	/**
	 * @var API_Interface $api
	 */
	protected $api;


	public function __construct( $api ) {

		$this->api = $api;

	}

	/**
	 * @hoooked bh-wc-auto-purchase-stamps-process-order
	 *
	 * @param int[] $order_ids The order id passed by cron (in an array... indexed?)
	 *
	 * @see API::ORDER_PAID_CRON_JOB_NAME
	 */
	public function auto_purchase_stamps_for_order( $order_ids ) {

		foreach ( $order_ids as $order_id ) {
			$this->api->auto_purchase_stamps_for_order( $order_id );
		}
	}

}


