<?php
/**
 * Bulk print merged pdf.
 *
 * @see wp-admin/edit.php?post_type=shop_order
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce;

use BrianHenryIE\WC_Auto_Purchase_Stamps\API\API_Interface;
use BrianHenryIE\WC_Auto_Purchase_Stamps\API\Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Order;

class Shop_Order_Admin_List {

	const BULK_ACTION_NAME = 'print_4x6_stamps_labels_pdf';

	use LoggerAwareTrait;

	/** @var API_Interface */
	protected $api;

	/** @var Settings_Interface */
	protected $settings;

	/**
	 * Shop_Order_Admin_List constructor.
	 *
	 * @param API_Interface      $api The main plugin functions.
	 * @param Settings_Interface $settings The plugin settings.
	 * @param LoggerInterface    $logger A PSR logger.
	 */
	public function __construct( API_Interface $api, Settings_Interface $settings, LoggerInterface $logger ) {
		$this->setLogger( $logger );
		$this->settings = $settings;
		$this->api      = $api;
	}

	/**
	 * Add "Print 4x6 shipping labels PDF" to bulk actions drop-down, immediately before "Change status to completed".
	 *
	 * <option value="print_shipping_labels_pdf">Print shipping labels PDF</option>
	 *
	 * @see https://rudrastyh.com/woocommerce/bulk-change-custom-order-status.html
	 *
	 * @hooked bulk_actions-edit-shop_order

	 * @param string[] $bulk_actions "Change status to processing" etc.
	 * @return string[]
	 */
	public function register_bulk_action_print_4x6_stamps_labels_pdf( array $bulk_actions ): array {

		$bulk_actions[ self::BULK_ACTION_NAME ] = 'Print 4x6 Stamps.com labels PDF';

		return $bulk_actions;
	}

	/**
	 * @see https://rudrastyh.com/woocommerce/bulk-change-custom-order-status.html
	 *
	 * @hooked admin_action_print_4x6_stamps_labels_pdf
	 * @see Shop_Order_Admin_List::register_bulk_action_print_4x6_stamps_labels_pdf()
	 * @see wp-admin/admin.php
	 */
	public function generate_print_4x6_stamps_labels_pdf(): void {

		// If an array with order IDs is not presented, exit the function.
		// 'post' as in post id, not HTTP POST.
		if ( ! isset( $_REQUEST['post'] ) && ! is_array( $_REQUEST['post'] ) ) {

			// TODO: Admin notice to say "none selected".

			return;
		}

		$order_ids = array_map( 'intval', $_REQUEST['post'] );

		$orders = array();

		// Purchase labels if they haven't already been (e.g. if auto-purchase is disabled).
		foreach ( $order_ids as $order_id ) {

			$order = wc_get_order( $order_id );

			if ( ! ( $order instanceof WC_Order ) ) {

				$message = 'No order found ' . $order_id;
				$this->logger->debug( $message, array( 'order_id' => $order_id ) );

				continue;
			}

			$orders[] = $order;
		}

		foreach ( $orders as $order ) {
			$this->api->auto_purchase_stamps_for_order( $order );
		}

		$result = $this->api->generate_merged_4x6_pdf_for_orders( $orders );

		// Maybe mark orders complete.
		if ( ! is_null( $this->settings->get_order_status_after_bulk_printing() ) ) {

			foreach ( $result['order_ids_succeeded'] as $order_id ) {

				$order = wc_get_order( $order_id );

				if ( ! ( $order instanceof WC_Order ) ) {
					$this->logger->error( 'bad order id', array( 'order_id' => $order_id ) );
					continue;
				}

				$note = 'Bulk printed 4x6';
				$order->set_status( $this->settings->get_order_status_after_bulk_printing(), $note );
				$order->save();

			}
		}

		// TODO: Log the failures and display in admin later.

		if ( ! isset( $result['file'] ) || ! isset( $result['file']['url'] ) ) {
			return;
		}

		wp_safe_redirect( $result['file']['url'] );

		exit;
	}

}
