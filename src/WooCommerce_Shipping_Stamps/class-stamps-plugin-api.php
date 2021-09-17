<?php
/**
 * Since the plugin uses a lot of static methods, wrap them in an easy to substitute and easy to reason about class.
 *
 * @package           brianhenryie/wc-auto-purchase-stamps
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce_Shipping_Stamps;

use stdClass;
use WC_Order;
use WC_Stamps_API;
use WC_Stamps_Label;
use WP_Error;

/**
 * Wrap static methods and define option names.
 */
class Stamps_Plugin_API {

	const SAMPLES_ONLY_OPTION_NAME = 'wc_settings_stamps_sample_only';

	/**
	 * Runs a query for the custom post type `wc_stamps_label` whose parent post is the order id.
	 *
	 * @param int $order_id The WooCommerce order id (aka WordPress post ID).
	 *
	 * @return WC_Stamps_Label[]
	 */
	public function get_order_labels( int $order_id ): array {
		return \WC_Stamps_Labels::get_order_labels( $order_id );
	}

	/**
	 * Verifys/updates the shipping address and returns a "validated" hash that is later compared when purchasing the shipping label.
	 *
	 * @param WC_Order $order The WooCommerce order.
	 *
	 * @return array{matched:bool, matched_zip:bool, hash:string, override_hash:string, address:array}
	 */
	public function verify_address( WC_Order $order ): array {
		return WC_Stamps_API::verify_address( $order );
	}

	/**
	 * Gets the available rates to the order's shipping address, from the address specified in the Stamps.com settings.
	 *
	 * @param WC_Order                                                                                                    $order The WooCommerce order.
	 * @param array{date:string, type:string, value:float|string, weight:float, length:float, width: float,height: float} $rates_args The sending date, dimensions, value, and package type.
	 *
	 * @return array<array{cost: string, service: string, package: string, name: string, dim_weighting: string, rate_object: stdClass}>|WP_Error
	 */
	public function get_rates( WC_Order $order, array $rates_args ) {
		return WC_Stamps_API::get_rates( $order, $rates_args );
	}

	/**
	 * Purchase the shipping label.
	 *
	 * Uses many of the settings saved in Stamps.com settings UI.
	 *
	 * @param WC_Order                               $order The WooCommerce order.
	 * @param array{rate: stdClass, customs: ?array} $args The chosen rate object and customs array.
	 *
	 * @return WC_Stamps_Label|WP_Error
	 */
	public function get_label( WC_Order $order, array $args ) {
		return WC_Stamps_API::get_label( $order, $args );
	}
}
