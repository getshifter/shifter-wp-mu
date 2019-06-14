<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link    https://www.getshifter.io
 * @since   1.0.0
 * @package Shifter
 *
 * @wordpress-plugin
 * Plugin Name:       Shifter
 * Plugin URI:        https://github.com/getshifter/shifter-wp-mu
 * Description:       Helper functions for WordPress sites on Shifter.
 * Version:           1.0.8
 * Author:            DigitalCube
 * Author URI:        https://www.getshifter.io
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       shifter
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'SHIFTER_VERSION', '1.0.8' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-shifter-activator.php
 */
function activate_shifter() {
	include_once plugin_dir_path( __FILE__ ) . 'includes/class-shifter-activator.php';
	Shifter_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-shifter-deactivator.php
 */
function deactivate_shifter() {
	include_once plugin_dir_path( __FILE__ ) . 'includes/class-shifter-deactivator.php';
	Shifter_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_shifter' );
register_deactivation_hook( __FILE__, 'deactivate_shifter' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-shifter.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since 1.0.0
 */
function run_shifter() {
	$plugin = new Shifter();
	$plugin->run();

}

run_shifter();


// Temp filters before core update.

/**
 * Force remove controle characters from filename
 *
 * @link  https://www.getshifter.io
 * @since 1.0.8
 *
 * @package    Shifter
 * @subpackage Shifter/filters
 * core_ticket: https://core.trac.wordpress.org/ticket/47539
 */

add_filter(
	'sanitize_file_name',
	function( $filename, $filename_raw ) {
		return preg_replace( '/[\x00-\x1F]/', '', $filename );
	},
	10,
	2
);
