<?php 
/**
 * Plugin Name:       Artemis Payment Gateway
 * Plugin URI:        https://artemis-gateway.com/
 * Description:       Accept payment for WooCommerce orders via Stellar (both XLM and other tokens built on the Stellar Platform). No registration and No Fees. 
 * Text Domain: 	  artemis-payment-gateway 	  
 * Version:           1.2.2
 * Requires at least: 5.8
 * Requires PHP:      8.0
 * Author:            Artemis Gateway
 * Author URI: https://artemis-gateway.com
 */


if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

define('AGP_DOMAIN', 'Artemis Gateway Payment');
if ( ! defined( 'AGP_PATH' ) ) {
	define( 'AGP_PATH', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'AGP_DIR' ) ) {
	define( 'AGP_DIR', plugin_dir_url( __FILE__ ) );
}

if ( ! function_exists( 'is_plugin_active' ) ) {
    require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
}

if ( !is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
    function require_woocommerce_php_version_plugin() { 
		if (phpversion() < '8.0') {
			$AGP_error = 'The plugin require min PHP 8.0';
		}
		else {
			$AGP_error = 'Please install WooCommerce before activating Artemis Payment Gateway';
		}
		
		if (!empty($AGP_error)) {
			?>
			<div class="notice notice-error" >
				<p><?php echo $AGP_error; ?></p>
			</div><?php
			@trigger_error(__('Please Enable ACF Network Options Plugin before using CLN Admin Dashboard.', 'cln'), E_USER_ERROR);
		}
    }
	
    add_action('network_admin_notices','require_woocommerce_php_version_plugin');
    register_activation_hook(__FILE__, 'require_woocommerce_php_version_plugin');
}

function void_activation_time(){
	$get_activation_time = strtotime("now");
	add_option('artemis_payment_gateway_plugin_activation_time', $get_activation_time );
}

function void_check_installation_time() {   
	$install_date = get_option( 'artemis_payment_gateway_plugin_activation_time' );
	$past_date = strtotime( '-1 minutes' );
	if (get_option('void_spare_me') != 1) {
		if ( $past_date >= $install_date ) {
			add_action( 'admin_notices', 'void_display_admin_notice' );
		}
	}
}

/**
* Display Admin Notice, asking for a review
**/
function void_display_admin_notice() {
	global $pagenow;
	if( $pagenow == 'index.php' ){	 
		$dont_disturb = esc_url( get_admin_url() . '?spare_me=1' );
		$plugin_info = get_plugin_data( __FILE__ , true, true );       
		$reviewurl = esc_url( 'https://wordpress.org/plugins/'. sanitize_title( $plugin_info['Name'] ) . '/#reviews' );
	 
		printf(__('<div class="notice notice-info"><div class="artg-review-wrap">You have been using <b> %s </b> for a while. We hope you liked it ! Please give us a quick rating, it works as a boost for us to keep working on the plugin !<div class="artg-review-option"><div class="void-review-btn"><a href="%s" class="button button-primary" target=
			"_blank">Rate Now!</a><a href="%s" class="void-grid-review-done button button-default"> Already Done !</a></div></div></div></div>', $plugin_info['TextDomain']), $plugin_info['Name'], $reviewurl, $dont_disturb );
	}
}

// remove the notice for the user if review already done or if the user does not want to
function void_spare_me(){    
	if( isset( $_GET['spare_me'] ) && !empty( $_GET['spare_me'] ) ){
		$spare_me = $_GET['spare_me'];
		if( $spare_me == 1 ){
			if (get_option('void_spare_me') == '') 
				add_option( 'void_spare_me' , TRUE );
			else
				update_option( 'void_spare_me' , TRUE );
		}
	}
}

//add admin css
function void_admin_css(){
	 global $pagenow;
	if( $pagenow == 'index.php' ){
		wp_enqueue_style( 'void-admin', plugins_url( 'assets/css/void-admin.css', __FILE__ ) );
	}
}

add_action( 'admin_enqueue_scripts', 'void_admin_css' );

add_action( 'admin_init', 'void_spare_me', 5 );
add_action( 'admin_init', 'void_check_installation_time' );
register_activation_hook( __FILE__, 'void_activation_time' );

add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), 'add_action_links' );

function add_action_links ( $links ) {
	if ( current_user_can( 'manage_woocommerce' ) ) {

	   $plugins_links = array(
		  '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=artemis_gateway_payment' ) . '">Payment Settings</a>',
	   );
	   $links = array_merge( $plugins_links, $links );
	}
   
   return $links;
}

register_deactivation_hook( __FILE__, 'update_woocommerce_currency' );

function update_woocommerce_currency() {
	update_option('woocommerce_currency', 'USD');
}

require_once dirname(__FILE__) . '/src/agp_main.php';