<?php
/**
 * Tests
 *
 * @package BH_WC_Address_Validation
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce;

use BrianHenryIE\WC_Auto_Purchase_Stamps\API\API_Interface;
use BrianHenryIE\WC_Auto_Purchase_Stamps\API\Settings_Interface;
use Psr\Log\NullLogger;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce\Order_Status
 */
class Order_Status_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		\WP_Mock::tearDown();
	}

	public function test_const_printed() {
		$this->assertEquals( 'printed', Order_Status::PRINTED_STATUS, 'Changing the status name may require a migration function.' );
	}

	public function test_const_purchased() {
		$this->assertEquals( 'shippingpurchased', Order_Status::SHIPPING_LABEL_PURCHASED_STATUS, 'Changing the status name may require a migration function.' );
	}


	/**
	 * @covers ::register_status
	 */
	public function test_register_status() {

		\WP_Mock::userFunction(
			'_n_noop',
			array(
				'return_arg' => 0,
			)
		);

		\WP_Mock::userFunction(
			'register_post_status',
			array(
				'args'  => array( 'wc-printed', \WP_Mock\Functions::type( 'array' ) ),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'register_post_status',
			array(
				'args'  => array( 'wc-shippingpurchased', \WP_Mock\Functions::type( 'array' ) ),
				'times' => 1,
			)
		);

		$sut = new Order_Status();

		$sut->register_status();
	}

	/**
	 * @covers ::add_order_status_to_woocommerce
	 */
	public function test_add_to_woocommerce() {

		$sut = new Order_Status();

		$result = $sut->add_order_status_to_woocommerce( array( 'wc-processing' => 'Processing' ) );

		$this->assertArrayHasKey( 'wc-shippingpurchased', $result );
		$this->assertArrayHasKey( 'wc-printed', $result );

		$this->assertEquals( 'Shipping Label Purchased', $result['wc-shippingpurchased'] );
		$this->assertEquals( 'Printed', $result['wc-printed'] );

	}

	/**
	 * @covers ::add_to_paid_status_list
	 */
	public function test_add_to_paid_status_list() {

		$sut = new Order_Status();

		$result = $sut->add_to_paid_status_list( array() );

		$this->assertContains( 'shippingpurchased', $result );
		$this->assertContains( 'printed', $result );
	}

	/**
	 * The bad-address status should only be added to reports with paid statuses.
	 *
	 * @covers ::add_to_reports_status_list
	 */
	public function test_reports_status_filter_empty() {

		$sut = new Order_Status();

		$result = $sut->add_to_reports_status_list( array() );

		$this->assertEmpty( $result );

	}

	/**
	 * @covers ::add_to_reports_status_list
	 */
	public function test_reports_status_filter() {

		$sut = new Order_Status();

		$result = $sut->add_to_reports_status_list( array( 'completed', 'processing', 'on-hold' ) );

		$this->assertContains( Order_Status::SHIPPING_LABEL_PURCHASED_STATUS, $result );
		$this->assertContains( Order_Status::PRINTED_STATUS, $result );

	}

}
