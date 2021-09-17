<?php

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\Action_Scheduler;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Auto_Purchase_Stamps\API\API_Interface;
use BrianHenryIE\WC_Auto_Purchase_Stamps\API\Settings_Interface;
use Codeception\Stub\Expected;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Auto_Purchase_Stamps\Action_Scheduler\Scheduler
 */
class Scheduler_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	public function test_construct() {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty( API_Interface::class );

		$sut = new Scheduler( $api, $logger );

		$this->assertInstanceOf( Scheduler::class, $sut );
	}


	/**
	 * No order found 123
	 *
	 * @covers ::purchase_stamps_for_order
	 */
	public function test_purchase_stamps_for_order_bad_order_id() {

		$logger = new ColorLogger();
		$api    = $this->makeEmpty( API_Interface::class );

		$sut = new Scheduler( $api, $logger );

		$sut->purchase_stamps_for_order( 123 );

		$this->assertTrue( $logger->hasDebugThatContains( 'No order found 123' ) );
	}


	/**
	 * @covers ::purchase_stamps_for_order
	 */
	public function test_purchase_stamps_for_order_happy_path() {

		$order    = new \WC_Order();
		$order_id = $order->save();

		$logger = new ColorLogger();

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'auto_purchase_stamps_for_order' => Expected::once(
					function( $order_id ) {
						return array(); }
				),
			)
		);

		$sut = new Scheduler( $api, $logger );

		$sut->purchase_stamps_for_order( $order_id );

	}
}
