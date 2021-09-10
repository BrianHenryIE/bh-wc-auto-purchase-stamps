<?php

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\API;

interface API_Interface {

	public function auto_purchase_stamps_for_order( $order_id ): void;
	public function purchase_stamps_for_order( $order_id ): void;


	public function generate_merged_4x6_pdf_for_orders( $order_ids );
}
