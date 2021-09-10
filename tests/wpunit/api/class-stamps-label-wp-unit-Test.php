<?php

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\api;

use Psr\Log\NullLogger;

class Stamps_Label_WP_Unit_Tests extends \Codeception\TestCase\WPTestCase {


	public function test_dimensions() {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );

		$product = new \WC_Product();
		$product->set_height( 3 );
		$product->set_length( 4 );
		$product->set_width( 5 );
		$product->set_weight( 123 );
		$product->save();

		$order = new \WC_Order();
		$order->add_product( $product );
		$order->save();

		$sut = new Stamps_Label( $settings, $logger );

		update_option( 'woocommerce_weight_unit', 'oz' );

		$dimensions = $sut->get_order_dimensions( $order );

		$this->assertArrayHasKey( 'weight', $dimensions );

		$this->assertEquals( 123 / 16, $dimensions['weight'] );
	}
}
