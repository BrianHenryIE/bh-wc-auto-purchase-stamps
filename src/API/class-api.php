<?php
/**
 *
 * @link       https://BrianHenryIE.com
 * @since      1.0.0
 *
 * @package    brianhenryie/wc-auto-purchase-stamps
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\API;

use BrianHenryIE\WC_Auto_Purchase_Stamps\PdfHelpers\PdfConcatenate;
use BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce_Shipping_Stamps\Stamps_Plugin_API;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Order;
use WC_Stamps_Post_Types;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the frontend-facing stylesheet and JavaScript.
 *
 * @package    brianhenryie/wc-auto-purchase-stamps
 *
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */
class API implements API_Interface {

	use LoggerAwareTrait;

	/**
	 * Used when instantiating `Stamps_Label`.
	 *
	 * @var Settings_Interface The plugin settings.
	 */
	protected Settings_Interface $settings;

	protected Stamps_Plugin_API $stamps_plugin_api;

	/**
	 * API constructor.
	 *
	 * @param Settings_Interface $settings The plugin settings.
	 * @param LoggerInterface    $logger A PSR logger.
	 */
	public function __construct( Settings_Interface $settings, LoggerInterface $logger ) {

		$this->settings = $settings;
		$this->setLogger( $logger );

		$this->stamps_plugin_api = new Stamps_Plugin_API();

	}

	/**
	 * Function to call from cron.
	 *
	 * @param WC_Order $order The WooCommerce order.
	 *
	 * @return array{success:bool, message:string, labels?:array, file?:array}
	 */
	public function auto_purchase_stamps_for_order( WC_Order $order ): array {

		$context             = array();
		$order_id            = $order->get_id();
		$context['order_id'] = $order_id;

		// Check do we already have any?

		$labels_for_orders            = $this->stamps_plugin_api->get_order_labels( $order_id );
		$labels_for_orders_count      = count( $labels_for_orders );
		$context['labels_for_orders'] = $labels_for_orders_count;
		$context['labels']            = $labels_for_orders;

		if ( 0 !== count( $labels_for_orders ) ) {

			$message = 'Order already has stamps purchased ' . $order_id;

			$this->logger->debug( $message, $context );

			$labels_filepaths = $order->get_meta( Stamps_Label::LABEL_PDF_FILES_PATH_META_KEY, true );

			return array(
				'success' => false,
				'message' => $message,
				'labels'  => $labels_filepaths,
			);
		}

		$order_status            = $order->get_status();
		$context['order_status'] = $order_status;

		if ( ! in_array( $order_status, wc_get_is_paid_statuses(), true ) ) {

			$message = "Order $order_id has not been paid : $order_status.";
			$this->logger->debug( $message, $context );

			return array(
				'success' => false,
				'message' => $message,
			);
		}

		$disable = false;

		/**
		 * A filter to allow disabling auto-purchasing for this order.
		 *
		 * e.g. Do not run for wholesale orders.
		 *
		 * @param bool $disable Should auto-printing be disabled for this order?
		 * @param WC_Order $order The order object.
		 * @return bool
		 */
		$disable = apply_filters( 'bh_wc_auto_purchase_stamps_disable', $disable, $order );

		if ( false !== $disable ) {

			$message = 'Filter disabled auto purchase for this order ' . $order_id;

			$this->logger->debug( $message, $context );

			return array(
				'success' => false,
				'message' => $message,
			);
		}

		return $this->purchase_stamps_for_order( $order );
	}

	/**
	 *
	 *
	 * @param WC_Order $order The WooCommerce order.
	 *
	 * @return array{success:bool, message:string, file?:array}
	 */
	public function purchase_stamps_for_order( WC_Order $order ): array {

		$context             = array();
		$order_id            = $order->get_id();
		$context['order_id'] = $order_id;

		$order_shipping_country            = $order->get_shipping_country();
		$context['order_shipping_country'] = $order_shipping_country;

		// TODO: Only run for US orders. (at least until customs is figured out!)
		if ( 'US' !== $order_shipping_country ) {

			$message = 'Non US order ' . $order_id;

			$this->logger->info( $message, $context );

			return array(
				'success' => false,
				'message' => $message,
			);
		}

		// Prereq.
		if ( ! post_type_exists( 'wc_stamps_label' ) ) {
			$stamps_post_types = new WC_Stamps_Post_Types();
			$stamps_post_types->register_post_types();
		}

		$stamps_label = new Stamps_Label( $this->settings, $this->logger );

		return $stamps_label->do_order( $order );

	}


	/**
	 * Given a list of order ids, auto-purchases the stamps.com labels, then concatenates them into a single pdf for output.
	 *
	 * Intended for bulk printing labels from the orders screen.
	 *
	 * @used-by Shop_Order_Admin_List::generate_print_4x6_stamps_labels_pdf()
	 *
	 * @param WC_Order[] $orders The list of orders to print the labels for.
	 * @return array{order_ids_succeeded:array,order_ids_failed:array,file?:array,filepath?:string}
	 */
	public function generate_merged_4x6_pdf_for_orders( array $orders ): array {

		$pdf_file_list = array();

		$order_ids = array();

		foreach ( $orders as $order ) {

			$order_ids[] = $order->get_id();

			$result = $this->auto_purchase_stamps_for_order( $order );

			if ( ! isset( $result['labels'] ) ) {
				continue;
			}

			$file_path = array_pop( $result['labels'] );
			if ( file_exists( WP_CONTENT_DIR . '/' . $file_path ) ) {
				$pdf_file_list[ $order->get_id() ] = WP_CONTENT_DIR . '/' . $file_path;
			}
		}

		// TODO: would be nicer formatted rather than unix time.
		$time                = time();
		$filename            = "stamps-labels-4x6-bulk-print-$time.pdf";
		$tmp_output_filepath = get_temp_dir() . $filename;

		$concatenated_pdf = new PdfConcatenate();
		$concatenated_pdf->concatenateSequentially( $pdf_file_list, $tmp_output_filepath );

		if ( ! file_exists( $tmp_output_filepath ) ) {
			return array(
				'order_ids_succeeded' => array(),
				'order_ids_failed'    => $order_ids,
			);
		}

		$result = array(
			'order_ids_succeeded' => array_keys( $pdf_file_list ),
			'order_ids_failed'    => array_diff( $order_ids, array_keys( $pdf_file_list ) ),
		);

		/** @var \BrianHenryIE\WP_Private_Uploads\API\API_Interface $bh_wp_private_uploads */
		global $bh_wp_private_uploads;

		if ( empty( $bh_wp_private_uploads ) ) {
			$result['filepath'] = $tmp_output_filepath;
			return $result;
		}

		/**
		 * @var array{file?:string, url?:string, type?:string, error?:string} $file
		 */
		$file = $bh_wp_private_uploads->move_file_to_private_uploads( $tmp_output_filepath, $filename );

		$result['file']     = $file;
		$result['filepath'] = isset( $file['file'] ) ? $file['file'] : $tmp_output_filepath;

		return $result;
	}

}
