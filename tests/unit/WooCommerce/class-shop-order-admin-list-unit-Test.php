<?php

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Auto_Purchase_Stamps\API\API_Interface;
use BrianHenryIE\WC_Auto_Purchase_Stamps\API\Settings_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce\Shop_Order_Admin_List
 */
class Shop_Order_Admin_List_Unit_Test extends \Codeception\Test\Unit {

	/**
	 * @covers ::register_bulk_action_print_4x6_stamps_labels_pdf
	 */
	public function test_register_bulk_action() {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();
		$sut      = new Shop_Order_Admin_List( $api, $settings, $logger );

		$result = $sut->register_bulk_action_print_4x6_stamps_labels_pdf( array() );

		$this->assertArrayHasKey( 'print_4x6_stamps_labels_pdf', $result, 'Changing this key requires changing the name of the hooked action for processing it.' );
		$this->assertEquals( 'Print 4x6 Stamps.com labels PDF', $result['print_4x6_stamps_labels_pdf'] );
	}

}
