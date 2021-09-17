<?php
/**
 * Created by PhpStorm.
 * User: BrianHenryIE
 * Date: 5/6/18
 * Time: 3:08 PM
 *
 * @package brianhenryie/bh-wc-auto-purchase-stamps
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\API;

use BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce_Shipping_Stamps\Stamps_Plugin_API;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use stdClass;
use WC_Data_Exception;
use WC_Order;
use WC_Order_Item_Product;
use WC_Stamps_API;
use WC_Stamps_Label;
use WP_Error;

class Stamps_Label {

	use LoggerAwareTrait;

	public const LABEL_PDF_FILES_PATH_META_KEY = 'bh_wc_auto_purchase_stamps_png_relative_file_path';

	/**
	 * @var Settings_Interface
	 */
	protected $settings;

	protected Stamps_Plugin_API $stamps_plugin_api;

	/**
	 * Stamps_Label constructor.
	 *
	 * @param Settings_Interface $settings The plugin settings.
	 * @param LoggerInterface    $logger A PSR logger.
	 */
	public function __construct( Settings_Interface $settings, LoggerInterface $logger, ?Stamps_Plugin_API $stamps_plugin_api = null ) {
		$this->settings = $settings;
		$this->setLogger( $logger );

		$this->stamps_plugin_api = $stamps_plugin_api ?? new Stamps_Plugin_API();
	}

	/**
	 * Try to purchase a shipping label.
	 *
	 * * validate the address
	 * (how to notify staff of problem?)
	 *
	 * Order notes added.
	 *
	 * @param WC_Order $order The WooCommerce order.
	 *
	 * @return array{success:bool,message:string,file?:array}
	 */
	public function do_order( WC_Order $order ): array {

		$context             = array();
		$order_id            = $order->get_id();
		$context['order_id'] = $order_id;

		$this->logger->debug( 'Attempting to purchase Stamps.com shipping label for order ' . $order->get_id(), $context );

		if ( ! $this->validate_address( $order ) ) {

			$message = 'Error: invalid address. Unable to purchase Stamps.com shipping label';
			$order->add_order_note( $message );
			$order->save();

			return array(
				'success' => false,
				'message' => $message,
			);
		}

		// $rates[]  (object) { 'cost', 'service', 'package', 'name', 'dim_weighting', 'rate_object' }

		/** @var stdClass[] $rates */
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

			$message = 'no rate matching service code ' . $selected_rate_service_code;
			$this->logger->error( $message, $context );

			return array(
				'success' => false,
				'message' => $message,
			);
		}

		// Invalid rate. You cannot choose Delivery Confirmation (US-A-DC) with this Service Type and Postcard.
		$rate_addons = array( 'AddOnType' => 'US-A-DC' );

		// Overwrite the available addons with chosen ones...

		// TODO: make sure they don't contradict "ProhibitedWithAnyOf".

		// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$selected_rate->AddOns->AddOnV7 = $rate_addons;

		$customs = null;
		$customs = apply_filters( 'bh_wc_stamps_invoice_customs', $customs, $order, $rates );

		/** @var ?WC_Stamps_Label $purchased_label */
		$purchased_label = $this->purchase_label( $order, $selected_rate, $customs );

		if ( is_null( $purchased_label ) ) {

			$message = 'Failed to purchase label for ' . $order_id;
			$this->logger->error( $message, $context );

			return array(
				'success' => false,
				'message' => $message,
			);
		}

		// TODO: The $selected_rate contains the cost. Save it!

		$tracking_number = $purchased_label->get_tracking_number();

		// Add tracking number to Shipment Tracking plugin (but not when buying samples on the live site).
		if ( function_exists( 'wc_st_add_tracking_number' ) && 'yes' !== get_option( Stamps_Plugin_API::SAMPLES_ONLY_OPTION_NAME ) && 'production' === wp_get_environment_type() ) {

			// Change "9500111112340000000011"  to "9500 1111 1234 0000 0000 11".
			if ( is_numeric( $tracking_number ) ) {
				$tracking_number = trim( chunk_split( $tracking_number, 4, ' ' ) );
			}

			wc_st_add_tracking_number( $order->get_id(), $tracking_number, 'USPS', time() );
		}

		$label_url = $purchased_label->get_label_url();

		$filename = "order-$order_id-stamps-label-4x6-$tracking_number.pdf";

		$order_time = $order->get_date_paid();

		/** @var \BrianHenryIE\WP_Private_Uploads\API\API_Interface $bh_wp_private_uploads */
		global $bh_wp_private_uploads;

		if ( empty( $bh_wp_private_uploads ) ) {
			return array(
				'success' => false,
				'message' => 'Private uploads plugin unavailable. Cannot download and save PDF.',
			);
		}

		/**
		 * @var array{file?:string,url?:string,type?:string,error?:string} $file
		 */
		$file = $bh_wp_private_uploads->download_remote_file_to_private_uploads( $label_url, $filename, $order_time );

		$context['file_download'] = $file;

		/* See if it failed */
		if ( isset( $file['error'] ) ) {

			$context['label_url'] = $label_url;
			$message              = 'Failed saving shipping label PDF for ' . $order_id;

			$this->logger->error( $message, $context );

			return array(
				'success' => false,
				'message' => $message,
			);
		}

		/**
		 * @var array{file:string,url:string,type:string} $file
		 */

		$label_output_relative_filepath = str_replace( WP_CONTENT_DIR . DIRECTORY_SEPARATOR, '', $file['file'] );

		$message = "Saved shipping label PDF for order {$order->get_id()} at {$label_output_relative_filepath}";
		$this->logger->info( $message );

		$labels_filepaths = $order->get_meta( self::LABEL_PDF_FILES_PATH_META_KEY, true );
		if ( empty( $labels_filepaths ) ) {
			$labels_filepaths = array();
		}
		$labels_filepaths[ $purchased_label->get_tracking_number() ] = $label_output_relative_filepath;

		$order->add_meta_data( self::LABEL_PDF_FILES_PATH_META_KEY, $labels_filepaths, true );

		// Maybe change order status to wc-shippingpurchased.
		if ( ! is_null( $this->settings->get_order_status_after_purchase() ) ) {
			$order->set_status( $this->settings->get_order_status_after_purchase() );
		}

		$order->save();

		// TODO? generate letter pdf?
		// if( class_exists...)

		return array(
			'success' => true,
			'message' => $message,
			'context' => $context,
			'labels'  => $labels_filepaths,
		);
	}

	/**
	 * Generate the PNG URL for the Stamps label from the original URL which could be a png or a pdf etc.
	 *
	 * @param string $url The URL provided by the Stamps Integration for the pdf|png|... that was created.
	 *
	 * @return ?string
	 */
	protected function get_png_from_url( string $url ): ?string {

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
	 * @see WC_Stamps_Order::ajax_accept_address()
	 *
	 * @param WC_Order $order
	 *
	 * @return bool
	 */
	public function validate_address( WC_Order $order ) {

		$order_id            = $order->get_id();
		$context             = array();
		$context['order_id'] = $order_id;

		$this->logger->debug( 'Validating address for order ' . $order_id, $context );

		/**
		 * Verify the order's address with Stamps.com. A correct address and hash will be
		 * returned. The correct address will replace the existing order address.
		 *
		 * @var array{matched:bool,hash:string,override_hash:string,address:array}|WP_Error $result
		 */
		$result = $this->stamps_plugin_api->verify_address( $order );

		$context['verify_address_result'] = $result;

		if ( is_wp_error( $result ) ) {

			$this->logger->error( $result->get_error_message(), $context );

			return false;
		}

		if ( false === $result['matched'] ) {

			// TODO: Should this be a notice?!
			$this->logger->info( 'Failed to validate address for order ' . $order->get_id(), $context );

			return false;
		}

		$result_hash  = isset( $result['hash'] ) ? $result['hash'] : '';
		$overide_hash = isset( $result['overide_hash'] ) ? $result['overide_hash'] : '';

		$order->add_meta_data( '_stamps_response', $result, true );
		$order->add_meta_data( '_stamps_hash', $result_hash, true );
		$order->add_meta_data( '_stamps_override_hash', $overide_hash, true );

		$order->save_meta_data();

		try {
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

		$order->add_meta_data( '_stamps_verified_address_hash', $formatted_shipping_address_hash, true );
		$order->save_meta_data();

		$this->logger->info( 'Validated address for order ' . $order->get_id(), $context );

		return true;
	}

	/**
	 * Stamps.com Integration expects weights in lbs, dimensions in inches.
	 *
	 * This function takes in an order, get the products' dimensions' values, and converts them from the WooCommerce
	 * setting to lbs.
	 *
	 * @param WC_Order $order
	 * @return array{length:float,width:float,height:float,weight:float} dimension
	 */
	public function get_order_dimensions( WC_Order $order ): array {

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

			if ( ! ( $product instanceof \WC_Product ) ) {
				continue;
			}

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

		$volume_in_inches_cubed = wc_get_dimension( $package_volume, 'in', get_option( 'woocommerce_dimension_unit' ) );

		$order_dimensions['length'] = pow( $volume_in_inches_cubed / 24, 1 / 3 ) * 2;
		$order_dimensions['width']  = pow( $volume_in_inches_cubed / 24, 1 / 3 ) * 3;
		$order_dimensions['height'] = pow( $volume_in_inches_cubed / 24, 1 / 3 ) * 4;

		$this->logger->debug(
			'calculated dimensions for order ' . $order->get_id() . ' ' . wp_json_encode( $order_dimensions ),
			array(
				'order'      => $order,
				'dimensions' => $order_dimensions,
			)
		);

		$envelope_weight_oz = 1.5;

		$order_dimensions['weight'] = wc_get_weight( $package_weight, 'lbs', get_option( 'woocommerce_weight_unit' ) ) + ( $envelope_weight_oz / 16 );

		return $order_dimensions;
	}

	/**
	 *
	 * @param WC_Order $order The WooCommerce order.
	 *
	 * @return array<array{cost: string, service: string, package: string, name: string, dim_weighting: string, rate_object: stdClass}> Available rates.
	 */
	public function fetch_rates( WC_Order $order ): array {

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

		$rates = $this->stamps_plugin_api->get_rates( $order, $rates_args );

		// TODO: These aren't always plugin errors, e.g. "no rates available".
		if ( is_wp_error( $rates ) ) {

			$this->logger->error( $rates->get_error_message(), array( 'wp_error' => $rates ) );

			return array();
		}

		$this->logger->info( 'Retrieved rates for order ' . $order->get_id() );

		return $rates;
	}


	/**
	 * Purchases the shipping label as a 4x6 PDF.
	 *
	 * @param WC_Order $order The WooCommerce order.
	 * @param stdClass $rate The chosen rate.
	 * @param ?array   $customs Customs declaration.
	 *
	 * @return ?WC_Stamps_Label
	 */
	public function purchase_label( WC_Order $order, stdClass $rate, ?array $customs = null ): ?WC_Stamps_Label {

		$this->logger->debug(
			'Purchasing label for ' . $order->get_id(),
			array(
				'order'   => $order,
				'rate'    => $rate,
				'customs' => $customs,
			)
		);

		// 4x6 pdf.
		add_filter(
			'pre_option_wc_settings_stamps_image_type',
			function() {
				return 'Pdf';
			}
		);
		add_filter(
			'pre_option_wc_settings_stamps_paper_size',
			function() {
				return 'LabelSize';
			}
		);

		/** @var WC_Stamps_Label|WP_Error $stamps_label */
		$stamps_label = $this->stamps_plugin_api->get_label(
			$order,
			array(
				'rate'    => $rate,
				'customs' => $customs,
			)
		);

		if ( is_wp_error( $stamps_label ) ) {

			// TODO: What to do when the eror message is about 'Invalid rate'?
			// Invalid rate. USPS_Delivery_Confirmation is not allowed for Letter Priority
			// Invalid rate. Adult Signature Required (US-A-ASR) and USPS Tracking (US-A-DC) cannot be used at the same time.

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

}
