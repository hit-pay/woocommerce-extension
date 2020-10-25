<?php
/**
 * Check if WooCommerce is active
 */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if (is_plugin_active( 'woocommerce/woocommerce.php')) {
	
    class WC_Gateway_HitPay_API_Handler
    {
        /**
         * Client ID
         *
         * @var string
         */
        public static $client_id;

        /**
         * Client Secret
         *
         * @var string
         */
        public static $client_secret;

        /**
         * Sandbox
         *
         * @var bool
         */
        public static $sandbox = false;

        public static function refund_transaction($order, $amount = null, $reason = '') 
        {
            $raw_response = wp_safe_remote_post(
                self::$sandbox ? 'https://api.staging.hit-pay.com/charge/refund' : 'https://api.hit-pay.com/charge/refund',
                array(
                    'method'      => 'POST',
                    //'body'        => self::get_refund_request($order, $amount, $reason),
                    'timeout'     => 70,
                    'user-agent'  => 'WooCommerce/' . WC()->version,
                    'httpversion' => '1.1',
                )
            );

            WC_Gateway_HitPay::log('Refund Response: ' . wc_print_r($raw_response, true));

            if (empty( $raw_response['body'])) {
                return new WP_Error('hitpay-api', 'Empty Response');
            } elseif (is_wp_error( $raw_response)) {
                return $raw_response;
            }

            parse_str( $raw_response['body'], $response );

            return (object) $response;
        }
    }        
}