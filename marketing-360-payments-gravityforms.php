<?php
/**
 * Plugin Name: Gravity Forms Marketing 360® Payments Add-On
 * Plugin URI: https://marketing360.com
 * Description: Integrates Marketing 360® Payments with Gravity Forms, enabling your customers to make safe and secure purchases through Gravity Forms. To get started: activate the plugin and connect to your Marketing 360 Payments account.
 * Version: 1.0.5
 * Author: Marketing 360®
 * Author URI: https://marketing360.com
 * License: GPL-2.0+
 * Text Domain: gravityformsm360
 */

defined( 'ABSPATH' ) || die();

//Required constants
define( 'GF_M360_VERSION', '1.0.5' );
define(	'GF_M360_URL', plugin_dir_url(__FILE__));
define(	'GF_M360_PATH', plugin_dir_path(__FILE__));

// If Gravity Forms is loaded, bootstrap the Marketing 360 Payments Add-On.
add_action( 'gform_loaded', array( 'GF_M360_Bootstrap', 'load' ), 5 );


// Handle the loading of the Marketing 360 Payments Add-On and registers with the Add-On framework
class GF_M360_Bootstrap {

    // If the Payment Add-On Framework exists, Marketing 360 Payments Add-On is loaded.
	public static function load() {

		if ( ! method_exists( 'GFForms', 'include_payment_addon_framework' ) ) {
			return;
		}

		require_once('class-gf-m360-addon.php');

		GFAddOn::register('GF_M360_AddOn');

	}

}

// Register the REST endpoint for testing the authorization credentials and returning a list of M360 Accounts.
add_action('rest_api_init', function() {

	require_once('classes/class-gf-marketing-360-payments.php');
	
	register_rest_route('gf_marketing_360_payments/' . GF_Marketing_360_Payments::VER, '/sign_in', array(
		'methods' => 'POST',
		'callback' => 'GF_Marketing_360_Payments::rest_list_m360_accounts',
	));
}, 10);

// Get an instance of the GF_M360_AddOn class.
function gf_m360() {
	return GF_M360_AddOn::get_instance();
}

// When the plugin is active, remove the "activate the plugin" step from the plugin description.
function gf_m360_modify_plugin_description($all_plugins) {
	$description = "";

	if (!isset($all_plugins['gravityforms/gravityforms.php'])) {
		$description = __('Integrates Marketing 360® Payments with Gravity Forms, enabling your customers to make safe and secure purchases through Gravity Forms. To get started: activate Gravity Forms, then connect to your Marketing 360 Payments account.', 'gravityformsm360');
	} else {
		$description = __( 'Integrates Marketing 360® Payments with Gravity Forms, enabling your customers to make safe and secure purchases through Gravity Forms.', 'gravityformsm360');
	}

	$all_plugins[plugin_basename(__FILE__)]['Description'] = $description;
	
	return $all_plugins;
}
add_filter('all_plugins', 'gf_m360_modify_plugin_description', 0, 1);


// Add the settings tab to the list of plugin options.
$plugin = plugin_basename(__FILE__);
function gf_m360_plugin_options($links) {
	if (class_exists('GFForms')) {
		array_unshift($links, '<a href="' . get_admin_url(null, 'admin.php?page=gf_settings&subview=gravityforms-marketing-360-payments') . '">Settings</a>'); 
	}
	return $links;
}
add_filter("plugin_action_links_$plugin", "gf_m360_plugin_options", 10, 4);

// Render the signin popup in the admin footer, only when on the current subview of the Gravity Forms settings page.
add_action('admin_footer', function() {
	if (!function_exists('get_current_screen')) {
		return;
	}
	if (get_current_screen()->base == "forms_page_gf_settings" && isset($_GET['subview']) && $_GET['subview'] == 'gravityforms-marketing-360-payments') {
        require_once('marketing360-login-page.php');
	}
});