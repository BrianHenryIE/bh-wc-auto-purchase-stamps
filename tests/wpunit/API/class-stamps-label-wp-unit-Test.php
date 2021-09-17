<?php

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\API;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WC_Auto_Purchase_Stamps\WooCommerce_Shipping_Stamps\Stamps_Plugin_API;
use WC_Order;
use WC_Product;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Auto_Purchase_Stamps\API\Stamps_Label
 */
class Stamps_Label_WP_Unit_Tests extends \Codeception\TestCase\WPTestCase {


	/**
	 * @covers ::get_order_dimensions
	 */
	public function test_dimensions() {

		$this->markTestSkipped();

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );

		$product = new WC_Product();
		$product->set_height( 3 );
		$product->set_length( 4 );
		$product->set_width( 5 );
		$product->set_weight( 123 );
		$product->save();

		$order = new \WC_Order();
		$order->add_product( $product );
		$order->save();

		$sut = new Stamps_Label( $settings, $logger );

		update_option( 'woocommerce_weight_unit', 'oz' );

		$dimensions = $sut->get_order_dimensions( $order );

		$this->assertArrayHasKey( 'weight', $dimensions );

		$this->assertEquals( 123 / 16, $dimensions['weight'] );
	}

	/**
	 * @covers ::validate_address
	 */
	public function test_validate_address() {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );

		$valid_response = array(
			'matched'      => true,
			'matched_zip'  => true,
			'hash'         => 'uZTitYTYmobDYDOhMphEkdbfuRBkZWFkYmVlZg==20220413C033',
			'overide_hash' => 'GcNlL9EkigdlP4X5WS60sYP3YftkZWFkYmVlZg==20220413C033',
			'address'      =>
				array(
					'full_name' => 'BRIAN HENRY',
					'company'   => '',
					'address_1' => '815 E ST APT 16',
					'address_2' => '',
					'city'      => 'SACRAMENTO',
					'state'     => 'CA',
					'postcode'  => '95814-1341',
					'country'   => '',
				),
		);

		$stamps_plugin_api = $this->makeEmpty(
			Stamps_Plugin_API::class,
			array(
				'verify_address' => $valid_response,
			)
		);

		$sut = new Stamps_Label( $settings, $logger, $stamps_plugin_api );

		$order = new WC_Order();

		$order->set_shipping_first_name( 'Brian' );
		$order->set_shipping_last_name( 'Henry' );
		$order->set_shipping_address_1( '815 E St' );
		$order->set_shipping_address_2( '#16' );
		$order->set_shipping_city( 'Sacramento' );
		$order->set_shipping_state( 'CA' );
		$order->set_shipping_postcode( '95814' );
		$order->set_shipping_country( 'US' );

		$order->save();

		$result = $sut->validate_address( $order );

		$this->assertTrue( $result );

	}

	/**
	 * @covers ::fetch_rates
	 */
	public function test_fetch_rates() {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );

		$rates_response = array(
			0 =>
				(object) array(
					'cost'          => '14.27',
					'service'       => 'US-PM',
					'package'       => 'Package',
					'name'          => 'Priority Mail',
					'dim_weighting' => 'Y',
					'rate_object'   =>
						(object) array(
							'FromZIPCode'             => '95819',
							'ToZIPCode'               => '95814-1341',
							'ToCountry'               => 'US',
							'Amount'                  => '14.27',
							'ServiceType'             => 'US-PM',
							'PrintLayout'             => 'Normal4X6',
							'DeliverDays'             => '1',
							'WeightLb'                => 6.0,
							'WeightOz'                => 5.5,
							'PackageType'             => 'Package',
							'Length'                  => 12.599210498949,
							'Width'                   => 12.599210498949,
							'Height'                  => 12.599210498949,
							'ShipDate'                => '2021-09-14',
							'DeliveryDate'            => '2021-09-15',
							'InsuredValue'            => '99.99',
							'CODValue'                => '99.99',
							'DeclaredValue'           => '99.99',
							'DimWeighting'            => 'Y',
							'AddOns'                  =>
								(object) array(
									'AddOnV7' =>
										array(
											0  =>
												(object) array(
													'Amount' => '3.15',
													'AddOnType' => 'US-A-INS',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-REG',
																	1 => 'US-A-CM',
																	2 => 'US-A-COD',
																	3 => 'SC-A-INS',
																),
														),
												),
											1  =>
												(object) array(
													'Amount' => '10.7',
													'AddOnType' => 'US-A-COD',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-RRM',
																	1 => 'US-A-INS',
																	2 => 'US-A-CM',
																),
														),
												),
											2  =>
												(object) array(
													'AddOnType' => 'US-A-DC',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-SC',
																	1 => 'US-A-CM',
																	2 => 'US-A-ASR',
																	3 => 'US-A-ASRD',
																),
														),
												),
											3  =>
												(object) array(
													'Amount' => '2.9',
													'AddOnType' => 'US-A-SC',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-DC',
																	1 => 'US-A-CM',
																	2 => 'US-A-RRM',
																	3 => 'US-A-ASR',
																	4 => 'US-A-ASRD',
																),
														),
												),
											4  =>
												(object) array(
													'Amount' => '3.75',
													'AddOnType' => 'US-A-CM',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-RRM',
																	1 => 'US-A-REG',
																	2 => 'US-A-DC',
																	3 => 'US-A-COD',
																	4 => 'US-A-SC',
																	5 => 'US-A-INS',
																	6 => 'US-A-SH',
																),
														),
												),
											5  =>
												(object) array(
													'Amount' => '3.05',
													'AddOnType' => 'US-A-RR',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' =>
																		array(
																			0 => 'US-A-COD',
																			1 => 'US-A-REG',
																			2 => 'US-A-CM',
																			3 => 'US-A-INS',
																			4 => 'US-A-ASR',
																			5 => 'US-A-ASRD',
																			6 => 'US-A-SC',
																		),
																),
														),
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' => 'US-A-RRM',
														),
												),
											6  =>
												(object) array(
													'Amount' => '13.75',
													'AddOnType' => 'US-A-REG',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-CM',
																	1 => 'US-A-INS',
																	2 => 'US-A-RRM',
																	3 => 'SC-A-INS',
																	4 => 'US-A-SH',
																),
														),
												),
											7  =>
												(object) array(
													'Amount' => '6',
													'AddOnType' => 'US-A-RD',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' =>
																		array(
																			0 => 'US-A-COD',
																			1 => 'US-A-REG',
																			2 => 'US-A-CM',
																			3 => 'US-A-INS',
																			4 => 'US-A-SC',
																		),
																),
														),
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-RRM',
																	1 => 'US-A-SH',
																),
														),
												),
											8  =>
												(object) array(
													'Amount' => '2.83',
													'AddOnType' => 'SC-A-INS',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-REG',
																	1 => 'US-A-INS',
																),
														),
												),
											9  =>
												(object) array(
													'AddOnType' => 'SC-A-HP',
													'ProhibitedWithAnyOf' =>
														(object) array(),
												),
											10 =>
												(object) array(
													'AddOnType' => 'US-A-NND',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' => 'US-A-COD',
																),
														),
												),
											11 =>
												(object) array(
													'Amount' => '4.3',
													'AddOnType' => 'US-A-RRM',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-REG',
																	1 => 'US-A-RD',
																	2 => 'US-A-COD',
																	3 => 'US-A-SC',
																	4 => 'US-A-CM',
																	5 => 'US-A-RR',
																),
														),
												),
											12 =>
												(object) array(
													'Amount' => '1.85',
													'AddOnType' => 'US-A-RRE',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' =>
																		array(
																			0 => 'US-A-CM',
																			1 => 'US-A-COD',
																			2 => 'US-A-REG',
																			3 => 'US-A-INS',
																		),
																),
														),
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-DC',
																	1 => 'US-A-SC',
																	2 => 'US-A-COM',
																	3 => 'US-A-RRM',
																	4 => 'US-A-SH',
																	5 => 'SC-A-INSRM',
																	6 => 'US-A-NND',
																),
														),
												),
											13 =>
												(object) array(
													'Amount' => '6.9',
													'AddOnType' => 'US-A-ASR',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-ASRD',
																	1 => 'US-A-DC',
																	2 => 'US-A-SC',
																),
														),
												),
											14 =>
												(object) array(
													'Amount' => '7.15',
													'AddOnType' => 'US-A-ASRD',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-ASR',
																	1 => 'US-A-DC',
																	2 => 'US-A-SC',
																),
														),
												),
											15 =>
												(object) array(
													'AddOnType' => 'US-A-HM',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-CR',
																	1 => 'US-A-LANS',
																	2 => 'US-A-LAWS',
																),
														),
												),
											16 =>
												(object) array(
													'AddOnType' => 'US-A-LANS',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-CR',
																	1 => 'US-A-HM',
																	2 => 'US-A-LAWS',
																),
														),
												),
											17 =>
												(object) array(
													'Amount' => '1.4',
													'AddOnType' => 'US-A-LAWS',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-CR',
																	1 => 'US-A-HM',
																	2 => 'US-A-LANS',
																),
														),
												),
											18 =>
												(object) array(
													'Amount' => '12.15',
													'AddOnType' => 'US-A-SH',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-COM',
																	1 => 'US-A-CM',
																	2 => 'US-A-REG',
																	3 => 'US-A-RD',
																	4 => 'US-A-RRE',
																),
														),
												),
											19 =>
												(object) array(
													'AddOnType' => 'US-A-PR',
												),
										),
								),
							'EffectiveWeightInOunces' => 224,
							'Zone'                    => 1,
							'RateCategory'            => 1000,
							'ToState'                 => 'CA',
						),
				),
			1 =>
				(object) array(
					'cost'          => '43.95',
					'service'       => 'US-XM',
					'package'       => 'Package',
					'name'          => 'Priority Mail Express',
					'dim_weighting' => 'Y',
					'rate_object'   =>
						(object) array(
							'FromZIPCode'             => '95819',
							'ToZIPCode'               => '95814-1341',
							'ToCountry'               => 'US',
							'Amount'                  => '43.95',
							'ServiceType'             => 'US-XM',
							'PrintLayout'             => 'Normal4X6',
							'DeliverDays'             => '1-2',
							'WeightLb'                => 6.0,
							'WeightOz'                => 5.5,
							'PackageType'             => 'Package',
							'Length'                  => 12.599210498949,
							'Width'                   => 12.599210498949,
							'Height'                  => 12.599210498949,
							'ShipDate'                => '2021-09-14',
							'InsuredValue'            => '99.99',
							'CODValue'                => '99.99',
							'DeclaredValue'           => '99.99',
							'DimWeighting'            => 'Y',
							'AddOns'                  =>
								(object) array(
									'AddOnV7' =>
										array(
											0  =>
												(object) array(
													'AddOnType' => 'US-A-INS',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-REG',
																	1 => 'US-A-CM',
																	2 => 'US-A-COD',
																	3 => 'SC-A-INS',
																),
														),
												),
											1  =>
												(object) array(
													'Amount' => '10.7',
													'AddOnType' => 'US-A-COD',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-RRM',
																	1 => 'US-A-INS',
																	2 => 'US-A-CM',
																),
														),
												),
											2  =>
												(object) array(
													'Amount' => '3.05',
													'AddOnType' => 'US-A-RR',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' => 'US-A-RRM',
														),
												),
											3  =>
												(object) array(
													'AddOnType' => 'US-A-DC',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-SC',
																	1 => 'US-A-CM',
																	2 => 'US-A-ASR',
																	3 => 'US-A-ASRD',
																	4 => 'US-A-SR',
																),
														),
												),
											4  =>
												(object) array(
													'Amount' => '2.83',
													'AddOnType' => 'SC-A-INS',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-REG',
																	1 => 'US-A-INS',
																),
														),
												),
											5  =>
												(object) array(
													'AddOnType' => 'SC-A-HP',
													'ProhibitedWithAnyOf' =>
														(object) array(),
												),
											6  =>
												(object) array(
													'AddOnType' => 'US-A-NND',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' => 'US-A-COD',
																),
														),
												),
											7  =>
												(object) array(
													'AddOnType' => 'US-A-SR',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' => 'US-A-DC',
														),
												),
											8  =>
												(object) array(
													'Amount' => '6.9',
													'AddOnType' => 'US-A-ASR',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-ASRD',
																	1 => 'US-A-DC',
																	2 => 'US-A-SC',
																),
														),
												),
											9  =>
												(object) array(
													'Amount' => '7.15',
													'AddOnType' => 'US-A-ASRD',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-ASR',
																	1 => 'US-A-DC',
																	2 => 'US-A-SC',
																),
														),
												),
											10 =>
												(object) array(
													'AddOnType' => 'US-A-CR',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-COD',
																	1 => 'US-A-ESH',
																	2 => 'US-A-NND',
																	3 => 'US-A-NDW',
																	4 => 'US-A-ASR',
																	5 => 'US-A-ASRD',
																	6 => 'US-A-HM',
																	7 => 'US-A-LANS',
																	8 => 'US-A-LAWS',
																	9 => 'US-A-1030',
																	10 => 'US-A-SH',
																	11 => 'US-A-PR',
																),
														),
												),
											11 =>
												(object) array(
													'AddOnType' => 'US-A-HM',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-CR',
																	1 => 'US-A-LANS',
																	2 => 'US-A-LAWS',
																),
														),
												),
											12 =>
												(object) array(
													'AddOnType' => 'US-A-LANS',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-CR',
																	1 => 'US-A-HM',
																	2 => 'US-A-LAWS',
																),
														),
												),
											13 =>
												(object) array(
													'Amount' => '1.4',
													'AddOnType' => 'US-A-LAWS',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-CR',
																	1 => 'US-A-HM',
																	2 => 'US-A-LANS',
																),
														),
												),
											14 =>
												(object) array(
													'Amount' => '12.15',
													'AddOnType' => 'US-A-SH',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-COM',
																	1 => 'US-A-CM',
																	2 => 'US-A-REG',
																	3 => 'US-A-RD',
																	4 => 'US-A-RRE',
																),
														),
												),
											15 =>
												(object) array(
													'AddOnType' => 'US-A-PR',
												),
										),
								),
							'EffectiveWeightInOunces' => 224,
							'Zone'                    => 1,
							'RateCategory'            => 1000,
							'ToState'                 => 'CA',
						),
				),
			2 =>
				(object) array(
					'cost'          => '6.97',
					'service'       => 'US-MM',
					'package'       => 'Package',
					'name'          => 'Media Mail',
					'dim_weighting' => 0,
					'rate_object'   =>
						(object) array(
							'FromZIPCode'             => '95819',
							'ToZIPCode'               => '95814-1341',
							'ToCountry'               => 'US',
							'Amount'                  => '6.97',
							'ServiceType'             => 'US-MM',
							'PrintLayout'             => 'Normal4X6',
							'DeliverDays'             => '2',
							'WeightLb'                => 6.0,
							'WeightOz'                => 5.5,
							'PackageType'             => 'Package',
							'Length'                  => 12.599210498949,
							'Width'                   => 12.599210498949,
							'Height'                  => 12.599210498949,
							'ShipDate'                => '2021-09-14',
							'DeliveryDate'            => '2021-09-16',
							'InsuredValue'            => '99.99',
							'CODValue'                => '99.99',
							'DeclaredValue'           => '99.99',
							'AddOns'                  =>
								(object) array(
									'AddOnV7' =>
										array(
											0  =>
												(object) array(
													'Amount' => '3.15',
													'AddOnType' => 'US-A-INS',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-REG',
																	1 => 'US-A-CM',
																	2 => 'US-A-COD',
																	3 => 'SC-A-INS',
																),
														),
												),
											1  =>
												(object) array(
													'Amount' => '10.7',
													'AddOnType' => 'US-A-COD',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-RRM',
																	1 => 'US-A-INS',
																	2 => 'US-A-CM',
																),
														),
												),
											2  =>
												(object) array(
													'AddOnType' => 'US-A-DC',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-SC',
																	1 => 'US-A-CM',
																	2 => 'US-A-ASR',
																	3 => 'US-A-ASRD',
																),
														),
												),
											3  =>
												(object) array(
													'Amount' => '2.9',
													'AddOnType' => 'US-A-SC',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-DC',
																	1 => 'US-A-CM',
																	2 => 'US-A-RRM',
																	3 => 'US-A-ASR',
																	4 => 'US-A-ASRD',
																),
														),
												),
											4  =>
												(object) array(
													'Amount' => '3.05',
													'AddOnType' => 'US-A-RR',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' =>
																		array(
																			0 => 'US-A-COD',
																			1 => 'US-A-REG',
																			2 => 'US-A-CM',
																			3 => 'US-A-INS',
																			4 => 'US-A-ASR',
																			5 => 'US-A-ASRD',
																			6 => 'US-A-SC',
																		),
																),
														),
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' => 'US-A-RRM',
														),
												),
											5  =>
												(object) array(
													'Amount' => '6',
													'AddOnType' => 'US-A-RD',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' =>
																		array(
																			0 => 'US-A-COD',
																			1 => 'US-A-REG',
																			2 => 'US-A-CM',
																			3 => 'US-A-INS',
																			4 => 'US-A-SC',
																		),
																),
														),
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-RRM',
																	1 => 'US-A-SH',
																),
														),
												),
											6  =>
												(object) array(
													'Amount' => '2.83',
													'AddOnType' => 'SC-A-INS',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-REG',
																	1 => 'US-A-INS',
																),
														),
												),
											7  =>
												(object) array(
													'AddOnType' => 'SC-A-HP',
													'ProhibitedWithAnyOf' =>
														(object) array(),
												),
											8  =>
												(object) array(
													'AddOnType' => 'US-A-NND',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' => 'US-A-COD',
																),
														),
												),
											9  =>
												(object) array(
													'Amount' => '4.3',
													'AddOnType' => 'US-A-RRM',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-REG',
																	1 => 'US-A-RD',
																	2 => 'US-A-COD',
																	3 => 'US-A-SC',
																	4 => 'US-A-CM',
																	5 => 'US-A-RR',
																),
														),
												),
											10 =>
												(object) array(
													'Amount' => '1.85',
													'AddOnType' => 'US-A-RRE',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' =>
																		array(
																			0 => 'US-A-CM',
																			1 => 'US-A-COD',
																			2 => 'US-A-REG',
																			3 => 'US-A-INS',
																		),
																),
														),
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-DC',
																	1 => 'US-A-SC',
																	2 => 'US-A-COM',
																	3 => 'US-A-RRM',
																	4 => 'US-A-SH',
																	5 => 'SC-A-INSRM',
																	6 => 'US-A-NND',
																),
														),
												),
											11 =>
												(object) array(
													'AddOnType' => 'US-A-HM',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' =>
																		array(
																			0 => 'US-A-DC',
																			1 => 'US-A-SC',
																		),
																),
														),
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-CR',
																	1 => 'US-A-LANS',
																	2 => 'US-A-LAWS',
																),
														),
												),
											12 =>
												(object) array(
													'AddOnType' => 'US-A-LANS',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' =>
																		array(
																			0 => 'US-A-DC',
																			1 => 'US-A-SC',
																		),
																),
														),
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-CR',
																	1 => 'US-A-HM',
																	2 => 'US-A-LAWS',
																),
														),
												),
											13 =>
												(object) array(
													'Amount' => '1.4',
													'AddOnType' => 'US-A-LAWS',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' =>
																		array(
																			0 => 'US-A-DC',
																			1 => 'US-A-SC',
																		),
																),
														),
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-CR',
																	1 => 'US-A-HM',
																	2 => 'US-A-LANS',
																),
														),
												),
											14 =>
												(object) array(
													'Amount' => '12.15',
													'AddOnType' => 'US-A-SH',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' =>
																		array(
																			0 => 'US-A-DC',
																			1 => 'US-A-SC',
																		),
																),
														),
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-COM',
																	1 => 'US-A-CM',
																	2 => 'US-A-REG',
																	3 => 'US-A-RD',
																	4 => 'US-A-RRE',
																),
														),
												),
										),
								),
							'EffectiveWeightInOunces' => 102,
							'Zone'                    => 1,
							'RateCategory'            => 1000,
							'ToState'                 => 'CA',
						),
				),
			3 =>
				(object) array(
					'cost'          => '13.77',
					'service'       => 'US-PS',
					'package'       => 'Package',
					'name'          => 'Parcel Select',
					'dim_weighting' => 'Y',
					'rate_object'   =>
						(object) array(
							'FromZIPCode'             => '95819',
							'ToZIPCode'               => '95814-1341',
							'ToCountry'               => 'US',
							'Amount'                  => '13.77',
							'ServiceType'             => 'US-PS',
							'PrintLayout'             => 'Normal4X6',
							'DeliverDays'             => '2',
							'WeightLb'                => 6.0,
							'WeightOz'                => 5.5,
							'PackageType'             => 'Package',
							'Length'                  => 12.599210498949,
							'Width'                   => 12.599210498949,
							'Height'                  => 12.599210498949,
							'ShipDate'                => '2021-09-14',
							'DeliveryDate'            => '2021-09-16',
							'InsuredValue'            => '99.99',
							'CODValue'                => '99.99',
							'DeclaredValue'           => '99.99',
							'DimWeighting'            => 'Y',
							'AddOns'                  =>
								(object) array(
									'AddOnV7' =>
										array(
											0  =>
												(object) array(
													'Amount' => '6.9',
													'AddOnType' => 'US-A-ASR',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-ASRD',
																	1 => 'US-A-DC',
																	2 => 'US-A-SC',
																),
														),
												),
											1  =>
												(object) array(
													'Amount' => '7.15',
													'AddOnType' => 'US-A-ASRD',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-ASR',
																	1 => 'US-A-DC',
																	2 => 'US-A-SC',
																),
														),
												),
											2  =>
												(object) array(
													'Amount' => '3.15',
													'AddOnType' => 'US-A-INS',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-REG',
																	1 => 'US-A-CM',
																	2 => 'US-A-COD',
																	3 => 'SC-A-INS',
																),
														),
												),
											3  =>
												(object) array(
													'Amount' => '10.7',
													'AddOnType' => 'US-A-COD',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-RRM',
																	1 => 'US-A-INS',
																	2 => 'US-A-CM',
																),
														),
												),
											4  =>
												(object) array(
													'AddOnType' => 'US-A-DC',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-SC',
																	1 => 'US-A-CM',
																	2 => 'US-A-ASR',
																	3 => 'US-A-ASRD',
																),
														),
												),
											5  =>
												(object) array(
													'Amount' => '2.9',
													'AddOnType' => 'US-A-SC',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-DC',
																	1 => 'US-A-CM',
																	2 => 'US-A-RRM',
																	3 => 'US-A-ASR',
																	4 => 'US-A-ASRD',
																),
														),
												),
											6  =>
												(object) array(
													'Amount' => '3.05',
													'AddOnType' => 'US-A-RR',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' =>
																		array(
																			0 => 'US-A-COD',
																			1 => 'US-A-REG',
																			2 => 'US-A-CM',
																			3 => 'US-A-INS',
																			4 => 'US-A-ASR',
																			5 => 'US-A-ASRD',
																			6 => 'US-A-SC',
																		),
																),
														),
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' => 'US-A-RRM',
														),
												),
											7  =>
												(object) array(
													'Amount' => '6',
													'AddOnType' => 'US-A-RD',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' =>
																		array(
																			0 => 'US-A-COD',
																			1 => 'US-A-REG',
																			2 => 'US-A-CM',
																			3 => 'US-A-INS',
																			4 => 'US-A-SC',
																		),
																),
														),
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-RRM',
																	1 => 'US-A-SH',
																),
														),
												),
											8  =>
												(object) array(
													'Amount' => '2.83',
													'AddOnType' => 'SC-A-INS',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-REG',
																	1 => 'US-A-INS',
																),
														),
												),
											9  =>
												(object) array(
													'AddOnType' => 'SC-A-HP',
													'ProhibitedWithAnyOf' =>
														(object) array(),
												),
											10 =>
												(object) array(
													'AddOnType' => 'US-A-NND',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' => 'US-A-COD',
																),
														),
												),
											11 =>
												(object) array(
													'Amount' => '4.3',
													'AddOnType' => 'US-A-RRM',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-REG',
																	1 => 'US-A-RD',
																	2 => 'US-A-COD',
																	3 => 'US-A-SC',
																	4 => 'US-A-CM',
																	5 => 'US-A-RR',
																),
														),
												),
											12 =>
												(object) array(
													'Amount' => '1.85',
													'AddOnType' => 'US-A-RRE',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' =>
																		array(
																			0 => 'US-A-CM',
																			1 => 'US-A-COD',
																			2 => 'US-A-REG',
																			3 => 'US-A-INS',
																		),
																),
														),
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-DC',
																	1 => 'US-A-SC',
																	2 => 'US-A-COM',
																	3 => 'US-A-RRM',
																	4 => 'US-A-SH',
																	5 => 'SC-A-INSRM',
																	6 => 'US-A-NND',
																),
														),
												),
											13 =>
												(object) array(
													'AddOnType' => 'US-A-HM',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-CR',
																	1 => 'US-A-LANS',
																	2 => 'US-A-LAWS',
																),
														),
												),
											14 =>
												(object) array(
													'AddOnType' => 'US-A-LANS',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-CR',
																	1 => 'US-A-HM',
																	2 => 'US-A-LAWS',
																),
														),
												),
											15 =>
												(object) array(
													'Amount' => '1.4',
													'AddOnType' => 'US-A-LAWS',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-CR',
																	1 => 'US-A-HM',
																	2 => 'US-A-LANS',
																),
														),
												),
											16 =>
												(object) array(
													'Amount' => '12.15',
													'AddOnType' => 'US-A-SH',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-COM',
																	1 => 'US-A-CM',
																	2 => 'US-A-REG',
																	3 => 'US-A-RD',
																	4 => 'US-A-RRE',
																),
														),
												),
											17 =>
												(object) array(
													'AddOnType' => 'US-A-PR',
												),
										),
								),
							'EffectiveWeightInOunces' => 224,
							'Zone'                    => 1,
							'RateCategory'            => 1000,
							'ToState'                 => 'CA',
						),
				),
			4 =>
				(object) array(
					'cost'          => '6.63',
					'service'       => 'US-LM',
					'package'       => 'Package',
					'name'          => 'Library Mail',
					'dim_weighting' => 0,
					'rate_object'   =>
						(object) array(
							'FromZIPCode'             => '95819',
							'ToZIPCode'               => '95814-1341',
							'ToCountry'               => 'US',
							'Amount'                  => '6.63',
							'ServiceType'             => 'US-LM',
							'PrintLayout'             => 'Normal4X6',
							'DeliverDays'             => '2-8',
							'WeightLb'                => 6.0,
							'WeightOz'                => 5.5,
							'PackageType'             => 'Package',
							'Length'                  => 12.599210498949,
							'Width'                   => 12.599210498949,
							'Height'                  => 12.599210498949,
							'ShipDate'                => '2021-09-14',
							'InsuredValue'            => '99.99',
							'CODValue'                => '99.99',
							'DeclaredValue'           => '99.99',
							'AddOns'                  =>
								(object) array(
									'AddOnV7' =>
										array(
											0  =>
												(object) array(
													'Amount' => '3.15',
													'AddOnType' => 'US-A-INS',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-REG',
																	1 => 'US-A-CM',
																	2 => 'US-A-COD',
																	3 => 'SC-A-INS',
																),
														),
												),
											1  =>
												(object) array(
													'Amount' => '10.7',
													'AddOnType' => 'US-A-COD',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-RRM',
																	1 => 'US-A-INS',
																	2 => 'US-A-CM',
																),
														),
												),
											2  =>
												(object) array(
													'AddOnType' => 'US-A-DC',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-SC',
																	1 => 'US-A-CM',
																	2 => 'US-A-ASR',
																	3 => 'US-A-ASRD',
																),
														),
												),
											3  =>
												(object) array(
													'Amount' => '2.9',
													'AddOnType' => 'US-A-SC',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-DC',
																	1 => 'US-A-CM',
																	2 => 'US-A-RRM',
																	3 => 'US-A-ASR',
																	4 => 'US-A-ASRD',
																),
														),
												),
											4  =>
												(object) array(
													'Amount' => '3.05',
													'AddOnType' => 'US-A-RR',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' =>
																		array(
																			0 => 'US-A-COD',
																			1 => 'US-A-REG',
																			2 => 'US-A-CM',
																			3 => 'US-A-INS',
																			4 => 'US-A-ASR',
																			5 => 'US-A-ASRD',
																			6 => 'US-A-SC',
																		),
																),
														),
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' => 'US-A-RRM',
														),
												),
											5  =>
												(object) array(
													'Amount' => '6',
													'AddOnType' => 'US-A-RD',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' =>
																		array(
																			0 => 'US-A-COD',
																			1 => 'US-A-REG',
																			2 => 'US-A-CM',
																			3 => 'US-A-INS',
																			4 => 'US-A-SC',
																		),
																),
														),
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-RRM',
																	1 => 'US-A-SH',
																),
														),
												),
											6  =>
												(object) array(
													'Amount' => '2.83',
													'AddOnType' => 'SC-A-INS',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-REG',
																	1 => 'US-A-INS',
																),
														),
												),
											7  =>
												(object) array(
													'AddOnType' => 'SC-A-HP',
													'ProhibitedWithAnyOf' =>
														(object) array(),
												),
											8  =>
												(object) array(
													'Amount' => '4.3',
													'AddOnType' => 'US-A-RRM',
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-REG',
																	1 => 'US-A-RD',
																	2 => 'US-A-COD',
																	3 => 'US-A-SC',
																	4 => 'US-A-CM',
																	5 => 'US-A-RR',
																),
														),
												),
											9  =>
												(object) array(
													'Amount' => '1.85',
													'AddOnType' => 'US-A-RRE',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' =>
																		array(
																			0 => 'US-A-CM',
																			1 => 'US-A-COD',
																			2 => 'US-A-REG',
																			3 => 'US-A-INS',
																		),
																),
														),
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-DC',
																	1 => 'US-A-SC',
																	2 => 'US-A-COM',
																	3 => 'US-A-RRM',
																	4 => 'US-A-SH',
																	5 => 'SC-A-INSRM',
																	6 => 'US-A-NND',
																),
														),
												),
											10 =>
												(object) array(
													'AddOnType' => 'US-A-HM',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' =>
																		array(
																			0 => 'US-A-DC',
																			1 => 'US-A-SC',
																		),
																),
														),
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-CR',
																	1 => 'US-A-LANS',
																	2 => 'US-A-LAWS',
																),
														),
												),
											11 =>
												(object) array(
													'AddOnType' => 'US-A-LANS',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' =>
																		array(
																			0 => 'US-A-DC',
																			1 => 'US-A-SC',
																		),
																),
														),
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-CR',
																	1 => 'US-A-HM',
																	2 => 'US-A-LAWS',
																),
														),
												),
											12 =>
												(object) array(
													'Amount' => '1.4',
													'AddOnType' => 'US-A-LAWS',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' =>
																		array(
																			0 => 'US-A-DC',
																			1 => 'US-A-SC',
																		),
																),
														),
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-CR',
																	1 => 'US-A-HM',
																	2 => 'US-A-LANS',
																),
														),
												),
											13 =>
												(object) array(
													'Amount' => '12.15',
													'AddOnType' => 'US-A-SH',
													'RequiresAllOf' =>
														(object) array(
															'RequiresOneOf' =>
																(object) array(
																	'AddOnTypeV7' =>
																		array(
																			0 => 'US-A-DC',
																			1 => 'US-A-SC',
																		),
																),
														),
													'ProhibitedWithAnyOf' =>
														(object) array(
															'AddOnTypeV7' =>
																array(
																	0 => 'US-A-COM',
																	1 => 'US-A-CM',
																	2 => 'US-A-REG',
																	3 => 'US-A-RD',
																	4 => 'US-A-RRE',
																),
														),
												),
											14 =>
												(object) array(
													'AddOnType' => 'US-A-PR',
												),
										),
								),
							'EffectiveWeightInOunces' => 102,
							'Zone'                    => 1,
							'RateCategory'            => 1000,
							'ToState'                 => 'CA',
						),
				),
		);

		$stamps_plugin_api = $this->makeEmpty(
			Stamps_Plugin_API::class,
			array(
				'get_rates' => $rates_response,
			)
		);

		$sut = new Stamps_Label( $settings, $logger, $stamps_plugin_api );

		$order = new WC_Order();

		$order->set_shipping_first_name( 'Brian' );
		$order->set_shipping_last_name( 'Henry' );
		$order->set_shipping_address_1( '815 E ST APT 16' );
		$order->set_shipping_city( 'SACRAMENTO' );
		$order->set_shipping_state( 'CA' );
		$order->set_shipping_postcode( '95814-1341' );
		$order->set_shipping_country( 'US' );

		$valid_response = array(
			'matched'      => true,
			'matched_zip'  => true,
			'hash'         => 'uZTitYTYmobDYDOhMphEkdbfuRBkZWFkYmVlZg==20220413C033',
			'overide_hash' => 'GcNlL9EkigdlP4X5WS60sYP3YftkZWFkYmVlZg==20220413C033',
			'address'      =>
				array(
					'full_name' => 'BRIAN HENRY',
					'company'   => '',
					'address_1' => '815 E ST APT 16',
					'address_2' => '',
					'city'      => 'SACRAMENTO',
					'state'     => 'CA',
					'postcode'  => '95814-1341',
					'country'   => '',
				),
		);

		$order->add_meta_data( '_stamps_response', $valid_response, true );
		$order->add_meta_data( '_stamps_hash', 'uZTitYTYmobDYDOhMphEkdbfuRBkZWFkYmVlZg==20220413C033', true );
		$order->add_meta_data( '_stamps_override_hash', 'GcNlL9EkigdlP4X5WS60sYP3YftkZWFkYmVlZg==20220413C033', true );

		$order->set_total( '99.99' );

		$product = new WC_Product();
		$product->set_name( 'Dummy' );
		$product->set_price( '9.99' );
		$product->set_weight( '50' ); // TODO What are the units?!

		// @see WC_Stamps_API::get_rates()
		// 'WeightLb'      => floor( $args['weight'] ),
		// 'WeightOz'      => number_format( ( $args['weight'] - floor( $args['weight'] ) ) * 16, 2 ),

		$product->set_height( '10' );
		$product->set_width( '10' );
		$product->set_length( '10' );
		$product_id = $product->save();

		$order->add_product( $product, 2 );

		$order->save();

		$result = $sut->fetch_rates( $order );
	}


	/**
	 * @covers ::get_order_dimensions
	 */
	public function test_get_order_dimensions() {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Settings_Interface::class );

		$sut = new Stamps_Label( $settings, $logger );

		$order = new WC_Order();

		$product = new WC_Product();
		$product->set_length( 5 );
		$product->set_height( 5 );
		$product->set_width( 5 );
		$product->set_weight( 5 );
		$product->save();

		$order->add_product( $product );
		$order->save();

		// Dimension 5*5*5 = volume 125.
		$result        = $sut->get_order_dimensions( $order );
		$result_volume = $result['length'] * $result['width'] * $result['height'];
		$this->assertEquals( 125.0, $result_volume );
	}
}
