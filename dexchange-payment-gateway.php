<?php
/**
 * Plugin Name: DEXCHANGE Payment Gateway
 * Plugin URI: https://api.dexchange.sn
 * Description: Acceptez les paiements via la passerelle de paiement DEXCHANGE
 * Version: 1.0.0
 * Author: DEXCHANGE
 * Author URI: https://api.dexchange.sn
 * Text Domain: dexchange-payment-gateway
 * WC requires at least: 3.0.0
 * WC tested up to: 8.0.0
 * Requires PHP: 7.2
 *
 * @package DexchangePaymentGateway
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Check if WooCommerce is active
 */
function dexchange_check_woocommerce() {
    if (!in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        add_action('admin_notices', 'dexchange_woocommerce_missing_notice');
        return false;
    }
    return true;
}

/**
 * Display WooCommerce missing notice
 */
function dexchange_woocommerce_missing_notice() {
    ?>
<div class="error">
    <p><?php _e('DEXCHANGE Payment Gateway nécessite que WooCommerce soit installé et activé.', 'dexchange-payment-gateway'); ?>
    </p>
</div>
<?php
}

/**
 * Declare HPOS compatibility
 */
function dexchange_declare_hpos_compatibility() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('remote_logging', __FILE__, true);
    }
}
add_action('before_woocommerce_init', 'dexchange_declare_hpos_compatibility');

/**
 * Initialize the gateway
 */
function init_dexchange_gateway() {
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    // Include main plugin classes
    require_once plugin_dir_path(__FILE__) . 'includes/class-dexchange-api.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-dexchange-order-handler.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-dexchange-webhook-handler.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-dexchange-gateway.php';

    // Add the gateway to WooCommerce
    function add_dexchange_gateway($methods) {
        $methods[] = 'Dexchange_Gateway';
        return $methods;
    }
    add_filter('woocommerce_payment_gateways', 'add_dexchange_gateway');

    // Enqueue styles
    function dexchange_enqueue_styles() {
        if (is_checkout()) {
            wp_enqueue_style('dexchange-style', plugins_url('assets/css/dexchange-style.css', __FILE__));
        }
    }
    add_action('wp_enqueue_scripts', 'dexchange_enqueue_styles');
}
add_action('plugins_loaded', 'init_dexchange_gateway', 0);

/**
 * Create necessary directories on plugin activation
 */
function dexchange_plugin_activation() {
    if (!dexchange_check_woocommerce()) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die(__('Veuillez installer et activer WooCommerce avant d\'activer DEXCHANGE Payment Gateway.', 'dexchange-payment-gateway'));
    }

    $upload_dir = wp_upload_dir();
    $dexchange_upload_dir = $upload_dir['basedir'] . '/dexchange';
    
    if (!file_exists($dexchange_upload_dir)) {
        wp_mkdir_p($dexchange_upload_dir);
    }
}
register_activation_hook(__FILE__, 'dexchange_plugin_activation');