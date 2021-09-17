<?php
/**
 * @package brianhenryie/bh-wc-auto-purchase-stamps
 * @author  Your Name <email@example.com>
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\Includes;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Auto_Purchase_Stamps\Action_Scheduler\Scheduler;
use BrianHenryIE\WC_Auto_Purchase_Stamps\Admin\Admin;
use BrianHenryIE\WC_Auto_Purchase_Stamps\Admin\Plugins_Page;
use BrianHenryIE\WC_Auto_Purchase_Stamps\API\API_Interface;
use BrianHenryIE\WC_Auto_Purchase_Stamps\API\Settings_Interface;
use BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce\Order;
use BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce\Order_Status;
use BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce_Shipping_Stamps\Stamps_Settings;
use WP_Mock\Matcher\AnyInstance;

/**
 *
 * @coversDefaultClass \BrianHenryIE\WC_Auto_Purchase_Stamps\Includes\BH_WC_Auto_Purchase_Stamps
 */
class BH_WC_Auto_Purchase_Stamps_Unit_Test extends \Codeception\Test\Unit {


	protected function setup(): void {
		parent::setup();
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::set_locale
	 */
	public function test_set_locale_hooked() {

		\WP_Mock::expectActionAdded(
			'init',
			array( new AnyInstance( I18n::class ), 'load_plugin_textdomain' )
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();
		new BH_WC_Auto_Purchase_Stamps( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_plugins_page_hooks
	 */
	public function test_plugins_page_hooks() {

		$plugin_basename = 'bh-wc-auto-purchase-stamps/bh-wc-auto-purchase-stamps.php';

		\WP_Mock::expectFilterAdded(
			"plugin_action_links_{$plugin_basename}",
			array( new AnyInstance( Plugins_Page::class ), 'action_links' ),
			10,
			4
		);

		\WP_Mock::expectFilterAdded(
			'plugin_row_meta',
			array( new AnyInstance( Plugins_Page::class ), 'row_meta' ),
			10,
			4
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'get_plugin_basename' => $plugin_basename,
			)
		);
		$logger   = new ColorLogger();
		new BH_WC_Auto_Purchase_Stamps( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_stamps_settings_hooks
	 */
	public function test_stamps_settings_hooks() {

		\WP_Mock::expectFilterAdded(
			'wc_settings_tab_stamps',
			array( new AnyInstance( Stamps_Settings::class ), 'add_plugin_settings' ),
			11,
			1
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();
		new BH_WC_Auto_Purchase_Stamps( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_woocommerce_order_status_hooks
	 */
	public function test_woocommerce_order_status_hooks() {

		\WP_Mock::expectActionAdded(
			'woocommerce_init',
			array( new AnyInstance( Order_Status::class ), 'register_status' )
		);
		\WP_Mock::expectFilterAdded(
			'wc_order_statuses',
			array( new AnyInstance( Order_Status::class ), 'add_order_status_to_woocommerce' ),
			9,
			1
		);
		\WP_Mock::expectFilterAdded(
			'woocommerce_order_is_paid_statuses',
			array( new AnyInstance( Order_Status::class ), 'add_to_paid_status_list' )
		);
		\WP_Mock::expectFilterAdded(
			'woocommerce_reports_order_statuses',
			array( new AnyInstance( Order_Status::class ), 'add_to_reports_status_list' )
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();
		new BH_WC_Auto_Purchase_Stamps( $api, $settings, $logger );
	}

	/**
	 * @covers ::define_woocommerce_order_hooks
	 */
	public function test_woocommerce_order_hooks() {

		\WP_Mock::expectActionAdded(
			'woocommerce_order_status_changed',
			array( new AnyInstance( Order::class ), 'schedule_purchase_stamps_on_status_change_to_paid' ),
			10,
			3
		);

		\WP_Mock::expectActionAdded(
			'woocommerce_payment_complete',
			array( new AnyInstance( Order::class ), 'schedule_purchase_stamps_on_payment_complete' )
		);

		\WP_Mock::expectActionAdded(
			'admin_action_mark_processing',
			array( new AnyInstance( Order::class ), 'on_bulk_order_processing' )
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();
		new BH_WC_Auto_Purchase_Stamps( $api, $settings, $logger );
	}


	/**
	 * @covers ::define_action_scheduler_hooks
	 */
	public function test_action_scheduler_hooks() {

		\WP_Mock::expectActionAdded(
			Scheduler::ORDER_PAID_JOB_ACTION_NAME,
			array( new AnyInstance( Scheduler::class ), 'purchase_stamps_for_order' ),
			10,
			1
		);

		\WP_Mock::expectActionAdded(
			'woocommerce_payment_complete',
			array( new AnyInstance( Order::class ), 'schedule_purchase_stamps_on_payment_complete' )
		);

		\WP_Mock::expectActionAdded(
			'admin_action_mark_processing',
			array( new AnyInstance( Order::class ), 'on_bulk_order_processing' )
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Settings_Interface::class );
		$logger   = new ColorLogger();
		new BH_WC_Auto_Purchase_Stamps( $api, $settings, $logger );
	}


}
