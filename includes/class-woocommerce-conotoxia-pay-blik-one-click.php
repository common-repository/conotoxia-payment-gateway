<?php

use CKPL\Pay\Definition\BlikProfile\BlikProfileInterface;
use CKPL\Pay\Definition\BlikProfile\Builder\BlikProfileBuilder;
use CKPL\Pay\Definition\Confirm\Builder\ConfirmPaymentBuilder;
use CKPL\Pay\Definition\Confirm\ConfirmPaymentInterface;
use CKPL\Pay\Definition\Payment\Status\Reason;
use CKPL\Pay\Definition\Payment\Status\Status;
use CKPL\Pay\Exception\Api\ValidationErrorException;
use CKPL\Pay\Exception\Definition\BlikProfileException;
use CKPL\Pay\Exception\Definition\ConfirmPaymentException;
use CKPL\Pay\Exception\Exception;
use CKPL\Pay\Model\Response\BlikAliasResponseModel;
use WC_Gateway_Conotoxia_Pay_Logger as Logger;

class WC_Gateway_Conotoxia_Pay_Blik_One_Click extends WC_Payment_Gateway_Conotoxia
{
    const ALIASES_KEY = 'cx_blik_aliases';
    const DEFAULT_ALIAS_KEY = 'cx_default_blik_alias';

    public function __construct()
    {
        $this->id = Identifier::CONOTOXIA_PAY_BLIK_ONE_CLICK;
        $this->title = __('BLIK without code', CONOTOXIA_PAY);
        $this->has_fields = true;
        $this->method_title = __('BLIK OneClick via Conotoxia Pay', CONOTOXIA_PAY);
        $this->method_description = __(
            'Allow customers to pay with BLIK without a code by simply allowing the transaction to be completed directly on your online store\'s website via Conotoxia Pay payment gateway.',
            CONOTOXIA_PAY
        );
        $this->description = '';
        $this->supports = ['products', 'refunds'];
        $this->icon = apply_filters(CONOTOXIA_PAY . '_icon', plugins_url('images/blik.svg', __DIR__));
        if ($this->is_admin_panel()) {
            $this->init_form_fields();
        }
        $this->init_settings();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        add_action('woocommerce_after_checkout_form', [$this, 'prepare_checkout']);
        add_action('after_woocommerce_pay', [$this, 'prepare_checkout']);

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
     * @return void
     */
    public function prepare_checkout(): void
    {
        if (!$this->is_enabled()) {
            return;
        }
        $user_id = get_current_user_id();
        if (!$user_id) {
            return;
        }
        $aliases = $this->get_aliases($user_id);
        if (count($aliases) > 0) {
            echo WC_Gateway_Conotoxia_Pay_Blik_Without_Code_Notice_Template::get($aliases[0]->getAliasName());
        }
        $this->save_aliases($user_id, $aliases);
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
        if (!array_key_exists(Identifier::CONOTOXIA_PAY_BLIK, $payment_gateways) || get_woocommerce_currency() !== 'PLN') {
            unset($payment_gateways[$this->id]);
            return $payment_gateways;
        }
        $user_id = get_current_user_id();
        if ($user_id) {
            $aliases = $this->get_saved_aliases($user_id);
        }
        if (empty($aliases) || count($aliases) < 1) {
            unset($payment_gateways[$this->id]);
        } else {
            $payment_gateways[Identifier::CONOTOXIA_PAY_BLIK]->title = __('BLIK with code', CONOTOXIA_PAY);
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
                    $value = $this->get_field_value($key, $field, $post_data);
                    if ($key === 'enabled' && $value === 'yes' && !$this->is_enabled()) {
                        $this->show_notice(
                            __(
                                'In addition to the enabling of the payment method, it is also necessary for the shop to pass the certification process. Please contact us by email at <a href="mailto:payments@conotoxia.com">payments@conotoxia.com</a> to start the certification process.',
                                CONOTOXIA_PAY
                            ),
                            'info'
                        );
                    }
                    $this->settings[$key] = $value;
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
        $user_id = get_current_user_id();
        if (!$user_id) {
            Logger::log('Could not find user when displaying BLIK payment fields.');
            return;
        }
        $aliases = $this->get_saved_aliases($user_id);
        if (count($aliases) > 0) {
            $default_alias = $this->get_saved_default_alias($user_id);
            if (empty($default_alias)) {
                echo WC_Gateway_Conotoxia_Pay_Blik_Aliases_Template::get($aliases);
            } else {
                echo WC_Gateway_Conotoxia_Pay_Blik_Aliases_Template::get($aliases, $default_alias);
            }
        } else {
            Logger::log('Could not find aliases when displaying BLIK payment fields.');
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
            Logger::log('Could not find order with id \'%s\' when creating BLIK OneClick payment.', $order_id);
            return $this->get_failed_payment_processing();
        }
        if (!$order->needs_payment()) {
            Logger::log(
                'Order with id \'%s\' has invalid state (status \'%s\' and total \'%s\') when creating BLIK OneClick payment.',
                $order->get_order_number(),
                $order->get_status(),
                $order->get_total()
            );
            return $this->get_failed_payment_processing();
        }
        $user_id = get_current_user_id();
        if (!$user_id) {
            Logger::log('Could not find user when creating BLIK OneClick payment for order \'%s\'.', $order_id);
            $this->get_failed_payment_processing();
            return [];
        }
        $aliases = $this->get_saved_aliases($user_id);
        $alias = $this->resolve_alias($aliases, sanitize_text_field($_POST['cx-blik-alias']));
        if (!isset($alias)) {
            Logger::log('Could not match selected alias when creating BLIK OneClick payment for order \'%s\'.', $order_id);
            $this->get_failed_payment_processing();
            return [];
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
                $created_payment_response->getToken(),
                $alias->getAliasKey(),
                $alias->getAliasName(),
                $user_id,
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
            $this->save_default_alias($user_id, $alias);
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
        } elseif (Reason::isTooHighAmount($reason)) {
            $failed_payment_processing_with_reason = $this->get_failed_payment_processing(
                __('Payment amount too high.', CONOTOXIA_PAY)
            );
        } elseif (Reason::isAliasRejection($reason)) {
            $failed_payment_processing_with_reason = $this->get_failed_payment_processing(
                __('Payment requires BLIK code.', CONOTOXIA_PAY)
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
     * @return array
     */
    private function get_blik_form_fields(): array
    {
        return [
            'enabled' => [
                'title' => __('Enable/Disable', CONOTOXIA_PAY),
                'label' => __('Enable BLIK OneClick payment method', CONOTOXIA_PAY),
                'description' => __(
                    'Contact us by email at <a href="mailto:payments@conotoxia.com">payments@conotoxia.com</a> to start the certification process necessary to enable BLIK OneClick.',
                    CONOTOXIA_PAY
                ),
                'type' => 'checkbox',
                'default' => 'no',
            ],
            'asDefault' => [
                'title' => __('Default payment method', CONOTOXIA_PAY),
                'description' => __('Sets BLIK OneClick as the default payment method.', CONOTOXIA_PAY),
                'type' => 'checkbox',
                'default' => 'no',
            ],
        ];
    }

    /**
     * @return bool
     */
    private function is_enabled(): bool
    {
        return $this->get_option('enabled') === 'yes';
    }

    /**
     * @param int $user_id
     * @return void
     */
    public function get_aliases(int $user_id): ArrayIterator
    {
        try {
            $sdk = $this->initialize_conotoxia_pay();
            $blik_profile_request = $this->create_blik_profile_request($user_id);
            $blik_profiles_response = $sdk->payments()->getBlikProfiles($blik_profile_request);
            $aliases = $blik_profiles_response->getIterator();
        } catch (Exception $exception) {
            Logger::log($exception->getMessage());
            $aliases = new ArrayIterator();
        }
        return $aliases;
    }

    public function save_aliases(string $user_id, ArrayIterator $aliases): void
    {
        update_user_meta($user_id, self::ALIASES_KEY, $aliases);
    }

    public function get_saved_aliases(string $user_id): ArrayIterator
    {
        $aliases = get_user_meta($user_id, self::ALIASES_KEY, true);
        if (is_string($aliases)) {
            if (empty($aliases)) {
                return new ArrayIterator();
            }
            $aliases = unserialize($aliases);
        }
        if ($aliases instanceof ArrayIterator) {
            return $aliases;
        }
        return new ArrayIterator();
    }

    public function save_default_alias(string $user_id, BlikAliasResponseModel $default_alias): void
    {
        update_user_meta($user_id, self::DEFAULT_ALIAS_KEY, $default_alias);
    }

    public function get_saved_default_alias(string $user_id): ?BlikAliasResponseModel
    {
        $default_alias = get_user_meta($user_id, self::DEFAULT_ALIAS_KEY, true);
        if (is_string($default_alias)) {
            if (empty($default_alias)) {
                return null;
            }
            $default_alias = unserialize($default_alias);
        }
        if ($default_alias instanceof BlikAliasResponseModel) {
            return $default_alias;
        }
        return null;
    }

    /**
     * @param string $customer_id
     * @return BlikProfileInterface
     * @throws BlikProfileException
     */
    private function create_blik_profile_request(string $customer_id): BlikProfileInterface
    {
        $primary_gateway = $this->get_primary_gateway();
        return (new BlikProfileBuilder())
            ->setPointOfSaleId($primary_gateway->get_point_of_sale_id())
            ->setCustomerId($customer_id)
            ->getBlikProfile();
    }

    /**
     * @param WC_Order $order
     * @param string $payment_token
     * @param string $alias_key
     * @param string $alias_name
     * @param string $customer_id
     * @param string $user_screen_resolution
     * @param string $user_agent
     * @return ConfirmPaymentInterface
     * @throws ConfirmPaymentException
     */
    private function create_confirm_payment_request(
        WC_Order $order,
        string   $payment_token,
        string   $alias_key,
        string   $alias_name,
        string   $customer_id,
        string   $user_screen_resolution,
        string   $user_agent
    ): ConfirmPaymentInterface
    {
        return (new ConfirmPaymentBuilder())
            ->setToken($payment_token)
            ->setType('BLIK')
            ->setAliasKey($alias_key)
            ->setAliasName($alias_name)
            ->setCustomerId($customer_id)
            ->setFirstName($order->get_billing_first_name())
            ->setLastName($order->get_billing_last_name())
            ->setEmail($order->get_billing_email())
            ->setAcceptLanguage($this->get_accept_language())
            ->setUserScreenResolution($user_screen_resolution)
            ->setUserAgent($user_agent)
            ->setUserIpAddress(WC_Geolocation::get_ip_address())
            ->setUserPort($this->get_remote_port())
            ->setFingerprint($this->get_fingerprint())
            ->getConfirmPayment();
    }

    /**
     * @param ArrayIterator $aliases
     * @param string $alias_key
     * @return BlikAliasResponseModel|null
     */
    private function resolve_alias(ArrayIterator $aliases, string $alias_key): ?BlikAliasResponseModel
    {
        if (empty($alias_key)) {
            return null;
        }
        foreach ($aliases as $alias) {
            if ($alias->getAliasKey() === $alias_key) {
                return $alias;
            }
        }
        return null;
    }
}