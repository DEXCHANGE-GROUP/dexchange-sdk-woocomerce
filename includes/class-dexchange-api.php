<?php
/**
 * DEXCHANGE API Handler
 *
 * @package DexchangePaymentGateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class Dexchange_API {
    private $api_url = 'https://api-m.dexchange.sn/api/v1';
    private $api_key;

    public function __construct($api_key) {
        $this->api_key = $api_key;
    }

    /**
     * Generate payment URL
     */
    public function get_payment_link($external_transaction_id, $item_name, $item_price, $custom_data, $callback_url, $success_url, $failure_url) {
        $endpoint = '/transaction/merchant/get-link';
        
        $body = array(
            'externalTransactionId' => $external_transaction_id,
            'ItemName' => $item_name,
            'ItemPrice' => $item_price,
            'customData' => $custom_data,
            'callBackURL' => $callback_url,
            'successUrl' => $success_url,
            'failureUrl' => $failure_url
        );

        return $this->make_request('POST', $endpoint, $body);
    }

    /**
     * Make HTTP request to DEXCHANGE API
     */
    private function make_request($method, $endpoint, $body = null) {
        $args = array(
            'method' => $method,
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->api_key,
                'Content-Type' => 'application/json'
            ),
            'timeout' => 30
        );

        if ($body) {
            $args['body'] = json_encode($body);
        }

        $response = wp_remote_request($this->api_url . $endpoint, $args);

        if (is_wp_error($response)) {
            return array(
                'success' => false,
                'error' => $response->get_error_message()
            );
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $status_code = wp_remote_retrieve_response_code($response);

        return array(
            'success' => $status_code >= 200 && $status_code < 300,
            'status_code' => $status_code,
            'data' => $body
        );
    }
}