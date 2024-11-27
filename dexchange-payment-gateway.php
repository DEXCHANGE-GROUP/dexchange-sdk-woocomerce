<?php
/**
 * Plugin Name: DEXCHANGE Payment Gateway
 * Plugin URI: https://api.dexchange.sn
 * Description: Acceptez les paiements via la passerelle de paiement DEXCHANGE
 * Version: 1.0.0
 * Author: DEXCHANGE
 * Author URI: https://api.dexchange.sn
 * Text Domain: dexchange-payment-gateway
 *
 * @package DexchangePaymentGateway
 */

if (!defined('ABSPATH')) {
    exit;
}

// Make sure WooCommerce is active
if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
    return;
}

// Include main plugin classes
require_once plugin_dir_path(__FILE__) . 'includes/class-dexchange-api.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-dexchange-order-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-dexchange-webhook-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-dexchange-gateway.php';

/**
 * Add the gateway to WooCommerce
 */
function add_dexchange_gateway($methods) {
    $methods[] = 'Dexchange_Gateway';
    return $methods;
}
add_filter('woocommerce_payment_gateways', 'add_dexchange_gateway');

/**
 * Initialize the gateway and load assets
 */
function init_dexchange_gateway() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    // Enqueue styles
    wp_enqueue_style('dexchange-style', plugins_url('assets/css/dexchange-style.css', __FILE__));
}
add_action('plugins_loaded', 'init_dexchange_gateway');

/**
 * Create necessary directories on plugin activation
 */
function dexchange_plugin_activation() {
    $upload_dir = wp_upload_dir();
    $dexchange_upload_dir = $upload_dir['basedir'] . '/dexchange';
    
    if (!file_exists($dexchange_upload_dir)) {
        wp_mkdir_p($dexchange_upload_dir);
    }
}
register_activation_hook(__FILE__, 'dexchange_plugin_activation');