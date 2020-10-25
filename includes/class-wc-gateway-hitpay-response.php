<?php
/**
 * Check if WooCommerce is active
 */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if (is_plugin_active( 'woocommerce/woocommerce.php')) {

    abstract class WC_Gateway_HitPay_Response 
    {
        /**
         * Get the order from the HitPay variable.
         *
         * @param  string $order_id JSON Data passed back by HitPay.
         * @param  string $order_key JSON Data passed back by HitPay.
         * @return bool|WC_Order object
         */
        protected function get_hitpay_order($order_id, $order_key) 
        {
            if (empty($order_id) || empty($order_key)) {
                return false;
            }
    
            $order = wc_get_order($order_id);
    
            if (!$order) {
                $order_id = wc_get_order_id_by_order_key( $order_key );
                $order    = wc_get_order($order_id);
            }
    
            if (!$order || !hash_equals( $order->get_order_key(), $order_key)) {
                return false;
            }
    
            return $order;
        }
    
        /**
         * Complete order, add reference ID and note.
         *
         * @param  WC_Order $order Order object.
         * @param  string   $reference Reference ID.
         * @param  string   $note Payment note.
         */
        protected function payment_complete($order, $reference = '', $note = '') 
        {
            if (!$order->has_status(array('processing', 'completed'))) {
                $order->add_order_note($note);
                /*$order->update_status( 'completed' );*/
                $order->payment_complete($reference);

                WC()->cart->empty_cart();
            }
        }
    
        /**
         * Hold order and add note.
         *
         * @param  WC_Order $order Order object.
         * @param  string   $reason Reason why the payment is on hold.
         */
        protected function payment_on_hold($order, $reason = '') 
        {
            $order->update_status('on-hold', $reason);
            WC()->cart->empty_cart();
        }
    }        
}