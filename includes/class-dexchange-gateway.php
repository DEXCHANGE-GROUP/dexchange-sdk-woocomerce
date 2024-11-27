<?php
/**
 * DEXCHANGE Payment Gateway
 *
 * @package DexchangePaymentGateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class Dexchange_Gateway extends WC_Payment_Gateway {
    private $api;

    public function __construct() {
        $this->id = 'dexchange';
        $this->icon = plugins_url('../assets/images/dexchange-logo.png', __FILE__);
        $this->has_fields = false;
        $this->method_title = 'Passerelle de Paiement DEXCHANGE';
        $this->method_description = 'Acceptez les paiements via DEXCHANGE';
        $this->supports = array('products');

        // Load the form fields
        $this->init_form_fields();
        $this->init_settings();

        // Define user set variables
        $this->title = $this->get_option('title');
        $this->description = $this->get_option('description');
        $this->enabled = $this->get_option('enabled');
        $this->api_key = $this->get_option('api_key');
        
        $this->api = new Dexchange_API($this->api_key);

        // Hooks
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_dexchange_gateway', array($this, 'handle_webhook'));
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action('woocommerce_review_order_before_payment', array($this, 'display_dexchange_banner'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => 'Activer/Désactiver',
                'type' => 'checkbox',
                'label' => 'Activer le paiement DEXCHANGE',
                'default' => 'yes'
            ),
            'title' => array(
                'title' => 'Titre',
                'type' => 'text',
                'description' => 'Le titre que les clients voient lors du paiement',
                'default' => 'Paiement DEXCHANGE',
                'desc_tip' => true,
            ),
            'description' => array(
                'title' => 'Description',
                'type' => 'textarea',
                'description' => 'La description que les clients voient lors du paiement',
                'default' => 'Payez en toute sécurité via DEXCHANGE',
            ),
            'api_key' => array(
                'title' => 'Clé API',
                'type' => 'password',
                'description' => 'Entrez votre clé API DEXCHANGE',
                'required' => true
            )
        );
    }

    public function display_dexchange_banner() {
        if ($this->is_available()) {
            echo '<div class="dexchange-banner">
                    <img src="' . esc_url(plugins_url('../assets/images/dexchange-banner.png', __FILE__)) . '" 
                         alt="DEXCHANGE Payment" />
                  </div>';
        }
    }

    public function receipt_page($order_id) {
        echo '<div class="dexchange-banner">
                <img src="' . esc_url(plugins_url('../assets/images/dexchange-banner.png', __FILE__)) . '" 
                     alt="DEXCHANGE Payment" />
              </div>';
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        
        try {
            $response = $this->api->get_payment_link(
                $order_id,
                'Commande #' . $order_id,
                $order->get_total(),
                '',
                home_url('/wc-api/dexchange_gateway'),
                $this->get_return_url($order),
                $order->get_cancel_order_url()
            );

            if (!$response['success']) {
                throw new Exception('Échec de l\'initialisation du paiement');
            }

            $payment_url = $response['data']['transaction']['PaymentUrl'];
            
            $order->update_meta_data('_dexchange_transaction_id', $response['data']['transaction']['transactionId']);
            $order->update_status('pending', 'En attente du paiement DEXCHANGE');
            $order->save();

            return array(
                'result' => 'success',
                'redirect' => $payment_url
            );

        } catch (Exception $e) {
            wc_add_notice('Erreur de paiement: ' . $e->getMessage(), 'error');
            return array(
                'result' => 'failure',
                'messages' => $e->getMessage()
            );
        }
    }

    public function handle_webhook() {
        $webhook_handler = new Dexchange_Webhook_Handler();
        $webhook_handler->handle_webhook();
    }

    public function is_available() {
        return parent::is_available() && !empty($this->api_key);
    }
}