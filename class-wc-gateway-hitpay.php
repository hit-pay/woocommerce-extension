<?php
/*
Plugin Name: HitPay
Description: HitPay Payment Gateway
Author: HitPay Payment Solutions Pte Ltd
Author URI: https://www.hitpayapp.com
Version: 1.3.3
Copyright: © 2020 HitPay
*/

/**
 * Check if WooCommerce is active
 */

include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

if (is_plugin_active( 'woocommerce/woocommerce.php')) {

    function init_hitpay_gateway_class()
    {
        class WC_Gateway_HitPay extends WC_Payment_Gateway
        {
            /**
             * Whether or not logging is enabled
             *
             * @var bool
             */
            public static $log_enabled = false;

            /**
             * Logger instance
             *
             * @var WC_Logger
             */
            public static $log = null;

            public function __construct()
            {
                global $woocommerce;

                $this->id = 'hitpay';
                $this->method_title = __('HitPay', 'woocommerce');
                $this->method_description = __('HitPay checkout payment gateway.', 'woocommerce');

                $this->init_form_fields();
                $this->init_settings();

                $this->title = $this->get_option('title');
                $this->description = $this->get_option('description');
                $this->client_id = $this->get_option('client_id');
                $this->client_secret = $this->get_option('client_secret');
                $this->sandbox_client_id = $this->get_option('sandbox_client_id');
                $this->sandbox_client_secret = $this->get_option('sandbox_client_secret');
                $this->paynow_only = 'yes' === $this->get_option('paynow_only', 'no');
                $this->paynow_qr = 'yes' === $this->get_option('paynow_qr', 'no');
                $this->credit_card = 'yes' === $this->get_option('credit_card', 'no');
                $this->wechat_alipay = 'yes' === $this->get_option('wechat_alipay', 'no');
                $this->testmode = 'yes' === $this->get_option('testmode', 'no');
                $this->debug = 'yes' === $this->get_option('debug', 'no');

                self::$log_enabled = $this->debug;

                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
                add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));

                include_once dirname(__FILE__) . '/includes/class-wc-gateway-hitpay-handler.php';

                new WC_Gateway_HitPay_Handler($this);
            }

            public function getIcons()
            {
                $icon = WC_HTTPS::force_https_url(str_replace('/woocommerce', '', WC()->plugin_url()) . '/woocommerce-hitpay/assets/images/paynow.png');
                $icon_paynow = '<img src="' . esc_attr($icon) . '" alt="' . esc_attr__('PayNow', 'woocommerce') . '" />';

                $icon = WC_HTTPS::force_https_url(str_replace('/woocommerce', '', WC()->plugin_url()) . '/woocommerce-hitpay/assets/images/cards.png');
                $icon_cards = '<img src="' . esc_attr($icon) . '" alt="' . esc_attr__('Credit Cards', 'woocommerce') . '" />';

                $icon = WC_HTTPS::force_https_url(str_replace('/woocommerce', '', WC()->plugin_url()) . '/woocommerce-hitpay/assets/images/webpay.png');
                $icon_webpay = '<img src="' . esc_attr($icon) . '" alt="' . esc_attr__('WebPay', 'woocommerce') . '" />';

                return array(
                    $icon_paynow,
                    $icon_cards,
                    $icon_webpay,
                );
            }

            public function init_form_fields()
            {
                list($icon_paynow, $icon_cards, $icon_webpay) = $this->getIcons();

                $this->form_fields = array(
                    'title' => array(
                        'title' => __('Title', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('This controls the title which the user sees during checkout.', 'woocommerce'),
                        'default' => __('Hitpay', 'woocommerce'),
                        'desc_tip' => true,
                    ),
                    'description' => array(
                        'title' => __('Description', 'woocommerce'),
                        'type' => 'text',
                        'desc_tip' => true,
                        'description' => __('This controls the description which the user sees during checkout.', 'woocommerce'),
                        'default' => __("Pay via Hitpay.", 'woocommerce'),
                    ),
                    'client_id' => array(
                        'title' => __('HitPay Client ID', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('Get your Client ID from HitPay business account.', 'woocommerce'),
                        'desc_tip' => true,
                        'default' => '',
                    ),
                    'client_secret' => array(
                        'title' => __('HitPay Client Secret', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('Get your Client Secret from HitPay business account.', 'woocommerce'),
                        'desc_tip' => true,
                        'default' => '',
                    ),
                    'sandbox_client_id' => array(
                        'title' => __('HitPay Sandbox Client ID', 'woocommerce'),
                        'type' => 'text',
                        'description' => __('Get your Client ID from HitPay business account.', 'woocommerce'),
                        'desc_tip' => true,
                        'default' => '',
                    ),
                    'sandbox_client_secret' => array(
                        'title' => __('HitPay Sandbox Client Secret'),
                        'type' => 'text',
                        'description' => __('Get your Client Secret from HitPay business account.', 'woocommerce'),
                        'desc_tip' => true,
                        'default' => '',
                    ),
                    /*'paynow_only' => array(
                        'title' => __('Paynow Only', 'woocommerce'),
                        'type' => 'checkbox',
                        'label' => __('Paynow Only', 'woocommerce'),
                        'default' => 'no',
                        'description' => __('Show only paynow icon', 'woocommerce'),
                    ),*/
                    'testmode' => array(
                        'title' => __('HitPay sandbox', 'woocommerce'),
                        'type' => 'checkbox',
                        'label' => __('Enable HitPay sandbox', 'woocommerce'),
                        'default' => 'no',
                        'description' => __('HitPay sandbox can be used to test payments', 'woocommerce'),
                    ),
                    'paynow_qr' => array(
                        'title' => __('PayNow QR', 'woocommerce'),
                        'type' => 'checkbox',
                        'default' => 'yes',
                        'description' => $icon_paynow,
                    ),
                    'credit_card' => array(
                        'title' => __('Credit cards', 'woocommerce'),
                        'type' => 'checkbox',
                        'default' => 'yes',
                        'description' => $icon_cards,
                    ),
                    'wechat_alipay' => array(
                        'title' => __('WeChatPay and AliPay', 'woocommerce'),
                        'type' => 'checkbox',
                        'default' => 'yes',
                        'description' => $icon_webpay,
                    ),
                    'debug' => array(
                        'title' => __('Debug log', 'woocommerce'),
                        'type' => 'checkbox',
                        'label' => __('Enable logging', 'woocommerce'),
                        'default' => 'no',
                        'description' => sprintf(__('Log HitPay events inside %s Note: this may log personal information. We recommend using this for debugging purposes only and deleting the logs when finished.', 'woocommerce'), '<code>' . WC_Log_Handler_File::get_log_file_path('hitpay') . '</code>'),
                    ),
                );
            }

            public function process_payment($order_id)
            {
                global $woocommerce;

                $order = wc_get_order($order_id);

                return array(
                    'result' => 'success',
                    'redirect' => add_query_arg('order', $order->id, add_query_arg('key', $order->order_key, get_permalink(woocommerce_get_page_id('pay'))))
                );
            }

            public function receipt_page($order_id)
            {
                include_once dirname(__FILE__) . '/includes/class-wc-gateway-hitpay-form-request.php';

                $hitpayRequest = new WC_Gateway_HitPay_Form_Request($this);
                $order = wc_get_order($order_id);

                $hitpayRequest->display_form_request($order);
            }

            public function process_admin_options()
            {
                $saved = parent::process_admin_options();

                if ('yes' !== $this->get_option('debug', 'no')) {
                    if (empty(self::$log)) {
                        self::$log = wc_get_logger();
                    }

                    self::$log->clear('hitpay');
                }

                return $saved;
            }

            /*public function admin_options()
            {
                if ($this->is_valid_for_use()) {
                    parent::admin_options();
                } else {
                    include_once dirname(__FILE__) . '/partials/gateway-disabled.php';
                }
            }

            public function is_valid_for_use()
            {
                return in_array(
                    get_woocommerce_currency(),
                    apply_filters(
                        'woocommerce_hitpay_supported_currencies',
                        array('SGD')
                    ),
                    true
                );
            }*/

            public function process_refund($order_id, $amount = null, $reason = '')
            {
                $order = wc_get_order($order_id);

                if (!$this->can_refund_order($order)) {
                    return new WP_Error('error', __('Refund failed.', 'woocommerce'));
                }

                $this->init_api();

                $result = WC_Gateway_HitPay_API_Handler::refund_transaction($order, $amount, $reason);

                if (is_wp_error($result)) {
                    $this->log('Refund Failed: ' . $result->get_error_message(), 'error');

                    return new WP_Error('error', $result->get_error_message());
                }

                $this->log('Refund Result: ' . wc_print_r($result, true));

                switch (strtolower($result->ACK)) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
                    case 'success':
                    case 'successwithwarning':
                        $order->add_order_note(
                        /* translators: 1: Refund amount, 2: Refund ID */
                            sprintf(__('Refunded %1$s - Refund ID: %2$s', 'woocommerce'), $result->GROSSREFUNDAMT, $result->REFUNDTRANSACTIONID) // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
                        );
                        return true;
                }

                return isset($result->L_LONGMESSAGE0) ? new WP_Error('error', $result->L_LONGMESSAGE0) : false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
            }

            public function can_refund_order($order)
            {
                $has_api_creds = false;

                if ($this->testmode) {
                    $has_api_creds = $this->get_option('sandbox_client_id') && $this->get_option('sandbox_client_secret');
                } else {
                    $has_api_creds = $this->get_option('client_id') && $this->get_option('client_secret');
                }

                return $order && $order->get_transaction_id() && $has_api_creds;
            }

            public function get_icon()
            {
                list($icon_paynow, $icon_cards, $icon_webpay) = $this->getIcons();

                $icon_html = '';
                if ($this->wechat_alipay) {
                    $icon_html .= str_replace('<img ', '<img style="width: 60px;" ', $icon_webpay);
                }

                if ($this->credit_card) {
                    $icon_html .= str_replace('<img ', '<img style="width: 80px;" ', $icon_cards);
                }

                if ($this->paynow_qr) {
                    $icon_html .= str_replace('<img ', '<img style="width: 30px;" ', $icon_paynow);
                }

                /*$icon = WC_HTTPS::force_https_url(str_replace('/woocommerce', '', WC()->plugin_url()) . '/woocommerce-hitpay/assets/images/accepted-marks.png');

                if ($this->paynow_only) {
                    $icon = WC_HTTPS::force_https_url(str_replace('/woocommerce', '', WC()->plugin_url()) . '/woocommerce-hitpay/assets/images/accepted-marks-paynow.png');
                }

                $icon_html = '<img src="' . esc_attr($icon) . '" alt="' . esc_attr__('HitPay acceptance mark', 'woocommerce') . '" />';*/

                return apply_filters('woocommerce_gateway_icon', $icon_html, $this->id);
            }

            public static function generate_signature($params, $secret)
            {
                $hmacSource = [];

                ksort($params);

                foreach ($params as $key => $val) {
                    $hmacSource[] = "{$key}{$val}";
                }

                $sig = implode("", $hmacSource);
                $calculatedHmac = hash_hmac('sha256', $sig, $secret);

                return $calculatedHmac;
            }

            public static function log($message, $level = 'info')
            {
                if (self::$log_enabled) {
                    if (empty(self::$log)) {
                        self::$log = wc_get_logger();
                    }

                    self::$log->log($level, $message, array('source' => 'hitpay'));
                }
            }

            protected function init_api()
            {
                include_once dirname(__FILE__) . '/includes/class-wc-gateway-hitpay-api-handler.php';

                WC_Gateway_HitPay_API_Handler::$client_id = $this->testmode ? $this->get_option('sandbox_client_id') : $this->get_option('client_id');
                WC_Gateway_HitPay_API_Handler::$client_secret = $this->testmode ? $this->get_option('sandbox_client_secret') : $this->get_option('client_secret');
                WC_Gateway_HitPay_API_Handler::$sandbox = $this->testmode;
            }
        }
    }

    function add_hitpay_gateway_class($methods)
    {
        $methods[] = 'WC_Gateway_HitPay';

        return $methods;
    }

    function current_location()
    {
        if (isset($_SERVER['HTTPS']) &&
            ($_SERVER['HTTPS'] == 'on' || $_SERVER['HTTPS'] == 1) ||
            isset($_SERVER['HTTP_X_FORWARDED_PROTO']) &&
            $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https') {
            $protocol = 'https://';
        } else {
            $protocol = 'http://';
        }
        return $protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    }

    function woocommerce_hitpay_check_return()
    {
        if (!isset($_SERVER['HTTP_REFERER'])) {
            return;
        }

        $endpointTest = 'https://securecheckout.staging.hit-pay.loc/payment-gateway/woocommerce/checkout';
        $endpointProd = 'https://securecheckout.hit-pay.com/payment-gateway/woocommerce/checkout';

        $checkout_link = esc_url(wc_get_checkout_url());
        if (($endpointTest == $_SERVER['HTTP_REFERER']
            || $endpointProd == $_SERVER['HTTP_REFERER'])
            && strpos(current_location(), $checkout_link) === false) {
            header('Location: ' . $checkout_link);
            exit;
        }
    }

    add_action('init', 'woocommerce_hitpay_check_return');
    add_action('plugins_loaded', 'init_hitpay_gateway_class');
    add_filter('woocommerce_payment_gateways', 'add_hitpay_gateway_class');

}