<?php
/**
 * PHPUnit bootstrap file for wpunit tests. Since the plugin will not be otherwise autoloaded.
 *
 * @package           BH_WC_Auto_Purchase_Stamps
 */

global $plugin_root_dir;
require_once $plugin_root_dir . '/autoload.php';

activate_plugin( 'woocommerce/woocommerce.php' );

$settings = array(
	'wc_settings_stamps_username'    => $_ENV['STAMPS_USERNAME'],
	'wc_settings_stamps_password'    => $_ENV['STAMPS_PASSWORD'],
	'wc_settings_stamps_sample_only' => 'yes',
	'wc_settings_stamps_zip'         => '95819',
	'woocommerce_weight_unit'        => 'oz',
	'woocommerce_dimension_unit'     => 'in',


);

foreach ( $settings as $option_name => $value ) {
	add_filter(
		"pre_option_{$option_name}",
		function( $retval, $option, $default ) use ( $value ) {
			return $value;
		},
		10,
		3
	);
}
