<?php

namespace BrianHenryIE\WC_Auto_Purchase_Stamps\Admin;

/**
 * @coversDefaultClass \BrianHenryIE\WC_Auto_Purchase_Stamps\Admin\Plugins_Page
 */
class Plugins_Page_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}


	/**
	 * @covers ::action_links
	 */
	public function test_settings_action_link_added() {

		\WP_Mock::userFunction(
			'is_plugin_active',
			array(
				'return' => true,
				'times'  => 2,
			)
		);

		\WP_Mock::userFunction(
			'admin_url',
			array(
				'args'       => array( 'admin.php?page=wc-settings&tab=stamps' ),
				'return_arg' => 0,
			)
		);

		$sut = new Plugins_Page();

		$links_array = array();
		$plugin_file = 'bh-wc-auto-purchase-stamps/bh-wc-auto-purchase-stamps.php';
		$plugin_data = array();
		$context     = '';

		$result = $sut->action_links( $links_array, $plugin_file, $plugin_data, $context );

		$this->assertIsArray( $result );

		$link_html = $result[0];

		$this->assertStringContainsString( 'Settings', $link_html );

		$this->assertStringContainsString( 'admin.php?page=wc-settings&tab=stamps', $link_html );
	}

	/**
	 * @covers ::row_meta
	 */
	public function test_stamps_row_meta_link_added_stamps_plugin_active() {

		\WP_Mock::userFunction(
			'is_plugin_active',
			array(
				'args'   => array( 'woocommerce-shipping-stamps/woocommmerce-shipping-stamps.php' ),
				'return' => true,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'admin_url',
			array(
				'args'       => array( '?stamps_redirect=WebClientHome' ),
				'return_arg' => 0,
				'times'      => 1,
			)
		);

		$sut = new Plugins_Page();

		$links_array = array();
		$plugin_file = 'bh-wc-auto-purchase-stamps/bh-wc-auto-purchase-stamps.php';
		$plugin_data = array();
		$context     = '';

		$result = $sut->row_meta( $links_array, $plugin_file, $plugin_data, $context );

		$this->assertIsArray( $result );

		$link_html = $result[0];

		$this->assertStringContainsString( 'Stamps.com', $link_html );

		$this->assertStringContainsString( 'stamps_redirect=WebClientHome', $link_html );
	}


	/**
	 * @covers ::row_meta
	 */
	public function test_stamps_row_meta_link_added_stamps_plugin_not_active() {

		\WP_Mock::userFunction(
			'is_plugin_active',
			array(
				'args'   => array( 'woocommerce-shipping-stamps/woocommmerce-shipping-stamps.php' ),
				'return' => false,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'admin_url',
			array(
				'args'       => array( '?stamps_redirect=WebClientHome' ),
				'return_arg' => 0,
				'times'      => 0,
			)
		);

		$sut = new Plugins_Page();

		$links_array = array();
		$plugin_file = 'bh-wc-auto-purchase-stamps/bh-wc-auto-purchase-stamps.php';
		$plugin_data = array();
		$context     = '';

		$result = $sut->row_meta( $links_array, $plugin_file, $plugin_data, $context );

		$this->assertIsArray( $result );

		$link_html = $result[0];

		$this->assertStringContainsString( 'Stamps.com', $link_html );

		$this->assertStringContainsString( 'print.stamps.com', $link_html );
	}
}
