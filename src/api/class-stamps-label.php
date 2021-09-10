<?php
/**
 * Created by PhpStorm.
 * User: BrianHenryIE
 * Date: 5/6/18
 * Time: 3:08 PM
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\api;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Exception;
use WC_Data_Exception;
use WC_Order;
use WC_Order_Item_Product;
use WC_Stamps_API;
use WC_Stamps_Label;
use WP_Error;

class Stamps_Label {

	use LoggerAwareTrait;

	public const LABEL_PNG_FILE_PATH_META_KEY = 'bh-wc-auto-purchase-stamps-png-relative-file-path';

	/** @var WC_Stamps_Label $stamps_label */
	protected $stamps_label;

	protected $saved_label_relative_path;

	/**
	 * @var Settings_Interface
	 */
	protected $settings;

	/**
	 * Stamps_Label constructor.
	 *
	 * @param Settings_Interface $settings
	 * @param LoggerInterface    $logger
	 */
	public function __construct( $settings, $logger ) {
		$this->settings = $settings;
		$this->logger   = $logger;
	}

	/**
	 * Stamps_Label constructor.
	 *
	 * @param WC_Order $order
	 */
	public function do_order( $order ) {

		// validate the address first
		// how to notify staff of problem?
		// use bad-address status?

		if ( ! $this->validate_address( $order ) ) {

			return;
		}

		// $rates[]  (object) { 'cost', 'service', 'package', 'name', 'dim_weighting', 'rate_object' }

		/** @var array() $rates */
		$rates = $this->fetch_rates( $order );

		// TODO MORE RULES!

		// Returns weight in lbs (since that's what Stamps wants).
		$order_dimensions = $this->get_order_dimensions( $order );

		// If package weight over 1 lb, Priority Mail.
		// If package weight under 1 lb, First Class US-FC.

		if ( $order_dimensions['weight'] < 1 ) {
			$selected_rate_service_code = 'US-FC';
		} else {
			$selected_rate_service_code = 'US-PM';
		}

		// This should be a preference list, i.e. first class, then priority mail.
		$selected_rate_service_code = apply_filters( 'bh_wc_stamps_invoice_rate_service_code', $selected_rate_service_code, $order, $order_dimensions, $rates );

		$selected_rate = null;

		foreach ( $rates as $rate ) {

			// {"soap:Server":["Invalid rate. Ship date 1\/1\/0001 is not in the future."]}

			if ( $rate->service === $selected_rate_service_code ) {

				$selected_rate = $rate->rate_object;
				break;
			}
		}

		if ( null === $selected_rate ) {

			$this->logger->error( 'no rate matching service code ' . $selected_rate_service_code, array( 'order_id' => $order->get_id() ) );

			return;
		}

		// Invalid rate. You cannot choose Delivery Confirmation (US-A-DC) with this Service Type and Postcard.
		$rate_addons = array( 'AddOnType' => 'US-A-DC' );

		// Overwrite the available addons with chosen ones...

		// TODO: make sure they don't contradict "ProhibitedWithAnyOf".

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$selected_rate->AddOns->AddOnV7 = $rate_addons;

		$customs = null;
		$customs = apply_filters( 'bh_wc_stamps_invoice_customs', $customs, $order, $rates );

		/** @var WC_Stamps_Label $stamps_label */
		$new_label = $this->purchase_label( $order, $selected_rate, $customs );

		if ( is_wp_error( $new_label ) ) {

			$this->logger->warning( 'Failed to purchase label for ' . $order->get_id() );

			return;
		}

		// TODO: The $selected_rate contains the cost. Save it!

		$this->stamps_label = $new_label;
		$tracking_number    = $this->stamps_label->get_tracking_number();

		// Add tracking number to Shipment Tracking plugin (but not when buying samples on the live site).
		if ( function_exists( 'wc_st_add_tracking_number' ) && ! ( 'yes' === get_option( 'wc_settings_stamps_sample_only' ) && 'production' === wp_get_environment_type() ) ) {

			// Change "9400111969000940000011"  to "9400 1119 6900 0940 0000 11".
			$tracking_number = trim( chunk_split( $tracking_number, 4, ' ' ) );

			wc_st_add_tracking_number( $order->get_id(), $tracking_number, 'USPS', time() );
		}

		$label_url = $this->stamps_label->get_label_url();

		// TODO: Use wp_handle_sideload() which takes care of a lot of this.
		// e.g. mkdir handling.
		// e.g. "file already exists callback"

		try {
			$upload_dir = $this->get_upload_dir_for_order( $order );
		} catch ( Exception $e ) {

			// We have purchased it but failed to save it

			return;
		}

		$label_url = $this->get_png_from_url( $label_url );

		// Find a filename that has not been used.
		// "/path/to/wp-content/uploads/private/2020/10/order-label-2265-2-9400111969009940000011.png".
		$i = 1;
		do {
			$label_output_filepath = $upload_dir . "/order-{$order->get_id()}-label-{$i}-{$tracking_number}.png";
			$i++;
		} while ( file_exists( $label_output_filepath ) );

		/* Attempt to open */
		$image = @imagecreatefrompng( $label_url );

		/* See if it failed */
		if ( ! $image ) {
			$this->logger->error( 'Failed saving shipping label PNG for ' . $order->get_id(), array( 'label_url' => $label_url ) );
			return;
		}

		$rotation = imagerotate( $image, '0', 0 );
		imagepng( $rotation, $label_output_filepath );

		$label_output_relative_filepath = str_replace( ABSPATH, '', $label_output_filepath );

		$this->logger->info( "Saved shipping label PNG for order {$order->get_id()} at {$label_output_relative_filepath}" );

		$order->add_meta_data( self::LABEL_PNG_FILE_PATH_META_KEY, $label_output_relative_filepath );

		$this->saved_label_relative_path = $label_output_relative_filepath;

		// Maybe change order status to wc-shippingpurchased.
		if ( ! is_null( $this->settings->order_status_after_purchase() ) ) {
			$order->set_status( $this->settings->order_status_after_purchase() );
		}

		$order->save();

		// TODO? generate letter pdf?
		// if( class_exists...)
	}

	/**
	 * @param WC_Order $order
	 *
	 * @return string
	 * @throws Exception
	 */
	protected function get_upload_dir_for_order( $order ) {
		$upload     = wp_upload_dir();
		$upload_dir = $upload['basedir'] . '/private/' . $order->get_date_created()->format( 'Y' ) . '/' . $order->get_date_created()->format( 'm' );

		// Create the folder if absent.
		if ( ! file_exists( $upload_dir ) ) {

			if ( ! mkdir( $upload_dir, ( fileperms( ABSPATH ) & 0777 | 0755 ), true ) ) {

				$this->logger->error( 'failed to created folder for saving shipping labels' );

				throw new \Exception();
			}
		}

		return $upload_dir;
	}

	/**
	 * Generate the PNG URL for the Stamps label from the original URL which could be a png or a pdf etc.
	 *
	 * @param string $url The URL provided by the Stamps Integration for the pdf|png|... that was created.
	 *
	 * @return string
	 */
	protected function get_png_from_url( $url ) {

		if ( 1 === preg_match( '/(https:\/\/swsim.stamps.com\/Label\/label.ashx\/)[^\?]*(.*)/', $url, $output_array ) ) {

			return $output_array[1] . 'label-300.png' . $output_array[2];

		} else {

			$this->logger->error( 'Failed to parse the stamps label URL', array( 'url' => $url ) );

			return null;
		}
	}

	/**
	 * Verifies the address with USPS via Stamps.com and adds a hash to the order metadata
	 * which will be later checked when purchasing the stamps.
	 *
	 * @param WC_Order $order
	 *
	 * @see WC_Stamps_Order::ajax_accept_address()
	 *
	 * @return array|bool|Exception|WC_Data_Exception|WP_Error return true on success
	 */
	public function validate_address( $order ) {

		$this->logger->debug( 'Validating address for order ' . $order->get_id() );

		/**
		 * Verify the order's address with Stamps.com. A correct address and hash will be
		 * returned. The correct address will replace the existing order address.
		 *
		 * @var array|WP_Error $result
		 */
		$result = WC_Stamps_API::verify_address( $order );

		if ( is_wp_error( $result ) ) {

			$this->logger->error( $result->get_error_message(), array( 'wp_error' => $result ) );

			return false;
		}

		if ( false === $result['matched'] ) {

			// TODO: Should this be a notice?!
			$this->logger->info( 'Failed to validate address for order ' . $order->get_id() );

			return false;
		}

		$result_hash  = isset( $result['hash'] ) ? $result['hash'] : '';
		$overide_hash = isset( $result['overide_hash'] ) ? $result['overide_hash'] : '';

		$order->update_meta_data( '_stamps_response', $result );
		$order->update_meta_data( '_stamps_hash', $result_hash );
		$order->update_meta_data( '_stamps_override_hash', $overide_hash );

		$order->save_meta_data();

		$result = $order->get_meta( '_stamps_response', true );

		$shipping_name  = explode( ' ', $result['address']['full_name'] );
		$shipping_last  = array_pop( $shipping_name );
		$shipping_first = implode( ' ', $shipping_name );

		try {
			$order->set_shipping_first_name( $shipping_first );
			$order->set_shipping_last_name( $shipping_last );
			$order->set_shipping_company( $result['address']['company'] );
			$order->set_shipping_address_1( $result['address']['address_1'] );
			$order->set_shipping_address_2( $result['address']['address_2'] );
			$order->set_shipping_city( $result['address']['city'] );
			$order->set_shipping_state( $result['address']['state'] );
			$order->set_shipping_postcode( $result['address']['postcode'] );
			if ( ! empty( $result['address']['country'] ) ) {
				$order->set_shipping_country( $result['address']['country'] );
			}
			$order->save();
		} catch ( WC_Data_Exception $e ) {

			// Unlikely to happen since it's not user input.
			// "Throws exception when invalid data is found.".

			$this->logger->error( $e->getMessage(), array( 'exception' => $e ) );

			return false;
		}

		$formatted_shipping_address_hash = md5( $order->get_formatted_shipping_address() );

		$order->update_meta_data( '_stamps_verified_address_hash', $formatted_shipping_address_hash );
		$order->save_meta_data();

		$this->logger->info( 'Validated address for order ' . $order->get_id() );

		return true;
	}


	// $settings = new BH_WC_Auto_Purchase_Stamps\api\Settings();
	// $logger = BH_WC_Auto_Purchase_Stamps\BrianHenryIE\WP_Logger\Logger::instance( $settings );
	// $stamps_label = new BH_WC_Auto_Purchase_Stamps\api\Stamps_Label( $settings, $logger );
	// $order = new WC_Order(58618);
	// $dimension = $stamps_label->get_order_dimensions( $order );
	/**
	 * @param WC_Order $order
	 */
	public function get_order_dimensions( $order ) {

		$order_dimensions = array();

		$package_weight = 0.0;
		$package_volume = 0.0;

		/** @var WC_Order_Item_Product[] $items */
		$items = $order->get_items();
		foreach ( $items as $item ) {

			if ( ! ( $item instanceof WC_Order_Item_Product ) ) {
				continue;
			}

			$product = $item->get_product();

			// TODO: What is the policy when a product's weight has not been set?

			if ( ! is_numeric( $product->get_weight() ) || ! is_numeric( $item->get_quantity() ) ) {
				$context = array(
					'product'              => $product,
					'product_name'         => $product->get_name(),
					'product_id'           => $product->get_id(),
					'product->get_weight'  => $product->get_weight(),
					'item->get_quantity()' => $item->get_quantity(),
				);
				$this->logger->warning( 'Weight or quantity were not a number. PHP Warning:  A non-numeric value encountered in', $context );
			} else {

				$package_weight += $product->get_weight() * $item->get_quantity();

			}

			if ( is_numeric( $product->get_height() ) && is_numeric( $product->get_width() ) && is_numeric( $product->get_length() ) ) {
				$product_volume  = $product->get_height() * $product->get_width() * $product->get_length();
				$package_volume += $product_volume * $item->get_quantity();
			} else {
				$this->logger->warning(
					'A non-numeric value encountered in class-stamps-label.php on... Package volume may be 0.',
					array(
						'product'    => $product->get_name(),
						'product_id' => $product->get_id(),
					)
				);
			}
		}

		/**
		 * The WooComerce setting for product weight unit.
		 * One of: kg|g|lb|oz
		 *
		 * @see wp-admin/admin.php?page=wc-settings&tab=products
		 */
		$weight_unit = get_option( 'woocommerce_weight_unit' );

		// Stamps.com Integration expects weights in lbs.
		switch ( $weight_unit ) {
			case 'kg':
				$order_dimensions['weight'] = $package_weight * 2.2;
				break;
			case 'g':
				$order_dimensions['weight'] = $package_weight * 0.0022;
				break;
			case 'lb':
				$order_dimensions['weight'] = $package_weight;
				break;
			case 'oz':
				$order_dimensions['weight'] = $package_weight / 16;
				break;
		}

		/**
		 * The WooComerce setting for product dimensions unit.
		 * One of: m|cm|mm|in|yd.
		 *
		 * @see wp-admin/admin.php?page=wc-settings&tab=products
		 */
		$dimension_unit = get_option( 'woocommerce_dimension_unit' );

		// Stamps.com Integration expects dimensions in inches.
		switch ( $dimension_unit ) {
			case 'm':
				$cubed_root_volume = pow( $package_volume, 1 / 3 ) * 39.3;
				break;
			case 'cm':
				$cubed_root_volume = pow( $package_volume, 1 / 3 ) * 0.39;
				break;
			case 'mm':
				$cubed_root_volume = pow( $package_volume, 1 / 3 ) * 0.039;
				break;
			case 'in':
				$cubed_root_volume = pow( $package_volume, 1 / 3 );
				break;
			case 'yd':
				$cubed_root_volume = pow( $package_volume, 1 / 3 ) * 36;
				break;
		}

		// TODO: Envelope sizes.
		// There are maximum dimensioins... i.e. length might be ok but an height will be too big.

		$order_dimensions['length'] = $cubed_root_volume;
		$order_dimensions['width']  = $cubed_root_volume;
		$order_dimensions['height'] = $cubed_root_volume;

		$this->logger->debug(
			'calculated dimensions for order ' . $order->get_id() . ' ' . wp_json_encode( $order_dimensions ),
			array(
				'order'      => $order,
				'dimensions' => $order_dimensions,
			)
		);

		$envelope_weight_oz = 1.5;

		$order_dimensions['weight'] = $order_dimensions['weight'] + ( $envelope_weight_oz / 16 );

		return $order_dimensions;
	}

	/**
	 *
	 *
	 *
	 * @param WC_Order $order
	 *
	 * @return false|array of viable rates
	 */
	public function fetch_rates( $order ) {

		$this->logger->debug( 'Fetching rates for ' . $order->get_id() );

		$rates_args = array();

		// TODO: test the empty string. Test an array

		// Settings should specify available envelope sizes and this should try to constrain itself:

		// http://www.stamps.com/xml/namespace/2015/12/swsim/swsimV50:PackageTypeV6
		// <s:simpleType name="PackageTypeV6">
		// <s:restriction base="s:string">
		// <s:enumeration value="Unknown" />
		// <s:enumeration value="Postcard" />
		// <s:enumeration value="Letter" />
		// <s:enumeration value="Large Envelope or Flat" />
		// <s:enumeration value="Thick Envelope" />
		// <s:enumeration value="Package" />
		// <s:enumeration value="Flat Rate Box" />
		// <s:enumeration value="Small Flat Rate Box" />
		// <s:enumeration value="Large Flat Rate Box" />
		// <s:enumeration value="Flat Rate Envelope" />
		// <s:enumeration value="Flat Rate Padded Envelope" />
		// <s:enumeration value="Large Package" />
		// <s:enumeration value="Oversized Package" />
		// <s:enumeration value="Regional Rate Box A" />
		// <s:enumeration value="Regional Rate Box B" />
		// <s:enumeration value="Legal Flat Rate Envelope" />
		// <s:enumeration value="Regional Rate Box C" />
		// </s:restriction>

		// 'any' is an option (empty string?)

		$rates_args['date']  = gmdate( 'Y-m-d' );
		$rates_args['value'] = $order->get_total();

		$order_dimensions = $this->get_order_dimensions( $order );

		$rates_args['weight'] = $order_dimensions['weight'];
		$rates_args['length'] = $order_dimensions['length'];
		$rates_args['width']  = $order_dimensions['width'];
		$rates_args['height'] = $order_dimensions['height'];

		$rates_args['type'] = 'Package';

		$this->logger->debug(
			'calling  WC_Stamps_API::get_rates( $order, $rates_args )',
			array(
				'order'      => $order,
				'rates_args' => $rates_args,
			)
		);

		// Expects weight in lbs.
		$rates = WC_Stamps_API::get_rates( $order, $rates_args );

		// TODO: These aren't always plugin errors, e.g. "no rates available".
		if ( is_wp_error( $rates ) ) {

			$this->logger->error( $rates->get_error_message(), array( 'wp_error' => $rates ) );

			return false;
		}

		$this->logger->info( 'Retrieved rates for order ' . $order->get_id() );

		return $rates;
	}


	/**
	 * @param WC_Order $order
	 * @param array    $rate
	 * @param $customs
	 *
	 * @return WC_Stamps_Label|null
	 */
	public function purchase_label( $order, $rate, $customs = null ) {

		$this->logger->debug(
			'Purchasing label for ' . $order->get_id(),
			array(
				'order'   => $order,
				'rate'    => $rate,
				'customs' => $customs,
			)
		);

		/** @var WC_Stamps_Label $stamps_label */
		$stamps_label = WC_Stamps_API::get_label(
			$order,
			array(
				'rate'    => $rate,
				'customs' => $customs,
			)
		);

		if ( is_wp_error( $stamps_label ) ) {
			/** @var WP_Error $stamps_label */

			// Invalid rate. USPS_Delivery_Confirmation is not allowed for Letter Priority
			// Invalid rate. Adult Signature Required (US-A-ASR) and USPS Tracking (US-A-DC) cannot be used at the same time.

			// $error_message = $stamps_label->get_error_message();
			// if( strpos( $error_message, 'Invalid rate' ) === 0) {}

			// TODO: context
			$this->logger->error(
				$stamps_label->get_error_message(),
				array(
					'order'   => $order,
					'rate'    => $rate,
					'customs' => $customs,
				)
			);

			return null;
		}

		return $stamps_label;
	}

	/**
	 * The object generated by the Stamps.com Integration.
	 *
	 * @return WC_Stamps_Label
	 */
	public function get_stamps_label() {
		return $this->stamps_label;
	}

	/**
	 * The PNG file for the shipping label.
	 *
	 * @return string
	 */
	public function get_saved_label_relative_path() {
		return $this->saved_label_relative_path;
	}

}
