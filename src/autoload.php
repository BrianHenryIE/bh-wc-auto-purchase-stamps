<?php
/**
 * Loads all required classes
 *
 * Uses classmap, PSR4 & wp-namespace-autoloader.
 *
 * @link              https://BrianHenryIE.com
 * @since             1.0.0
 * @package           brianhenryie/wc-auto-purchase-stamps
 *
 * @see https://github.com/pablo-sg-pacheco/wp-namespace-autoloader/
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps;

use BrianHenryIE\WC_Auto_Purchase_Stamps\Pablo_Pacheco\WP_Namespace_Autoloader\WP_Namespace_Autoloader;


$class_map_file = __DIR__ . '/autoload-classmap.php';
if ( file_exists( $class_map_file ) ) {

	$class_map = include $class_map_file;

	if ( is_array( $class_map ) ) {
		spl_autoload_register(
			function ( $classname ) use ( $class_map ) {

				if ( array_key_exists( $classname, $class_map ) && file_exists( $class_map[ $classname ] ) ) {
					require_once $class_map[ $classname ];
				}
			}
		);
	}
}

unset( $class_map_files );
unset( $class_map_file );

// Load strauss classes after autoload-classmap.php so classes can be substituted.
require_once __DIR__ . '/strauss/autoload.php';


$wpcs_autoloader = new WP_Namespace_Autoloader();
$wpcs_autoloader->init();
