<?php

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\API;

use BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce\Shop_Order_Admin_List;
use WC_Order;

interface API_Interface {

	/**
	 * @param WC_Order $order A WooCommerce order.
	 *
	 * @return array{success:bool, message:string, labels?:array, file?:array}
	 */
	public function auto_purchase_stamps_for_order( WC_Order $order ): array;

	/**
	 * @param WC_Order $order A WooCommerce order.
	 *
	 * @return array{success:bool, message:string, file?:array}
	 */
	public function purchase_stamps_for_order( WC_Order $order ): array;

	/**
	 * @used-by Shop_Order_Admin_List::generate_print_4x6_stamps_labels_pdf()
	 *
	 * @param WC_Order[] $orders
	 *
	 * @return array{order_ids_succeeded:array, order_ids_failed:array, file?:array, filepath?:string}
	 */
	public function generate_merged_4x6_pdf_for_orders( array $orders ): array;
}
