<?php

use CKPL\Pay\Definition\Confirm\Builder\ConfirmPaymentBuilder;
use CKPL\Pay\Definition\Confirm\ConfirmPaymentInterface;
use CKPL\Pay\Definition\Payment\Status\Reason;
use CKPL\Pay\Definition\Payment\Status\Status;
use CKPL\Pay\Exception\Api\ValidationErrorException;
use CKPL\Pay\Exception\Definition\ConfirmPaymentException;
use CKPL\Pay\Exception\Exception;
use WC_Gateway_Conotoxia_Pay_Logger as Logger;

class WC_Gateway_Conotoxia_Pay_Blik extends WC_Payment_Gateway_Conotoxia
{
    public function __construct()
    {
        $this->id = Identifier::CONOTOXIA_PAY_BLIK;
        $this->title = 'BLIK';
        $this->has_fields = true;
        $this->method_title = __('BLIK Level 0 via Conotoxia Pay', CONOTOXIA_PAY);
        $this->method_description = __(
            'Allow customers to pay with BLIK by simply allowing the transaction to be completed directly on your online store\'s website via Conotoxia Pay payment gateway.',
            CONOTOXIA_PAY
        );
        $this->description = '';
        $this->supports = ['products', 'refunds'];
        $this->init_payment_icon('images/blik.svg');
        if ($this->is_admin_panel()) {
            $this->init_form_fields();
        }
        $this->init_settings();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_after_checkout_validation', [$this, 'validate_blik_code'], 10, 2);
        add_action('woocommerce_after_checkout_validation', [$this, 'validate_user_data'], 10, 2);

        add_filter('woocommerce_available_payment_gateways', [$this, 'resolve_gateway_availability']);
    }

    /**
     * @return void
     */
    public function init_form_fields(): void
    {
        $this->form_fields = $this->get_blik_form_fields();
    }

    /**
     * @param mixed $payment_gateways
     * @return mixed
     */
    public function resolve_gateway_availability($payment_gateways)
    {
        if (!array_key_exists($this->id, $payment_gateways) || !$this->is_checkout()) {
            return $payment_gateways;
        }
        if (get_woocommerce_currency() !== 'PLN') {
            unset($payment_gateways[$this->id]);
        }
        return $payment_gateways;
    }

    /**
     * @return bool
     */
    public function process_admin_options(): bool
    {
        $this->init_settings();
        $post_data = $this->get_post_data();

        foreach ($this->get_form_fields() as $key => $field) {
            if (array_key_exists($key, $this->get_blik_form_fields())) {
                try {
                    $this->settings[$key] = $this->get_field_value($key, $field, $post_data);
                } catch (\Exception $exception) {
                    $this->add_error($exception->getMessage());
                }
            } else {
                unset($this->settings[$key]);
            }
        }

        $option_key = $this->get_option_key();
        do_action('woocommerce_update_option', ['id' => $option_key]);
        $processed = update_option(
            $option_key,
            apply_filters('woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings),
            'yes'
        );

        if ($processed && !$this->get_primary_gateway()->is_configuration_completed()) {
            $this->show_notice(
                __(
                    'Enter the required data from the Conotoxia Pay payment gateway to complete the configuration.',
                    CONOTOXIA_PAY
                )
            );
        }
        return $processed;
    }

    /**
     * @return void
     */
    public function payment_fields(): void
    {
        parent::payment_fields();
        echo WC_Gateway_Conotoxia_Pay_Blik_Form_Template::get();
    }

    /**
     * @param mixed $data
     * @param mixed $errors
     * @return void
     */
    public function validate_blik_code($data, $errors): void
    {
        if (is_array($data) && array_key_exists('payment_method', $data) && $data['payment_method'] === $this->id) {
            $invalid_blik_code_message = __('The BLIK code should have 6 digits.', CONOTOXIA_PAY);
            $empty_blik_code_message = __('Enter the BLIK code.', CONOTOXIA_PAY);
            if (!isset($_POST['cx-blik-code'])) {
                $errors->add('validation', $empty_blik_code_message);
                return;
            }
            $blik_code = $this->resolve_blik_code(sanitize_text_field($_POST['cx-blik-code']));
            if (strlen($blik_code) === 0) {
                $errors->add('validation', $empty_blik_code_message);
            } elseif (strlen($blik_code) !== 6) {
                $errors->add('validation', $invalid_blik_code_message);
            }
        }
    }

    /**
     * @param mixed $data
     * @param mixed $errors
     * @return void
     */
    public function validate_user_data($data, $errors): void
    {
        if (is_array($data) && array_key_exists('payment_method', $data) && $data['payment_method'] === $this->id) {
            if (empty($_POST['billing_first_name'])) {
                Logger::log('Billing first name is a required field.');
                $errors->add('validation', __('Billing first name is a required field.', CONOTOXIA_PAY));
                return;
            }

            if (empty($_POST['billing_last_name'])) {
                Logger::log('Billing last name is a required field.');
                $errors->add('validation', __('Billing last name is a required field.', CONOTOXIA_PAY));
                return;
            }

            if (empty($_POST['billing_email'])) {
                Logger::log('Billing email is a required field.');
                $errors->add('validation', __('Billing email is a required field.', CONOTOXIA_PAY));
            }
        }
    }

    /**
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id): array
    {
        $order = wc_get_order($order_id);
        if (!$order) {
            Logger::log('Could not find order with id \'%s\' when creating BLIK Level 0 payment.', $order_id);
            return $this->get_failed_payment_processing();
        }
        if (!$order->needs_payment()) {
            Logger::log(
                'Order with id \'%s\' has invalid state (status \'%s\' and total \'%s\') when creating BLIK Level 0 payment.',
                $order->get_order_number(),
                $order->get_status(),
                $order->get_total()
            );
            return $this->get_failed_payment_processing();
        }

        try {
            $sdk = $this->initialize_conotoxia_pay();
            $created_payment_response = $this->create_or_retry_payment($order, $sdk);
        } catch (ValidationErrorException $exception) {
            $log_messages = $this->prepare_validation_log_message($exception);
            Logger::log('Payment creation validation problem for order \'%s\': %s', $order_id, $log_messages);
            return $this->get_failed_payment_processing();
        } catch (Exception $exception) {
            Logger::log('Payment creation problem for order \'%s\': %s', $order_id, $exception->getMessage());
            return $this->get_failed_payment_processing();
        }

        $payment_id = $created_payment_response->getPaymentId();
        try {
            $this->associate_payment_to_order($order, $payment_id);
        } catch (WC_Data_Exception $e) {
            Logger::log(
                'Problem with saving payment \'%s\' for order \'%s\': %s',
                $payment_id,
                $order_id,
                $e->getMessage()
            );
            return $this->get_failed_payment_processing();
        }

        try {
            $confirm_payment_request = $this->create_confirm_payment_request(
                $order,
                $this->resolve_blik_code(sanitize_text_field($_POST['cx-blik-code'])),
                $created_payment_response->getToken(),
                get_current_user_id(),
                sanitize_text_field($_POST['cx-user-screen-resolution']),
                sanitize_text_field($_POST['cx-user-agent'])
            );
            $confirm_payment_response = $sdk->payments()->confirmPayment($confirm_payment_request);
        } catch (ValidationErrorException $exception) {
            $language_code = $this->get_language_code();
            $log_messages = $this->prepare_validation_log_message($exception);
            $messages = $this->prepare_blik_validation_messages($exception->getLocalizedMessages($language_code));
            Logger::log(
                'Payment \'%s\' confirmation validation problem for order \'%s\': %s',
                $payment_id,
                $order_id,
                $log_messages
            );
            return $this->get_failed_payment_processing($messages);
        } catch (Exception $exception) {
            Logger::log(
                'Payment \'%s\' confirmation problem for order \'%s\': %s',
                $payment_id,
                $order_id,
                $exception->getMessage()
            );
            return $this->get_failed_payment_processing();
        }

        $status = $confirm_payment_response->getPaymentStatus();
        if (Status::isConfirmed($status) || Status::isWaiting($status)) {
            WC()->cart->empty_cart();
            return [
                'result' => 'success',
                'redirect' => add_query_arg(
                    ['order_id' => $order_id, 'order_key' => $order->get_order_key()],
                    get_permalink(get_page_by_path('cx_blik_status')->ID)
                ),
            ];
        }

        $reason = $confirm_payment_response->getReason();
        if (Reason::isCodeError($reason)) {
            $failed_payment_processing_with_reason = $this->get_failed_payment_processing(
                __('Incorrect BLIK code was entered.', CONOTOXIA_PAY),
                __('Try again.', CONOTOXIA_PAY)
            );
        } elseif (Reason::isBankRejection($reason)) {
            $failed_payment_processing_with_reason = $this->get_failed_payment_processing(
                __('Payment failed.', CONOTOXIA_PAY),
                __('Check the reason in the banking app and try again.', CONOTOXIA_PAY)
            );
        } elseif (Reason::isUserRejection($reason)) {
            $failed_payment_processing_with_reason = $this->get_failed_payment_processing(
                __('Payment rejected in a banking app.', CONOTOXIA_PAY),
                __('Try again.', CONOTOXIA_PAY)
            );
        } elseif (Reason::isTimeout($reason)) {
            $failed_payment_processing_with_reason = $this->get_failed_payment_processing(
                __('Payment failed - not confirmed on time in the banking app.', CONOTOXIA_PAY),
                __('Try again.', CONOTOXIA_PAY)
            );
        }

        Logger::log(
            'Payment \'%s\' confirmation problem for order \'%s\'; status: \'%s\'; reason: \'%s\'',
            $payment_id,
            $order_id,
            $status,
            $reason
        );
        return $failed_payment_processing_with_reason ?? $this->get_failed_payment_processing();
    }

    /**
     * @return WC_Gateway_Conotoxia_Pay_Blik
     */
    protected function get_blik_gateway(): WC_Gateway_Conotoxia_Pay_Blik
    {
        return $this;
    }

    /**
     * @param WC_Order $order
     * @param string $blik_code
     * @param string $payment_token
     * @param string $customer_id
     * @param string $user_screen_resolution
     * @param string $user_agent
     * @return ConfirmPaymentInterface
     * @throws ConfirmPaymentException
     */
    private function create_confirm_payment_request(
        WC_Order $order,
        string   $blik_code,
        string   $payment_token,
        string   $customer_id,
        string   $user_screen_resolution,
        string   $user_agent
    ): ConfirmPaymentInterface
    {
        $builder = (new ConfirmPaymentBuilder())
            ->setBlikCode($blik_code)
            ->setToken($payment_token)
            ->setEmail($order->get_billing_email())
            ->setType('BLIK')
            ->setFirstName($order->get_billing_first_name())
            ->setLastName($order->get_billing_last_name())
            ->setAcceptLanguage($this->get_accept_language())
            ->setUserScreenResolution($user_screen_resolution)
            ->setUserAgent($user_agent)
            ->setUserIpAddress(WC_Geolocation::get_ip_address())
            ->setUserPort($this->get_remote_port())
            ->setFingerprint($this->get_fingerprint());
        if ($customer_id) {
            $builder->setCustomerId($customer_id);
        }
        return $builder->getConfirmPayment();
    }

    /**
     * @return array
     */
    private function get_blik_form_fields(): array
    {
        return [
            'enabled' => [
                'title' => __('Enable/Disable', CONOTOXIA_PAY),
                'label' => __('Enable BLIK Level 0 payment method', CONOTOXIA_PAY),
                'type' => 'checkbox',
                'default' => 'no',
            ],
            'icon' => [
                'title' => __('BLIK icon', CONOTOXIA_PAY),
                'description' => __('Show the BLIK icon on the payment method selection screen.', CONOTOXIA_PAY),
                'type' => 'checkbox',
                'default' => 'no',
            ],
            'asDefault' => [
                'title' => __('Default payment method', CONOTOXIA_PAY),
                'description' => __('Sets BLIK Level 0 as the default payment method.', CONOTOXIA_PAY),
                'type' => 'checkbox',
                'default' => 'no',
            ],
            'redirect_to_order' => [
                'title' => __('Go to the order', CONOTOXIA_PAY),
                'description' => __('Go to the order summary after payment.', CONOTOXIA_PAY),
                'type' => 'checkbox',
                'default' => 'no',
            ],
        ];
    }

    /**
     * @param string $value
     * @return string
     */
    private function resolve_blik_code(string $value): string
    {
        if (empty($value)) {
            return "";
        }
        $value_array = str_split($value);
        $value_array = array_filter($value_array, function ($element) {
            return is_numeric($element);
        });
        return implode($value_array);
    }

    public function is_redirect_to_order_enabled(): bool
    {
        return $this->get_option('redirect_to_order') === 'yes';
    }
}