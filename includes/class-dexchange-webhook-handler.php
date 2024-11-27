<?php
/**
 * DEXCHANGE Webhook Handler
 *
 * @package DexchangePaymentGateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class Dexchange_Webhook_Handler {
    /**
     * Handle incoming webhook
     */
    public function handle_webhook() {
        $payload = file_get_contents('php://input');
        $data = json_decode($payload, true);

        if (!$this->validate_webhook($data)) {
            status_header(400);
            exit('Invalid webhook data');
        }

        $webhook_data = $this->format_webhook_data($data);
        
        if (Dexchange_Order_Handler::update_order_status($webhook_data)) {
            status_header(200);
            exit('Webhook processed successfully');
        }

        status_header(400);
        exit('Order processing failed');
    }

    /**
     * Validate webhook data
     */
    private function validate_webhook($data) {
        return isset($data['id']) && 
               isset($data['externalTransactionId']) && 
               isset($data['STATUS']);
    }

    /**
     * Format webhook data
     */
    private function format_webhook_data($data) {
        return array(
            'transaction_id' => $data['id'],
            'external_transaction_id' => $data['externalTransactionId'],
            'status' => $data['STATUS'],
            'amount' => $data['AMOUNT'],
            'completed_at' => $data['COMPLETED_AT']
        );
    }
}