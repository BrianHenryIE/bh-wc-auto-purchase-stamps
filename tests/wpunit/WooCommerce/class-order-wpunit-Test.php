<?php

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Auto_Purchase_Stamps\Action_Scheduler\Scheduler;
use BrianHenryIE\WC_Auto_Purchase_Stamps\API\Settings_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce\Order
 */
class Order_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * When the order status changed from an unpaid to a paid status, schedule the purchasing.
	 *
	 * @covers ::schedule_purchase_stamps_on_status_change_to_paid
	 */
	public function test_scheduled_on_status_changed_paid() {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'is_auto_purchase_enabled' => true,
			)
		);
		$sut      = new Order( $settings, $logger );

		$order    = new \WC_Order();
		$order_id = $order->save();

		$before_scheduled_actions = as_get_scheduled_actions( array( 'hook' => Scheduler::ORDER_PAID_JOB_ACTION_NAME ) );

		$sut->schedule_purchase_stamps_on_status_change_to_paid( $order_id, 'pending', 'processing' );

		$after_scheduled_actions = as_get_scheduled_actions( array( 'hook' => Scheduler::ORDER_PAID_JOB_ACTION_NAME ) );

		$difference_count = count( $after_scheduled_actions ) - count( $before_scheduled_actions );

		// One action should be added.
		$this->assertEquals( 1, $difference_count );

		$scheduled_action = array_pop( $after_scheduled_actions );

		$this->assertEquals( $order_id, $scheduled_action->get_args()[0] );

	}

	/**
	 * @covers ::schedule_purchase_stamps_on_payment_complete
	 */
	public function test_scheduled_action_on_payment_complete() {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'is_auto_purchase_enabled' => true,
			)
		);
		$sut      = new Order( $settings, $logger );

		$order    = new \WC_Order();
		$order_id = $order->save();

		$before_scheduled_actions = as_get_scheduled_actions( array( 'hook' => Scheduler::ORDER_PAID_JOB_ACTION_NAME ) );

		$sut->schedule_purchase_stamps_on_payment_complete( $order_id );

		$after_scheduled_actions = as_get_scheduled_actions( array( 'hook' => Scheduler::ORDER_PAID_JOB_ACTION_NAME ) );

		$difference_count = count( $after_scheduled_actions ) - count( $before_scheduled_actions );

		// One action should be added.
		$this->assertEquals( 1, $difference_count );

		$scheduled_action = array_pop( $after_scheduled_actions );

		$this->assertEquals( $order_id, $scheduled_action->get_args()[0] );
	}

	/**
	 * @covers ::maybe_schedule_purchase_stamps_action
	 */
	public function test_if_both_actions_fire_only_one_job_is_scheduled() {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'is_auto_purchase_enabled' => true,
			)
		);
		$sut      = new Order( $settings, $logger );

		$order    = new \WC_Order();
		$order_id = $order->save();

		$before_scheduled_actions = as_get_scheduled_actions( array( 'hook' => Scheduler::ORDER_PAID_JOB_ACTION_NAME ) );

		$sut->schedule_purchase_stamps_on_status_change_to_paid( $order_id, 'pending', 'processing' );
		$sut->schedule_purchase_stamps_on_payment_complete( $order_id );

		$after_scheduled_actions = as_get_scheduled_actions( array( 'hook' => Scheduler::ORDER_PAID_JOB_ACTION_NAME ) );

		$difference_count = count( $after_scheduled_actions ) - count( $before_scheduled_actions );

		// One action should be added.
		$this->assertEquals( 1, $difference_count );

		$scheduled_action = array_pop( $after_scheduled_actions );

		$this->assertEquals( $order_id, $scheduled_action->get_args()[0] );
	}

	/**
	 * Runs on admin_action_mark_processing hook.
	 *
	 * @covers ::on_bulk_order_processing
	 */
	public function test_on_bulk_order_processing() {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty(
			Settings_Interface::class,
			array(
				'is_auto_purchase_enabled' => true,
			)
		);
		$sut      = new Order( $settings, $logger );

		$_REQUEST['post'] = array( '123', '456' );

		$before_scheduled_actions = as_get_scheduled_actions( array( 'hook' => Scheduler::ORDER_PAID_JOB_ACTION_NAME ) );

		$sut->on_bulk_order_processing();

		$after_scheduled_actions = as_get_scheduled_actions( array( 'hook' => Scheduler::ORDER_PAID_JOB_ACTION_NAME ) );

		$difference_count = count( $after_scheduled_actions ) - count( $before_scheduled_actions );

		// One action should be added.
		$this->assertEquals( 2, $difference_count );

		$scheduled_action_order_ids = array_map(
			function( $element ) {
				return $element->get_args()[0];
			},
			$after_scheduled_actions
		);

		$this->assertEmpty( array_diff( array( 123, 456 ), $scheduled_action_order_ids ) );
	}
}
