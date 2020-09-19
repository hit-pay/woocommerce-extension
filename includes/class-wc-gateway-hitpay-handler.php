<?php
/**
 * Check if WooCommerce is active
 */
if (in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {

    require_once dirname( __FILE__ ) . '/class-wc-gateway-hitpay-response.php';

    class WC_Gateway_HitPay_Handler extends WC_Gateway_HitPay_Response 
    {
        /**
         * Pointer to gateway making the request.
         *
         * @var WC_Gateway_HitPay
         */
        protected $gateway;

        public function __construct($gateway) 
        {
            $this->gateway = $gateway;

            add_action('woocommerce_api_wc_gateway_hitpay', array($this, 'check_response'));
            add_action('valid-hitpay-notification-request', array($this, 'valid_response'));
        }

        /**
         * Check for HitPay Notification Response.
         */
        public function check_response() 
        {
            if (!empty($_POST) && $this->validate_signature()) {
                $posted = wp_unslash($_POST);

                do_action('valid-hitpay-notification-request', $posted);
                exit;
            }

            wp_die('HitPay Notification Failed', 'HitPay Notification', array('response' => 500));
        }

        /**
         * There was a valid response.
         *
         * @param  array $posted Post data after wp_unslash.
         */
        public function valid_response($posted) 
        {
            $order = !empty($posted['x_order_id']) && !empty($posted['x_reference']) ? $this->get_hitpay_order($posted['x_order_id'], $posted['x_reference']) : false;

            if ($order) {
                // Lowercase returned variables.
                $posted['x_result'] = strtolower($posted['x_result']);

                WC_Gateway_HitPay::log('Found order #' . $order->get_id());
                WC_Gateway_HitPay::log('Payment status: ' . $posted['x_result']);

                if (method_exists($this, 'payment_status_' . $posted['x_result'])) {
                    call_user_func(array($this, 'payment_status_' . $posted['x_result']), $order, $posted);
                }
            }
        }

        public function validate_signature() 
        {
            WC_Gateway_HitPay::log('Checking Signature response is valid');

            $posted     = wp_unslash($_POST);
            $signature  = $posted['x_signature'];
            $api_key    = $this->gateway->testmode? $this->gateway->get_option('sandbox_client_secret'): $this->gateway->get_option('client_secret');
            $params     = array(
                'x_amount'              => $posted['x_amount'],
                'x_reference'           => $posted['x_reference'], //order_key
                'x_account_id'          => $posted['x_account_id'],
                'x_gateway_reference'   => $posted['x_gateway_reference'],
                'x_currency'            => $posted['x_currency'],
                'x_result'              => $posted['x_result'],
                'x_test'                => $posted['x_test'],
                'x_timestamp'           => $posted['x_timestamp'],
            );

            $calculatedSignature = WC_Gateway_HitPay::generate_signature($params, $api_key);

            if (!hash_equals($signature, $calculatedSignature)) {
                WC_Gateway_HitPay::log('Response Signature is invalid.');
                
                return false;
            }    

            return true;
        }

        /**
         * Handle a completed payment.
         *
         * @param WC_Order $order  Order object.
         * @param array    $posted Posted data.
         */
        protected function payment_status_completed($order, $posted) 
        {
            if ($order->has_status(wc_get_is_paid_statuses())) {
                WC_Gateway_HitPay::log('Aborting, Order #' . $order->get_id() . ' is already complete.');
                exit;
            }

            $this->save_hitpay_meta_data( $order, $posted );

            if ('completed' === $posted['x_result']) {
                $this->payment_complete($order, wc_clean($posted['x_gateway_reference']), __('Payment completed', 'woocommerce'));
            }
        }

        /**
         * Handle a completed payment.
         *
         * @param WC_Order $order  Order object.
         * @param array    $posted Posted data.
         */
        protected function payment_status_pending($order, $posted) 
        {
            if ('pending' === $posted['x_result']) {
                $this->payment_on_hold($order, sprintf(__('Payment pending (%s).', 'woocommerce'), $posted['x_pending_reason']));
            }
        }

        protected function save_hitpay_meta_data($order, $posted) 
        {
            if (!empty($posted['x_gateway_reference'])) {
                update_post_meta($order->get_id(), '_transaction_id', wc_clean( $posted['x_gateway_reference']));
            }
            if (!empty($posted['x_result'])) {
                update_post_meta($order->get_id(), '_hitpay_status', wc_clean( $posted['x_result']));
            }
        }
    } 
}