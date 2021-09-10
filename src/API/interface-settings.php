<?php

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\API;

interface Settings_Interface {

	const ORDER_STATUS_AFTER_PURCHASE_OPTION_NAME      = 'bh_auto_purchase_stamps_set_purchased_status';
	const ORDER_STATUS_AFTER_BULK_PRINTING_OPTION_NAME = 'bh_auto_purchase_stamps_set_printed_status';

	public function is_enabled(): bool;
	public function get_plugin_basename(): string;

	public function order_status_after_purchase(): ?string;
	public function order_status_after_bulk_printing(): ?string;

	public function get_plugin_version(): string;
}
