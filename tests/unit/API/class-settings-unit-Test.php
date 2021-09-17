<?php
/**
 *
 * Maybe add in a check for `wc_is_order_status( $status )`.
 *
 * @package brianhenryie/bh-wc-auto-purchase-stamps
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\API;

use BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce\Order_Status;
use BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce_Shipping_Stamps\Stamps_Plugin_API;
use Psr\Log\LogLevel;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Auto_Purchase_Stamps\API\Settings
 */
class Settings_Unit_Test extends \Codeception\Test\Unit {

	protected function setup() : void {
		parent::setUp();
		\WP_Mock::setUp();
	}

	public function tearDown(): void {
		\WP_Mock::tearDown();
		parent::tearDown();
	}

	/**
	 * @covers ::is_auto_purchase_enabled
	 */
	public function test_is_enabled_true() {

		\WP_Mock::userFunction(
			'is_plugin_active',
			array(
				'args'   => array( 'woocommerce-shipping-stamps/woocommmerce-shipping-stamps.php' ),
				'return' => true,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( Settings::AUTO_PURCHASE_IS_ENABLED_OPTION_NAME ),
				'return' => 'yes',
				'times'  => 1,
			)
		);

		$sut = new Settings();

		$result = $sut->is_auto_purchase_enabled();

		$this->assertTrue( $result );
	}

	/**
	 * @covers ::is_auto_purchase_enabled
	 */
	public function test_is_enabled_false_option() {

		\WP_Mock::userFunction(
			'is_plugin_active',
			array(
				'args'   => array( 'woocommerce-shipping-stamps/woocommmerce-shipping-stamps.php' ),
				'return' => true,
				'times'  => 0,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( Settings::AUTO_PURCHASE_IS_ENABLED_OPTION_NAME ),
				'return' => 'no-or-anything-other-than-yes',
				'times'  => 1,
			)
		);

		$sut = new Settings();

		$result = $sut->is_auto_purchase_enabled();

		$this->assertFalse( $result );
	}

	/**
	 * @covers ::is_auto_purchase_enabled
	 */
	public function test_is_enabled_false_stamps_inactive() {

		\WP_Mock::userFunction(
			'is_plugin_active',
			array(
				'args'   => array( 'woocommerce-shipping-stamps/woocommmerce-shipping-stamps.php' ),
				'return' => false,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( Settings::AUTO_PURCHASE_IS_ENABLED_OPTION_NAME ),
				'return' => 'yes',
				'times'  => 1,
			)
		);

		$sut = new Settings();

		$result = $sut->is_auto_purchase_enabled();

		$this->assertFalse( $result );
	}


	/**
	 * @covers ::get_order_status_after_purchase
	 */
	public function test_order_status_after_purchase_happy_path() {

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( Stamps_Plugin_API::SAMPLES_ONLY_OPTION_NAME ),
				'return' => 'no',
				'times'  => 1,
			)
		);

		// Doesn't get called because the conditional && short circuits.
		\WP_Mock::userFunction(
			'wp_get_environment_type',
			array(
				'return' => 'production',
				'times'  => 0,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( Settings::ORDER_STATUS_AFTER_PURCHASE_OPTION_NAME, Order_Status::SHIPPING_LABEL_PURCHASED_STATUS ),
				'return' => Order_Status::SHIPPING_LABEL_PURCHASED_STATUS,
				'times'  => 1,
			)
		);

		$sut = new Settings();

		$result = $sut->get_order_status_after_purchase();

		$this->assertEquals( Order_Status::SHIPPING_LABEL_PURCHASED_STATUS, $result );

	}

	/**
	 * @covers ::get_order_status_after_purchase
	 */
	public function test_order_status_after_purchase_samples_on_production() {

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( Stamps_Plugin_API::SAMPLES_ONLY_OPTION_NAME ),
				'return' => 'yes',
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_get_environment_type',
			array(
				'return' => 'production',
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( Settings::ORDER_STATUS_AFTER_PURCHASE_OPTION_NAME, Order_Status::SHIPPING_LABEL_PURCHASED_STATUS ),
				'return' => Order_Status::SHIPPING_LABEL_PURCHASED_STATUS,
				'times'  => 0,
			)
		);

		$sut = new Settings();

		$result = $sut->get_order_status_after_purchase();

		$this->assertNull( $result );
	}


	/**
	 * @covers ::get_order_status_after_bulk_printing
	 */
	public function test_order_status_after_bulk_printing_happy_path() {

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( Stamps_Plugin_API::SAMPLES_ONLY_OPTION_NAME ),
				'return' => 'no',
				'times'  => 1,
			)
		);

		// Doesn't get called because the conditional && short circuits.
		\WP_Mock::userFunction(
			'wp_get_environment_type',
			array(
				'return' => 'production',
				'times'  => 0,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( Settings::ORDER_STATUS_AFTER_BULK_PRINTING_OPTION_NAME, Order_Status::PRINTED_STATUS ),
				'return' => Order_Status::PRINTED_STATUS,
				'times'  => 1,
			)
		);

		$sut = new Settings();

		$result = $sut->get_order_status_after_bulk_printing();

		$this->assertEquals( Order_Status::PRINTED_STATUS, $result );

	}

	/**
	 * @covers ::get_order_status_after_bulk_printing
	 */
	public function test_order_status_after_bulk_printing_samples_on_production() {

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( Stamps_Plugin_API::SAMPLES_ONLY_OPTION_NAME ),
				'return' => 'yes',
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_get_environment_type',
			array(
				'return' => 'production',
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( Settings::ORDER_STATUS_AFTER_PURCHASE_OPTION_NAME, Order_Status::PRINTED_STATUS ),
				'return' => Order_Status::PRINTED_STATUS,
				'times'  => 0,
			)
		);

		$sut = new Settings();

		$result = $sut->get_order_status_after_bulk_printing();

		$this->assertNull( $result );
	}

	/**
	 * TODO: Test the plugin header name matches.
	 *
	 * @covers ::get_plugin_name
	 */
	public function test_plugin_name() {
		$sut    = new Settings();
		$result = $sut->get_plugin_name();
		$this->assertEquals( 'Auto Purchase Stamps.com', $result );
	}

	/**
	 * @covers ::get_log_level
	 */
	public function test_get_log_level() {

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( Settings::LOG_LEVEL_OPTION_NAME, LogLevel::NOTICE ),
				'return' => LogLevel::DEBUG,
				'times'  => 1,
			)
		);

		$sut    = new Settings();
		$result = $sut->get_log_level();

		$this->assertEquals( LogLevel::DEBUG, $result );

	}

	/**
	 * @covers ::get_plugin_slug
	 */
	public function test_plugin_slug() {
		$sut    = new Settings();
		$result = $sut->get_plugin_slug();
		$this->assertEquals( 'bh-wc-auto-purchase-stamps', $result );
	}

	/**
	 * @covers ::get_plugin_basename
	 */
	public function test_plugin_basename() {
		$sut    = new Settings();
		$result = $sut->get_plugin_basename();
		$this->assertEquals( 'bh-wc-auto-purchase-stamps/bh-wc-auto-purchase-stamps.php', $result );
	}

}
