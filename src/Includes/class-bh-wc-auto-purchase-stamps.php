<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * frontend-facing side of the site and the admin area.
 *
 * @link       https://BrianHenryIE.com
 * @since      1.0.0
 *
 * @package    brianhenryie/wc-auto-purchase-stamps
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\Includes;

use BrianHenryIE\WC_Auto_Purchase_Stamps\Action_Scheduler\Scheduler;
use BrianHenryIE\WC_Auto_Purchase_Stamps\Admin\Plugins_Page;
use BrianHenryIE\WC_Auto_Purchase_Stamps\API\API_Interface;
use BrianHenryIE\WC_Auto_Purchase_Stamps\API\CLI;
use BrianHenryIE\WC_Auto_Purchase_Stamps\API\Settings_Interface;
use BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce\Order;
use BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce\Order_Status;
use BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce\Shop_Order_Admin_List;
use BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce_Shipping_Stamps\Stamps_Settings;
use Psr\Log\LoggerInterface;
use WP_CLI;

/**
 * The core plugin class.
 *
 * Add the actions and filters for the plugin.
 *
 * @since      1.0.0
 * @package    brianhenryie/wc-auto-purchase-stamps
 *
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */
class BH_WC_Auto_Purchase_Stamps {

	protected LoggerInterface $logger;

	protected Settings_Interface $settings;

	protected API_Interface $api;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the frontend-facing side of the site.
	 *
	 * @param API_Interface      $api The plugin's main functions.
	 * @param Settings_Interface $settings The plugin settings.
	 * @param LoggerInterface    $logger A PSR logger.
	 *
	 * @since    1.0.0
	 */
	public function __construct( API_Interface $api, Settings_Interface $settings, LoggerInterface $logger ) {

		$this->logger   = $logger;
		$this->settings = $settings;
		$this->api      = $api;

		$this->set_locale();

		$this->define_plugins_page_hooks();

		$this->define_stamps_settings_hooks();
		$this->define_woocommerce_order_status_hooks();
		$this->define_woocommerce_order_admin_list_hooks();
		$this->define_woocommerce_order_hooks();
		$this->define_action_scheduler_hooks();

		$this->define_cli_hooks();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 */
	protected function set_locale(): void {

		$plugin_i18n = new I18n();

		add_action( 'init', array( $plugin_i18n, 'load_plugin_textdomain' ) );
	}

	/**
	 * Add a link to settings on the plugins page, and a link to Stamps.com.
	 */
	protected function define_plugins_page_hooks(): void {

		// Add a link to the admin page on the plugin's entry.
		$plugins_page    = new Plugins_Page();
		$plugin_basename = $this->settings->get_plugin_basename();

		add_filter( "plugin_action_links_{$plugin_basename}", array( $plugins_page, 'action_links' ), 10, 4 );
		add_filter( 'plugin_row_meta', array( $plugins_page, 'row_meta' ), 10, 4 );
	}

	/**
	 * Hook into actions when the WooCommerce order status changes.
	 */
	protected function define_woocommerce_order_hooks(): void {

		$order = new Order( $this->settings, $this->logger );
		add_action(
			'woocommerce_order_status_changed',
			array( $order, 'schedule_purchase_stamps_on_status_change_to_paid' ),
			10,
			3
		);
		add_action( 'woocommerce_payment_complete', array( $order, 'schedule_purchase_stamps_on_payment_complete' ) );
		add_action( 'admin_action_mark_processing', array( $order, 'on_bulk_order_processing' ) );
	}

	/**
	 * Add this plugin's settings to the bottom of the Stamps.com plugin's settings page.
	 */
	protected function define_stamps_settings_hooks(): void {

		$stamps_settings = new Stamps_Settings();

		add_filter( 'wc_settings_tab_stamps', array( $stamps_settings, 'add_plugin_settings' ), 11, 1 );
	}

	/**
	 * Register the 'Shipping Label Purchased' and 'Printed' statuses, ensuring they are registered as paid statuses, and appear in reports.
	 */
	protected function define_woocommerce_order_status_hooks(): void {

		$order_status = new Order_Status();

		add_action( 'woocommerce_init', array( $order_status, 'register_status' ) );
		add_filter( 'wc_order_statuses', array( $order_status, 'add_order_status_to_woocommerce' ), 9 );
		add_filter( 'woocommerce_order_is_paid_statuses', array( $order_status, 'add_to_paid_status_list' ) );
		add_filter( 'woocommerce_reports_order_statuses', array( $order_status, 'add_to_reports_status_list' ) );
	}

	/**
	 * Add and handle the bulk-print action on the admin orders list page.
	 *
	 * @see wp-admin/edit.php?post_type=shop_order
	 */
	protected function define_woocommerce_order_admin_list_hooks(): void {

		$shop_order_posts_page = new Shop_Order_Admin_List( $this->api, $this->settings, $this->logger );

		add_filter( 'bulk_actions-edit-shop_order', array( $shop_order_posts_page, 'register_bulk_action_print_4x6_stamps_labels_pdf' ), 20, 1 );
		add_action( 'admin_action_print_4x6_stamps_labels_pdf', array( $shop_order_posts_page, 'generate_print_4x6_stamps_labels_pdf' ) );
	}

	/**
	 * Add the action that handles events initiated by Action Scheduler.
	 */
	protected function define_action_scheduler_hooks(): void {

		$scheduler = new Scheduler( $this->api, $this->logger );

		add_action( Scheduler::ORDER_PAID_JOB_ACTION_NAME, array( $scheduler, 'purchase_stamps_for_order' ) );
	}

	/**
	 * Add a `wp stamps purchase` CLI command.
	 *
	 * @since    1.0.0
	 */
	protected function define_cli_hooks(): void {

		if ( ! class_exists( WP_CLI::class ) ) {
			return;
		}

		$cli = new CLI( $this->api );

		WP_CLI::add_command( 'stamps', array( $cli, 'purchase' ) );

	}
}
