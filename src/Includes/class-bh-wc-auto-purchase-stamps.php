<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * frontend-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    BH_WC_Auto_Purchase_Stamps
 * @subpackage BH_WC_Auto_Purchase_Stamps/includes
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\Includes;

use BrianHenryIE\WC_Auto_Purchase_Stamps\Admin\Admin;
use BrianHenryIE\WC_Auto_Purchase_Stamps\Admin\Plugins_Page;
use BrianHenryIE\WC_Auto_Purchase_Stamps\API\API;
use BrianHenryIE\WC_Auto_Purchase_Stamps\API\API_Interface;
use BrianHenryIE\WC_Auto_Purchase_Stamps\API\CLI;
use BrianHenryIE\WC_Auto_Purchase_Stamps\API\Settings_Interface;

use Psr\Log\LoggerInterface;
use BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce\Order;
use BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce\Order_Status;
use BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce\Shop_Order_Admin_List;
use BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce_Shipping_Stamps\Stamps_Settings;
use WP_CLI;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * frontend-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    BH_WC_Auto_Purchase_Stamps
 * @subpackage BH_WC_Auto_Purchase_Stamps/includes
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */
class BH_WC_Auto_Purchase_Stamps {

	protected $logger;

	protected $settings;

	protected $api;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the frontend-facing side of the site.
	 *
	 * @param API_Interface      $api
	 * @param Settings_Interface $settings
	 * @param LoggerInterface    $logger
	 *
	 * @since    1.0.0
	 */
	public function __construct( $api, $settings, $logger ) {

		$this->logger   = $logger;
		$this->settings = $settings;
		$this->api      = $api;

		$this->set_locale();

		$this->define_admin_hooks();

		$this->define_api_hooks();

		$this->define_cron_hooks();
		$this->define_woocommerce_hooks();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 */
	protected function set_locale() {

		$plugin_i18n = new I18n();

		add_action( 'plugins_loaded', array( $plugin_i18n, 'load_plugin_textdomain' ) );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 */
	protected function define_admin_hooks() {

		$plugin_admin = new Admin( $this->settings );

		// Leaving these in because they might be used on a settings page.
		// add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );

		add_action( 'admin_init', array( $plugin_admin, 'check_required_plugins_active' ) );

		// Add a link to the admin page on the plugin's entry.
		$this->plugins_page = new Plugins_Page();
		$plugin_basename    = $this->settings->get_plugin_basename();
		add_filter( "plugin_action_links_{$plugin_basename}", array( $this->plugins_page, 'action_links' ) );

	}

	protected function define_woocommerce_hooks() {

		if ( $this->settings->is_enabled() ) {

			$order = new Order( $this->settings, $this->logger );
			add_action( 'woocommerce_order_status_changed', array( $order, 'schedule_purchase_stamps_on_paid' ), 10, 3 );
			add_action( 'admin_action_mark_processing', array( $order, 'on_bulk_order_processing' ) );
		}

		$stamps_settings = new Stamps_Settings();
		add_filter( 'wc_settings_tab_stamps', array( $stamps_settings, 'add_plugin_settings' ), 11, 1 );

		$order_status = new Order_Status();
		add_action( 'woocommerce_init', array( $order_status, 'register_status' ) );
		add_filter( 'wc_order_statuses', array( $order_status, 'add_order_status_to_woocommerce' ), 9 );
		add_filter( 'woocommerce_order_is_paid_statuses', array( $order_status, 'add_to_paid_status_list' ) );
		add_filter( 'woocommerce_reports_order_statuses', array( $order_status, 'add_to_reports_status_list' ) );

		/**
		 * Shop_Order_Post_Page is the orders list in the admin UI – wp-admin/edit.php?post_type=shop_order
		 */
		$shop_order_posts_page = new Shop_Order_Admin_List( $this->api, $this->settings, $this->logger );
		add_filter( 'bulk_actions-edit-shop_order', array( $shop_order_posts_page, 'register_bulk_action_print_4x6_stamps_labels_pdf' ), 20, 1 );
		add_action( 'admin_action_print_4x6_stamps_labels_pdf', array( $shop_order_posts_page, 'generate_print_4x6_stamps_labels_pdf' ) );

	}

	protected function define_cron_hooks() {

		$cron = new Cron( $this->api );

		add_action( API::ORDER_PAID_CRON_JOB_NAME, $cron, 'auto_purchase_stamps_for_order' );

	}

	/**
	 *
	 * @since    1.0.0
	 * @access   protected
	 */
	protected function define_api_hooks() {

		if ( ! class_exists( WP_CLI::class ) ) {
			return;
		}
		CLI::$api = $this->api;

		// wp stamps
		WP_CLI::add_command( 'stamps', CLI::class );

	}
}
