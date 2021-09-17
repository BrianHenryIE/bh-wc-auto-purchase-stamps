<?php

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\API;

use BrianHenryIE\ColorLogger\ColorLogger;
use WC_Order;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Auto_Purchase_Stamps\API\API
 */
class API_WPUnit_Test extends \Codeception\TestCase\WPTestCase {


	/**
	 * Test the function that gets called by cron.
	 *
	 * @coveres ::auto_purchase_stamps_for_order
	 *
	 * Happy path.
	 */
	public function test_auto_purchase_stamps_for_order() {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array()
		);
		$api      = new API( $settings, $logger );

		$order = new WC_Order();
		$order->set_status( 'processing' );
		$order_id = $order->save();

		$api->auto_purchase_stamps_for_order( $order );

	}


	/**
	 * @covers ::auto_purchase_stamps_for_order
	 */
	public function test_auto_purchase_stamps_for_order_status_not_processing() {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );

		$api = new API( $settings, $logger );

		$order = new WC_Order();
		// Order status not processing.
		$order_status = 'pending';
		$order->set_status( $order_status );
		$order_id = $order->save();

		$api->auto_purchase_stamps_for_order( $order );

		$this->assertTrue( $logger->hasDebugThatContains( "Order $order_id has not been paid : $order_status" ) );
	}

	/**
	 *
	 * @covers ::purchase_stamps_for_order
	 *
	 * @param $order_id
	 */
	public function test_purchase_stamps_for_order() {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );

		$api = new API( $settings, $logger );

		$order = new WC_Order();
		$order->set_status( 'processing' );
		$order_id = $order->save();

		// $result = $api->purchase_stamps_for_order( $order_id );
	}
}
