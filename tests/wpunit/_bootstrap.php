<?php
/**
 * PHPUnit bootstrap file for wpunit tests. Since the plugin will not be otherwise autoloaded.
 *
 * @package           BH_WC_Auto_Purchase_Stamps
 */

global $plugin_root_dir;
require_once $plugin_root_dir . '/autoload.php';

activate_plugin( 'woocommerce/woocommerce.php' );

