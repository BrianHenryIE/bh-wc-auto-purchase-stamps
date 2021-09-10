<?php
/**
 * Bulk print merged pdf.
 *
 * wp-admin/edit.php?post_type=shop_order
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\woocommerce;

use BrianHenryIE\WC_Auto_Purchase_Stamps\api\API_Interface;
use BrianHenryIE\WC_Auto_Purchase_Stamps\api\Settings_Interface;
use Psr\Log\LoggerInterface;
use WC_Order;

class Shop_Order_Admin_List {

	/** @var API_Interface */
	protected $api;

	/** @var Settings_Interface */
	protected $settings;

	/** @var LoggerInterface */
	protected $logger;

	/**
	 *
	 *
	 * @param API_Interface      $api
	 * @param Settings_Interface $plugin_name
	 * @param LoggerInterface    $version
	 */
	public function __construct( $api, $settings, $logger ) {
		$this->logger   = $logger;
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

	 * @param string[] $bulk_actions
	 * @return string[]
	 */
	public function register_bulk_action_print_4x6_stamps_labels_pdf( $bulk_actions ) {

		$bulk_actions['print_4x6_stamps_labels_pdf'] = 'Print 4x6 Stamps.com labels PDF';

		return $bulk_actions;
	}

	/**
	 * @see https://rudrastyh.com/woocommerce/bulk-change-custom-order-status.html
	 *
	 * @hooked admin_action_print_shipping_labels_pdf
	 */
	public function generate_print_4x6_stamps_labels_pdf() {

		// If an array with order IDs is not presented, exit the function.
		// 'post' as in post id, not HTTP POST.
		if ( ! isset( $_REQUEST['post'] ) && ! is_array( $_REQUEST['post'] ) ) {

			// TODO: Admin notice to say "none selected".

			return;
		}

		// TODO: sanitize
		$order_ids = $_REQUEST['post'];

		// Purchase labels if they haven't already been (e.g. if auto-purchase os disabled).
		foreach ( $order_ids as $order_id ) {
			$this->api->purchase_stamps_for_order( $order_id );
		}

		$result = $this->api->generate_merged_4x6_pdf_for_orders( $order_ids );

		foreach ( $result->succeeded_ids as $order_id ) {

			$order = new WC_Order( $order_id );

			$order_note = 'Label bulk printed:';

			if ( ! is_null( $this->settings->order_status_after_bulk_printing() ) && Order_Status::PRINTED_STATUS !== $order->get_status() ) {
				$order->set_status( $this->settings->order_status_after_bulk_printing(), $order_note, true );
			} else {
				$order->add_order_note( $order_note );
			}

			$order->save();
		}

		// TODO: Log the failures and display in admin later.

		wp_redirect( $result->url );

		exit;
	}


	/**
	 * @hooked admin_notices
	 */
	function bulk_mark_packed_status_notice() {

		global $pagenow, $typenow;

		if ( $typenow == 'shop_order'
			 && $pagenow == 'edit.php'
			 && isset( $_REQUEST['marked_packed'] ) ) {

			$changed = intval( $_REQUEST['marked_packed'] );

			$message = sprintf( _n( 'Order status set to Packed.', '%s order statuses set to Packed.', $changed ), number_format_i18n( $changed ) );
			echo "<div class=\"updated\"><p>{$message}</p></div>";

		}

	}

}
