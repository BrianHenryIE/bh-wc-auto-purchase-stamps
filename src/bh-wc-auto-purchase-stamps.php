<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           BH_WC_Auto_Purchase_Stamps
 *
 * @wordpress-plugin
 * Plugin Name:       Auto Purchase Stamps
 * Plugin URI:        http://github.com/BrianHenryIE/bh-wc-auto-purchase-stamps/
 * Description:       Automatically purchases shipping labels when orders are paid using the WooCommerce Stamps.com API integration.
 * Version:           1.1.0
 * Author:            Brian Henry
 * Author URI:        http://BrianHenryIE.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bh-wc-auto-purchase-stamps
 * Domain Path:       /languages
 */

namespace BrianHenryIE\WC_Auto_Purchase_Stamps;

use BrianHenryIE\WC_Auto_Purchase_Stamps\api\API;
use BrianHenryIE\WC_Auto_Purchase_Stamps\api\Settings;
use BrianHenryIE\WC_Auto_Purchase_Stamps\WP_Logger\Logger;
use BrianHenryIE\WC_Auto_Purchase_Stamps\includes\Activator;
use BrianHenryIE\WC_Auto_Purchase_Stamps\includes\Deactivator;
use BrianHenryIE\WC_Auto_Purchase_Stamps\includes\BH_WC_Auto_Purchase_Stamps;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once plugin_dir_path( __FILE__ ) . 'autoload.php';

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'BH_WC_AUTO_PURCHASE_STAMPS_VERSION', '1.1.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-activator.php
 */
function activate_bh_wc_auto_purchase_stamps() {

	Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-deactivator.php
 */
function deactivate_bh_wc_auto_purchase_stamps() {

	Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'BH_WC_Auto_Purchase_Stamps\activate_bh_wc_auto_purchase_stamps' );
register_deactivation_hook( __FILE__, 'BH_WC_Auto_Purchase_Stamps\deactivate_bh_wc_auto_purchase_stamps' );


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function instantiate_bh_wc_auto_purchase_stamps() {

	$settings = new Settings();
	$logger   = Logger::instance( $settings );
	$api      = new API( $settings, $logger );

	/**
	 * The core plugin class that is used to define internationalization,
	 * admin-specific hooks, and frontend-facing site hooks.
	 */
	new BH_WC_Auto_Purchase_Stamps( $api, $settings, $logger );

	return $api;
}


$GLOBALS['bh_wc_auto_purchase_stamps'] = $bh_wc_auto_purchase_stamps = instantiate_bh_wc_auto_purchase_stamps();

