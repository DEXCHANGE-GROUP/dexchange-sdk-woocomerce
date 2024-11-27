<?php
/**
 * DEXCHANGE Order Handler
 *
 * @package DexchangePaymentGateway
 */

if (!defined('ABSPATH')) {
    exit;
}

class Dexchange_Order_Handler {
    /**
     * Update order status based on webhook data
     */
    public static function update_order_status($webhook_data) {
        $order_id = $webhook_data['external_transaction_id'];
        $order = wc_get_order($order_id);

        if (!$order) {
            return false;
        }

        // Use HPOS compatible methods
        $order->update_meta_data('_dexchange_transaction_id', $webhook_data['transaction_id']);
        $order->update_meta_data('_dexchange_payment_status', $webhook_data['status']);

        switch ($webhook_data['status']) {
            case 'COMPLETED':
                $order->payment_complete($webhook_data['transaction_id']);
                $order->add_order_note('Paiement DEXCHANGE complété. ID de transaction: ' . $webhook_data['transaction_id']);
                break;
            case 'FAILED':
                $order->update_status('failed', 'Paiement DEXCHANGE échoué. ID de transaction: ' . $webhook_data['transaction_id']);
                break;
            case 'PENDING':
                $order->update_status('on-hold', 'Paiement DEXCHANGE en attente. ID de transaction: ' . $webhook_data['transaction_id']);
                break;
        }

        $order->save();
        return true;
    }
}