<?php
/**
 * Settings required by the plugin.
 *
 * @link       https://BrianHenryIE.com
 * @since      1.0.0
 *
 * @package    brianhenryie/wc-auto-purchase-stamps
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\API;

interface Settings_Interface {

	public function is_auto_purchase_enabled(): bool;

	public function get_plugin_basename(): string;

	public function get_order_status_after_purchase(): ?string;
	public function get_order_status_after_bulk_printing(): ?string;

}
