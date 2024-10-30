<?php

use CKPL\Pay\Configuration\ConfigurationInterface;
use CKPL\Pay\Configuration\Factory\ConfigurationFactory;
use CKPL\Pay\Definition\Payment\Builder\AmountBuilder;
use CKPL\Pay\Definition\Payment\Builder\PaymentBuilder;
use CKPL\Pay\Definition\Payment\Builder\SelectedPaymentMethodBuilder;
use CKPL\Pay\Definition\Payment\PaymentInterface;
use CKPL\Pay\Definition\Retry\RetryPayment;
use CKPL\Pay\Definition\StoreCustomer\StoreCustomer;
use CKPL\Pay\Exception\Api\ValidationErrorException;
use CKPL\Pay\Exception\ConfigurationException;
use CKPL\Pay\Exception\Definition\AmountException;
use CKPL\Pay\Exception\Definition\PaymentException;
use CKPL\Pay\Exception\Exception;
use CKPL\Pay\Exception\Http\HttpConflictException;
use CKPL\Pay\Exception\Http\HttpNotFoundException;
use CKPL\Pay\Model\Response\CreatedPaymentResponseModel;
use CKPL\Pay\Model\Response\RetriedPaymentResponseModel;
use CKPL\Pay\Pay;
use WC_Gateway_Conotoxia_Pay_Logger as Logger;

abstract class WC_Payment_Gateway_Conotoxia extends WC_Payment_Gateway
{

    /**
     * @type string
     */
    private const PAYMENTS_HOST = 'https://partner.cinkciarz.pl';

    /**
     * @type string
     */
    private const SANDBOX_PAYMENTS_HOST = 'https://pay-api.ckpl.us';

    /**
     * @type string
     */
    private const OIDC_HOST = 'https://login.cinkciarz.pl';

    /**
     * @type string
     */
    private const SANDBOX_OIDC_HOST = 'https://login.ckpl.io';

    /**
     * @param int $order_id
     * @param float|null $amount
     * @param string $reason
     * @return bool|WP_Error
     */
    public function process_refund($order_id, $amount = null, $reason = '')
    {
        $order = wc_get_order($order_id);
        if (empty($order->get_transaction_id())) {
            Logger::log('Missing transaction id for order with id \'%s\'', $order->get_order_number());
            return new WP_Error(CONOTOXIA_PAY, __('Missing transaction id.', CONOTOXIA_PAY));
        }

        if (!is_numeric($amount)) {
            Logger::log('Invalid refund amount format: ' . $amount);
            return new WP_Error(CONOTOXIA_PAY, __('Invalid refund amount format.', CONOTOXIA_PAY));
        }


        try {
            $sdk = $this->initialize_conotoxia_pay();
            $refund = $sdk->refunds()->createRefundBuilder()
                ->setReason(empty($reason) ? __('Refund reason', CONOTOXIA_PAY) : $reason)
                ->setValue(number_format(floatval($amount), 2, '.', ''))
                ->setCurrency($order->get_currency())
                ->setExternalRefundId($order->get_order_number())
                ->setPaymentId($order->get_transaction_id())
                ->setIntegrationPlatform($this->get_shop_version())
                ->setAcceptLanguage($this->get_accept_language())
                ->setNotificationUrlParameters(['wcOrderKey' => $order->get_order_key()])
                ->getRefund();
            $created_refund = $sdk->refunds()->makeRefund($refund);
            $order->add_meta_data(
                '_refund',
                ['id' => $created_refund->getId(), 'amount' => number_format($amount, 2, '.', '')]
            );
        } catch (HttpConflictException|HttpNotFoundException $exception) {
            $locale_iso_code = substr(get_bloginfo("language"), 0, 2);
            $exception = $exception->getTranslatedMessage($locale_iso_code) ?? $this->get_refund_creation_problem();
            wc_add_notice($exception, 'error');
            return new WP_Error(CONOTOXIA_PAY, $exception);
        } catch (ValidationErrorException $exception) {
            $language_code = $this->get_language_code();
            $log_messages = $this->prepare_validation_log_message($exception);
            Logger::log('Refund creation problem %s', $log_messages);
            return new WP_Error(CONOTOXIA_PAY, $exception->getLocalizedMessages($language_code));
        } catch (Exception $exception) {
            wc_add_notice(__('Refund creation problem.', CONOTOXIA_PAY), 'error');
            return $this->get_refund_creation_problem();
        }
        return true;
    }

    /**
     * @return Pay
     * @throws ConfigurationException
     */
    public function initialize_conotoxia_pay(): Pay
    {
        $primary_gateway = $this->get_primary_gateway();
        return new Pay(ConfigurationFactory::fromArray([
            ConfigurationInterface::HOST => $this->get_payment_host($primary_gateway),
            ConfigurationInterface::OIDC => $this->get_oidc_host($primary_gateway),
            ConfigurationInterface::CLIENT_ID => $primary_gateway->get_option('client_id'),
            ConfigurationInterface::CLIENT_SECRET => $primary_gateway->get_option('client_secret'),
            ConfigurationInterface::POINT_OF_SALE => $primary_gateway->get_point_of_sale_id(),
            ConfigurationInterface::PRIVATE_KEY => $primary_gateway->get_option('private_key'),
            ConfigurationInterface::PUBLIC_KEY => $primary_gateway->get_option('public_key'),
            ConfigurationInterface::STORAGE => new WC_Gateway_Conotoxia_Pay_Storage(),
        ]));
    }

    /**
     * @param string $payment_id
     * @param Pay|null $sdk
     * @return RetriedPaymentResponseModel
     * @throws Exception
     */
    public function retry_payment(string $payment_id, ?Pay $sdk = null): RetriedPaymentResponseModel
    {
        if (!isset($sdk)) {
            $sdk = $this->initialize_conotoxia_pay();
        }
        $retry_payment_request = new RetryPayment($payment_id);
        return $sdk->payments()->retryPayment($retry_payment_request);
    }

    /**
     * @return bool
     */
    public function is_checkout(): bool
    {
        return $this->has_woo_checkout_block() || is_checkout();
    }

    /**
     * @param string $icon_path
     * @return void
     */
    protected function init_payment_icon(string $icon_path): void
    {
        if ($this->is_payment_icon_enabled()) {
            $this->icon = apply_filters(
                CONOTOXIA_PAY . '_icon',
                plugins_url($icon_path, __DIR__)
            );
        }
    }

    /**
     * @param string $message
     * @param string $level
     * @return void
     */
    protected function show_notice(string $message, string $level = 'warning'): void
    {
        $allowed_html = [
            'a' => [
                'href' => [],
                'target' => [],
                'rel' => [],
            ]
        ];
        ob_start();
        echo '<div class="notice notice-' . esc_html($level) . '"><p><strong>' . wp_kses($message, $allowed_html) . '</strong></p></div>';
    }

    /**
     * @return bool
     */
    protected function is_admin_panel(): bool
    {
        return (
            $this->get_requested_parameter('page') === 'wc-settings'
            &&
            $this->get_requested_parameter('section') == $this->id
        );
    }

    /**
     * @return string
     */
    protected function get_shop_version(): string
    {
        global $wp_version;
        return 'WORDPRESS=' . $wp_version . ';WOO=' . WC()->version . ';PLUGIN=' . CONOTOXIA_PAY_VERSION;
    }

    /**
     * @return string|null
     */
    protected function get_accept_language(): ?string
    {
        return isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) ? sanitize_text_field($_SERVER['HTTP_ACCEPT_LANGUAGE']) : null;
    }

    /**
     * @return string|null
     */
    protected function get_remote_port(): ?string
    {
        if (!empty($_SERVER['REMOTE_PORT'])) {
            return sanitize_text_field($_SERVER['REMOTE_PORT']);
        }
        return null;
    }

    /**
     * @return string
     */
    protected function get_fingerprint(): string
    {
        $fingerprint = isset($GLOBALS['_COOKIE']['PHPSESSID'])
            ? md5(sanitize_text_field($GLOBALS['_COOKIE']['PHPSESSID']))
            : '-';
        $domain = parse_url(get_site_url(), PHP_URL_HOST);
        return '{"' . $domain . '":"' . $fingerprint . '"}';
    }

    /**
     * @param string $order_number
     * @return string
     */
    protected function get_payment_description(string $order_number): string
    {
        $blog_info = get_bloginfo('name');
        $order_keyword = ' ' . __('order', CONOTOXIA_PAY);
        $order_number_info = ' #' . $order_number;

        $max_length = 128;
        $available_length_for_blog_info_and_keyword = $max_length - strlen($order_number_info);

        $blog_info_and_keyword = $blog_info . $order_keyword;
        $total_length = strlen($blog_info_and_keyword);

        if ($total_length > $available_length_for_blog_info_and_keyword) {
            $blog_info_and_keyword = substr($blog_info_and_keyword, 0, $available_length_for_blog_info_and_keyword);
        }

        return sanitize_text_field($blog_info_and_keyword . $order_number_info);
    }

    /**
     * @return WC_Gateway_Conotoxia_Pay
     */
    protected function get_primary_gateway(): WC_Gateway_Conotoxia_Pay
    {
        $payment_gateways = WC()->payment_gateways()->payment_gateways();
        if (isset($payment_gateways[Identifier::CONOTOXIA_PAY])) {
            return $payment_gateways[Identifier::CONOTOXIA_PAY];
        }
        Logger::log('Unable to access primary gateway - creating new instance instead');
        return new WC_Gateway_Conotoxia_Pay();
    }

    /**
     * @return WC_Gateway_Conotoxia_Pay_Blik
     */
    protected function get_blik_gateway(): WC_Gateway_Conotoxia_Pay_Blik
    {
        $payment_gateways = WC()->payment_gateways()->payment_gateways();
        if (isset($payment_gateways[Identifier::CONOTOXIA_PAY_BLIK])) {
            return $payment_gateways[Identifier::CONOTOXIA_PAY_BLIK];
        }
        Logger::log('Unable to access BLIK gateway - creating new instance instead');
        return new WC_Gateway_Conotoxia_Pay_Blik();
    }

    /**
     * @param bool|WC_Order|WC_Order_Refund $order
     * @param Pay|null $sdk
     * @return CreatedPaymentResponseModel|RetriedPaymentResponseModel
     * @throws Exception
     */
    protected function create_or_retry_payment($order, ?Pay $sdk = null)
    {
        if (!isset($sdk)) {
            $sdk = $this->initialize_conotoxia_pay();
        }
        $transaction_id = $order->get_transaction_id();
        if ($this->is_payment_id($transaction_id)) {
            return $this->retry_payment($transaction_id, $sdk);
        }
        return $this->create_payment($order, $sdk);
    }

    /**
     * @param bool|WC_Order|WC_Order_Refund $order
     * @param string|null $payment_id
     * @return void
     * @throws WC_Data_Exception
     */
    protected function associate_payment_to_order($order, ?string $payment_id)
    {
        $custom_orders_enabled = $this->is_custom_orders_enabled();
        if ($custom_orders_enabled) {
            $parent_payment_id = $order->get_transaction_id();
        } else {
            $parent_payment_id = get_post_meta($order->get_id(), '_transaction_id', true);
        }
        if ($custom_orders_enabled) {
            $order->set_transaction_id($payment_id);
            $order->save();
        } elseif ($parent_payment_id) {
            update_post_meta($order->get_id(), '_transaction_id', $payment_id);
        } else {
            add_post_meta($order->get_id(), '_transaction_id', $payment_id, true);
        }
        if ($parent_payment_id) {
            $order_note = sprintf(
                __(
                    'Conotoxia Pay: Re-payment %s has been created. Waiting for customer confirmation.',
                    CONOTOXIA_PAY
                ),
                $payment_id
            );
        } else {
            $order_note = sprintf(
                __('Conotoxia Pay: Payment %s has been created. Waiting for customer confirmation.', CONOTOXIA_PAY),
                $payment_id
            );
        }
        $order->add_order_note($order_note);
        if ($this->get_primary_gateway()->is_on_hold_enabled()) {
            $order->update_status('on-hold');
        }
    }

    /**
     * @param string $transaction_id
     * @return bool
     */
    protected function is_payment_id(string $transaction_id): bool
    {
        return preg_match('/^PAY\d{15,}$/', $transaction_id);
    }

    /**
     * @return string
     */
    protected function get_language_code(): string
    {
        return substr(get_bloginfo("language"), 0, 2);
    }

    /**
     * @param ValidationErrorException $exception
     * @return string
     */
    protected function prepare_validation_log_message(ValidationErrorException $exception): string
    {
        return implode(" ", $exception->getLogMessages());
    }

    /**
     * @param array $messages
     * @return array
     */
    protected function prepare_blik_validation_messages(array $messages): array
    {
        $context_key_map = [
            'additionalData.email' => __('Email', CONOTOXIA_PAY),
            'additionalData.firstName' => __('First name', CONOTOXIA_PAY),
            'additionalData.lastName' => __('Last name', CONOTOXIA_PAY)
        ];

        $correct_messages = [];
        foreach ($messages as $message) {
            foreach ($context_key_map as $key => $value) {
                if (strpos($message, $key) !== false) {
                    $correct_messages[] = str_replace($key, $value, $message);
                    break;
                }
            }
        }

        return $correct_messages;
    }

    /**
     * @param string|array ...$messages
     * @return array
     */
    protected function get_failed_payment_processing(...$messages): array
    {
        if (is_array($messages[0])) {
            foreach ($messages[0] as $msg) {
                wc_add_notice($msg, 'error');
            }
        } else {
            $message = implode(' ', $messages);
            if (empty($message)) {
                $message = __(
                    'There was a problem with creating payment. Please contact the store support.',
                    CONOTOXIA_PAY
                );
            }
            wc_add_notice($message, 'error');
        }
        return [];
    }

    /**
     * @return bool
     */
    private function has_woo_checkout_block(): bool
    {
        return class_exists('WC_Blocks_Utils') && WC_Blocks_Utils::has_block_in_page(get_queried_object(), 'woocommerce/checkout');
    }

    /**
     * @param WC_Order $order
     * @param Pay|null $sdk
     * @return CreatedPaymentResponseModel
     * @throws Exception
     */
    private function create_payment(WC_Order $order, ?Pay $sdk = null): CreatedPaymentResponseModel
    {
        if (!isset($sdk)) {
            $sdk = $this->initialize_conotoxia_pay();
        }
        $create_payment_request = $this->create_payment_request($order);
        return $sdk->payments()->makePayment($create_payment_request);
    }

    /**
     * @param string $key
     * @param string $default
     * @return string
     */
    private function get_requested_parameter(string $key, string $default = ''): string
    {
        return isset($_GET[$key]) ? sanitize_text_field($_GET[$key]) : $default;
    }

    /**
     * @return bool
     */
    private function is_payment_icon_enabled(): bool
    {
        return $this->get_option('icon') === 'yes';
    }

    /**
     * @param WC_Gateway_Conotoxia_Pay $primary_gateway
     * @return string
     */
    private function get_payment_host(WC_Gateway_Conotoxia_Pay $primary_gateway): string
    {
        if ($primary_gateway->is_sandbox_mode_enabled()) {
            return self::SANDBOX_PAYMENTS_HOST;
        }
        return self::PAYMENTS_HOST;
    }

    /**
     * @param WC_Gateway_Conotoxia_Pay $primary_gateway
     * @return string
     */
    private function get_oidc_host(WC_Gateway_Conotoxia_Pay $primary_gateway): string
    {
        if ($primary_gateway->is_sandbox_mode_enabled()) {
            return self::SANDBOX_OIDC_HOST;
        }
        return self::OIDC_HOST;
    }

    /**
     * @return WP_Error
     */
    private function get_refund_creation_problem(): WP_Error
    {
        return new WP_Error(CONOTOXIA_PAY, __('Refund creation problem.', CONOTOXIA_PAY));
    }

    /**
     * @return bool
     */
    private function is_custom_orders_enabled(): bool
    {
        return class_exists(\Automattic\WooCommerce\Utilities\OrderUtil::class)
            && \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
    }

    /**
     * @param WC_Order $order
     * @return PaymentInterface
     * @throws AmountException
     * @throws PaymentException
     */
    private function create_payment_request(WC_Order $order): PaymentInterface
    {
        $primary_gateway = $this->get_primary_gateway();
        $selected_payment_method_builder = new SelectedPaymentMethodBuilder();
        if (in_array($order->get_payment_method(), Identifier::get_blik_ids())) {
            $selected_payment_method_builder->setType('BLIK');
        }
        $return_url = $this->get_return_url($order);
        return (new PaymentBuilder())
            ->setExternalPaymentId($order->get_order_number())
            ->setAmount((new AmountBuilder())
                ->setValue(number_format($order->get_total(), 2, '.', ''))
                ->setCurrency(get_woocommerce_currency())
                ->getAmount()
            )
            ->setStoreCustomer((new StoreCustomer())
                ->setFirstName($order->get_billing_first_name())
                ->setLastName($order->get_billing_last_name())
                ->setEmail($order->get_billing_email())
            )
            ->setSelectedPaymentMethod($selected_payment_method_builder->getSelectedPaymentMethod())
            ->setDescription($this->get_payment_description($order->get_order_number()))
            ->setReturnUrl($return_url)
            ->setErrorUrl($return_url)
            ->setIntegrationPlatform($this->get_shop_version())
            ->setAcceptLanguage($this->get_accept_language())
            ->setUserAcceptLanguage($this->get_accept_language())
            ->setNotificationUrlParameters(['wcOrderKey' => $order->get_order_key()])
            ->denyPayLater()
            ->setRetryEnabled($primary_gateway->is_retry_enabled())
            ->getPayment();
    }
}