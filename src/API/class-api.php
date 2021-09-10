<?php
/**
 *
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    BH_WC_Auto_Purchase_Stamps
 * @subpackage BH_WC_Auto_Purchase_Stamps/api
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\API;

use BrianHenryIE\WC_Auto_Purchase_Stamps\Mpdf\Mpdf;
use BrianHenryIE\WC_Auto_Purchase_Stamps\Mpdf\Output\Destination;
use Psr\Log\LoggerInterface;
use WC_Order;
use WC_Stamps_Post_Types;

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the frontend-facing stylesheet and JavaScript.
 *
 * @package    BH_WC_Auto_Purchase_Stamps
 * @subpackage BH_WC_Auto_Purchase_Stamps/frontend
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */
class API implements API_Interface {

	const ORDER_PAID_CRON_JOB_NAME = 'bh-wc-auto-purchase-stamps-process-order';

	/** @var Settings_Interface */
	protected $settings;

	/** @var LoggerInterface */
	protected $logger;


	/**
	 * API constructor.
	 *
	 * @param Settings_Interface $settings
	 * @param LoggerInterface    $logger
	 */
	public function __construct( $settings, $logger ) {

		$this->settings = $settings;
		$this->logger   = $logger;

	}

	/**
	 * Function to call from cron.
	 *
	 * @param string $order_id
	 */
	public function auto_purchase_stamps_for_order( $order_id ): void {

		$order = wc_get_order( $order_id );

		if ( false === $order ) {

			$this->logger->debug( 'No order found ' . $order_id );

			return;
		}

		if ( 'processing' !== $order->get_status() ) {

			$this->logger->debug( 'Order status not processing (maybe already changed by another plugin?) ' . $order_id );

			return;
		}

		/**
		 * A filter to allow disabling auto-purchasing for this order.
		 *
		 * e.g. do not run for wholesale orders.
		 */
		$disable = apply_filters( 'disable_auto_purchase_stamps', false, $order );

		if ( 'US' !== $order->get_shipping_country() ) {
			$disable = true;
		}

		// TODO: move out of here.
		// Disable for wholesale orders
		if ( 'wholesale' === $order->get_meta( '_wwpp_order_type' ) ) {
			$disable = true;
		}

		if ( false !== $disable ) {

			$this->logger->debug( 'Filter disabled auto purchase for this order ' . $order_id );

			return;
		}

		$this->purchase_stamps_for_order( $order_id );
	}

	/**
	 *
	 *
	 * @param int $order_id
	 */
	public function purchase_stamps_for_order( $order_id ): void {

		$order = wc_get_order( $order_id );

		if ( ! ( $order instanceof WC_Order ) ) {

			$this->logger->debug( 'No order found ' . $order_id );

			return;
		}

		// TODO: Only run for US orders. (at least until customs is figured out!)
		if ( 'US' !== $order->get_shipping_country() ) {

			$this->logger->info( 'Non US order ' . $order_id );

			return;
		}

		// Check do we already have any?
		$labels_for_orders = \WC_Stamps_Labels::get_order_labels( $order_id );

		if ( 0 !== count( $labels_for_orders ) ) {

			$this->logger->debug( 'Order already has stamps purchased ' . $order_id );

			return;
		}

		// Prereq.
		if ( ! post_type_exists( 'wc_stamps_label' ) ) {
			$stamps_post_types = new WC_Stamps_Post_Types();
			$stamps_post_types->register_post_types();
		}

		$stamps_label = new Stamps_Label( $this->settings, $this->logger );

		$stamps_label->do_order( $order );

	}


	/**
	 * Intended for bulk printing labels from the orders screen.
	 *
	 * @param array $order_ids
	 */
	public function generate_merged_4x6_pdf_for_orders( $order_ids ) {

		$png_file_list = array();

		foreach ( $order_ids as $order_id ) {
			$this->purchase_stamps_for_order( $order_id );

			$order = wc_get_order( $order_id );

			$png_path = $order->get_meta( Stamps_Label::LABEL_PNG_FILE_PATH_META_KEY );
			if ( ! empty( $png_path ) ) {
				$png_file_list[ $order_id ] = $png_path;
			}
		}

		$html = '';

		foreach ( $png_file_list as $png ) {

			$html .= '<div style="page-break-after: auto;" >';
			$html .= '<img style="width: 350px;" src="' . ABSPATH . $png . '" />';
			$html .= '</div>';

		}

		$output_path = ABSPATH . '/wp-content/uploads/private/stamps-4x6-' . time() . '.pdf';

		$result = $this->generate_pdf( $html, $output_path );

		if ( ! is_null( $result ) ) {

			// Maybe mark orders complete.
			if ( ! is_null( $this->settings->order_status_after_bulk_printing() ) ) {

				foreach ( $png_file_list as $order_id => $png ) {

					$order = wc_get_order( $order_id );
					$note  = 'Bulk printed 4x6';
					$order->set_status( $this->settings->order_status_after_bulk_printing(), $note );
					$order->save();

				}
			}

			$result = str_replace( ABSPATH, site_url(), $result );

			wp_safe_redirect( $result );
		}

		exit;
	}


	/**
	 * Uses MPDF to generate and save a PDF.
	 *
	 * @param string
	 * @param string
	 * @param array  $args
	 *
	 * @see Mpdf
	 *
	 * @return ?string path
	 */
	protected function generate_pdf( $html, $output_path, $args = array() ): ?string {

		$this->logger->debug( 'generate_pdf ' . $output_path );

		$paper_orientation = 'portrait';

		$mpdf_config = array(
			'orientation'   => $paper_orientation,
			'format'        => array( 100, 150 ), // 4x6
			'margin_left'   => 6,
			'margin_right'  => 5,
			'margin_top'    => 6,
			'margin_bottom' => 5,
			'margin_header' => 0,
			'margin_footer' => 0,
		);

		// TODO: merge defaults.

		try {
			$mpdf = new Mpdf( $mpdf_config );

			$mpdf->WriteHTML( $html );

			$mpdf->Output( $output_path, Destination::FILE );

			$this->logger->info( 'Success: ' . $output_path );

			return $output_path;

		} catch ( \Exception $e ) {

			$this->logger->error( $e->getMessage(), array( 'exception' => $e ) );

			return null;
		}

	}

}
