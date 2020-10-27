<?php
/**
 * Check if WooCommerce is active
 */
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if (is_plugin_active( 'woocommerce/woocommerce.php')) {

    class WC_Gateway_HitPay_Form_Request
    {
        /**
         * Pointer to gateway making the request.
         *
         * @var WC_Gateway_HitPay
         */
        protected $gateway;

        /**
         * Endpoint for requests from HitPay
         *
         * @var string
         */
        protected $notify_url;

        /**
         * Endpoint for requests to HitPay.
         *
         * @var string
         */
        protected $endpoint;

        public function __construct($gateway) 
        {
            $this->gateway      = $gateway;
            $this->notify_url   = WC()->api_request_url('wc_gateway_hitpay');
        }


        /**
         * Get the Hitpay request URL for an order.
         *
         * @param  WC_Order $order Order object.
         * @return string
         */
        public function display_form_request($order)
        {
            $endpoint = $this->gateway->testmode
                ? 'https://securecheckout.staging.hit-pay.loc/payment-gateway/woocommerce/checkout'
                : 'https://securecheckout.hit-pay.com/payment-gateway/woocommerce/checkout';

            $params                 = $this->get_transaction_args($order);
            $api_key                = $this->gateway->testmode
                ? $this->gateway->get_option('sandbox_client_secret')
                : $this->gateway->get_option('client_secret');
            $params['x_signature']  = WC_Gateway_HitPay::generate_signature($params, $api_key);

            include_once dirname(__FILE__) . '/../partials/form.php';
        }

        protected function get_transaction_args($order) 
        {
            return array_merge(
                array(
                    'x_account_id'                  => $this->gateway->testmode
                        ? $this->gateway->get_option('sandbox_client_id')
                        : $this->gateway->get_option('client_id'),
                    'x_currency'                    => get_woocommerce_currency(),
                    'x_url_complete'                => esc_url_raw($this->gateway->get_return_url($order)),
                    'x_url_cancel'                  => esc_url_raw($order->get_cancel_order_url_raw()),
                    'x_amount'                      => $order->get_total(),
                    'x_invoice'                     => $order->get_order_number(),
                    'x_order_id'                    => $order->get_id(),
                    'x_reference'                   => $order->get_order_key(),
                    'x_url_callback'                => $this->notify_url,
                    'x_customer_first_name'         => $order->get_billing_first_name(),
                    'x_customer_last_name'          => $order->get_billing_last_name(),
                    /*'x_customer_billing_address1'   => $order->get_billing_address_1(),
                    'x_customer_billing_address2'   => $order->get_billing_address_2(),
                    'x_customer_billing_city'       => $order->get_billing_city(),
                    'x_customer_billing_state'      => $this->get_hitpay_state($order->get_billing_country(), $order->get_billing_state()),
                    'x_customer_billing_zip'        => wc_format_postcode( $order->get_billing_postcode(), $order->get_billing_country()),
                    'x_customer_billing_country'    => $order->get_billing_country(),*/
                    'x_customer_email'              => $order->get_billing_email(),
                    'x_test'                        => $this->gateway->testmode? "true": "false",
                    'x_shop_name'                   => get_home_url(),
                    'x_checkout_url'                => esc_url( wc_get_checkout_url() ),
                ),
                $this->get_shipping_args($order)
            );
        }

        /**
         * Get shipping args for hitpay request.
         *
         * @param  WC_Order $order Order object.
         * @return array
         */
        protected function get_shipping_args($order) 
        {
            $shipping_args = array();

            if ($order->needs_shipping_address()) {
                $shipping_args['x_customer_shipping_first_name']    = $order->get_shipping_first_name();
                $shipping_args['x_customer_shipping_last_name']     = $order->get_shipping_last_name();
                $shipping_args['x_customer_shipping_address1']      = $order->get_shipping_address_1();
                $shipping_args['x_customer_shipping_address2']      = $order->get_shipping_address_2();
                $shipping_args['x_customer_shipping_city']          = $order->get_shipping_city();
                $shipping_args['x_customer_shipping_state']         = $this->get_hitpay_state($order->get_shipping_country(), $order->get_shipping_state());
                $shipping_args['x_customer_shipping_country']       = $order->get_shipping_country();
                $shipping_args['x_customer_shipping_zip']           = wc_format_postcode($order->get_shipping_postcode(), $order->get_shipping_country());
            }
            
            return $shipping_args;
        }

        /**
         * @param  string $cc Country two letter code.
         * @param  string $state State code.
         * @return string
         */
        protected function get_hitpay_state($cc, $state) 
        {
            if ('US' === $cc) {
                return $state;
            }

            $states = WC()->countries->get_states( $cc );

            if (isset($states[ $state ])) {
                return $states[$state];
            }

            return $state;
        }
    }            
}
