<?php

class WC_Gateway_Conotoxia_Pay_Blik_Status_Template extends WC_Gateway_Conotoxia_Pay_Template
{
    /**
     * @param WC_Order $order
     * @param bool $redirect_to_order_summary
     * @return string
     */
    public static function get(WC_Order $order, bool $redirect_to_order_summary): string
    {
        $order_id = esc_html($order->get_id());
        $order_key = esc_html($order->get_order_key());
        $payment_id = esc_html($order->get_transaction_id());
        $email = esc_html($order->get_billing_email());

        $ajax_url = esc_url(admin_url('admin-ajax.php'));
        $conotoxia_pay_logo_url = esc_url(plugins_url('images/conotoxia_pay_logo_raw.svg', dirname(__FILE__, 2)));
        $blik_logo_url = esc_url(plugins_url('images/blik.svg', dirname(__FILE__, 2)));
        $success_icon_url = esc_url(plugins_url('images/success_icon.svg', dirname(__FILE__, 2)));
        $phone_icon_url = esc_url(plugins_url('images/phone.svg', dirname(__FILE__, 2)));
        $error_icon_url = esc_url(plugins_url('images/error_icon.svg', dirname(__FILE__, 2)));

        $confirm_blik_payment_in_your_banking_app = esc_html(
            __('Confirm your BLIK payment in your banking app', CONOTOXIA_PAY)
        );
        $payment_number = esc_html(__('Payment number:', CONOTOXIA_PAY));
        $payment_of_this_shop_is_processed_by_conotoxia = esc_html(
                __('Payment for this store is executed by', CONOTOXIA_PAY)
            ) . ' Conotoxia Sp. z o.o.';
        if ($redirect_to_order_summary) {
            $redirect_url = esc_url($order->get_checkout_order_received_url());
            $redirect_text = esc_html(__('Order summary', CONOTOXIA_PAY));
        } else {
            $redirect_url = esc_url(wc_get_page_permalink('shop'));
            $redirect_text = esc_html(__('Continue shopping', CONOTOXIA_PAY));
        }
        $retry_text = esc_html(__('Repeat your payment', CONOTOXIA_PAY));
        $retry_error_message = __(
            'There was a problem with creating payment. Please contact the store support.',
            CONOTOXIA_PAY
        );

        $success_template = self::get_success($email);
        $timeout_template = self::get_timeout($payment_id);
        $error_template = self::get_error($payment_id);
        $problem_template = self::get_problem($payment_id, $email);
        $code_error_template = self::get_code_error($payment_id);
        $bank_rejection_template = self::get_bank_rejection($payment_id);
        $user_rejection_template = self::get_user_rejection($payment_id);
        $too_high_amount_template = self::get_too_high_amount($payment_id);
        $alias_rejection_template = self::get_alias_rejection($payment_id);
        $template = <<<HTML
            <style>
                #js-cx-blik-status-container {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    text-align: center;
                    padding: 40px 40px 56px;
                    margin: 5px auto;
                    max-width: 600px;
                    gap: 32px;
                    background: white;
                    border-radius: 24px;
                    box-shadow: 0 0 2px black;
                }
                #cx-blik-status-header {
                    display: flex;
                    width: 100%;
                    justify-content: space-between;
                }
                #cx-blik-status-cx-pay-logo {
                    width: fit-content;
                    height: 28px;
                }
                #cx-blik-status-blik-logo {
                    width: fit-content;
                    height: 30px;
                }
                .cx-blik-status-icon {
                    width: 32px;
                    height: 32px;
                }
                .cx-blik-status-primary-text {
                    font-weight: 800;
                    font-size: 32px;
                    line-height: 38px;
                    color: #333333;
                }
                .cx-blik-status-additional-info {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    text-align: center;
                    gap: 8px;
                }
                .cx-blik-status-text {
                    line-height: 24px;
                    color: #333333;
                }
                #cx-blik-status-loader {
                    width: 40px;
                    height: 40px;
                    border: 5px solid #F0F8FF;
                    border-bottom-color: #2699f2;
                    border-radius: 50%;
                    animation: rotation 1s linear infinite;
                }
                #cx-blik-status-retry-spinner {
                    width: 20px;
                    height: 20px;
                    margin: auto;
                    border: 2px solid transparent;
                    border-bottom-color: white;
                    border-radius: 50%;
                    animation: rotation 1s linear infinite;
                }
                @keyframes rotation {
                    0% {
                        transform: rotate(0deg);
                    }
                    100% {
                        transform: rotate(360deg);
                    }
                }
                #cx-blik-status-disclaimer {
                    font-size: 12px;
                    line-height: 14px;
                    letter-spacing: 0.004em;
                    color: #808080;
                }
                .cx-blik-status-button {
                    height: 48px;
                    min-width: 146px;
                    border: none;
                    border-radius: 4px;
                    background-color: #0b49db;
                    color: white;
                    font-size: 14px;
                    line-height: 16px;
                    cursor: pointer;
                    white-space: nowrap;
                }
                #js-cx-blik-status-error-message {
                    line-height: 24px;
                    color: #E94149;
                }
                @media only screen and (max-width: 576px) {
                    #js-cx-blik-status-container {
                        padding: 24px 24px 40px;
                        margin: 5px;
                        gap: 28px;
                    }
                    #cx-blik-status-cx-pay-logo {
                        height: 26px;
                    }
                    #cx-blik-status-blik-logo {
                        height: 28px;
                    }
                    .cx-blik-status-icon {
                        width: 30px;
                        height: 30px;
                    }
                    .cx-blik-status-primary-text {
                        font-size: 30px;
                        line-height: 36px;
                    }
                    #cx-blik-status-loader {
                        width: 38px;
                        height: 38px;
                    }
                }
            </style>
            <div id='js-cx-blik-status-container'>
                <div id='cx-blik-status-header'>
                    <img id='cx-blik-status-cx-pay-logo'
                         src='$conotoxia_pay_logo_url'
                         alt='Conotoxia Pay logo'>
                    <img id='cx-blik-status-blik-logo'
                         src='$blik_logo_url'
                         alt='BLIK logo'>
                </div>
                <div id='js-cx-blik-status-success-icon'
                     class='cx-blik-status-icon'
                     style='display: none'>
                    <img class='cx-blik-status-icon'
                         src='$success_icon_url'
                         alt='Success icon'>
                </div>
                <div id='js-cx-blik-status-phone-icon'
                     class='cx-blik-status-icon'>
                    <img class='cx-blik-status-icon'
                         src='$phone_icon_url'
                         alt='Phone icon'>
                </div>
                <div id='js-cx-blik-status-error-icon'
                     class='cx-blik-status-icon'
                     style='display: none'>
                    <img class='cx-blik-status-icon'
                         src='$error_icon_url'
                         alt='Error icon'>
                </div>
                <div class='cx-blik-status-primary-text js-cx-blik-status-waiting-element'>
                    $confirm_blik_payment_in_your_banking_app
                </div>
                <div class='cx-blik-status-text js-cx-blik-status-waiting-element'>
                    $payment_number <b>$payment_id</b>
                </div>
                <div id='cx-blik-status-loader'
                     class='js-cx-blik-status-waiting-element'>
                </div>
                <div id='cx-blik-status-disclaimer'
                     class='js-cx-blik-status-waiting-element'>
                    $payment_of_this_shop_is_processed_by_conotoxia
                </div>
                $success_template
                $timeout_template
                $error_template
                $problem_template
                $code_error_template
                $bank_rejection_template
                $user_rejection_template
                $too_high_amount_template
                $alias_rejection_template
            </div>
            <script>
                jQuery(document).ready($ => {
                    let status = 'waiting';
                    const waitingTime = 120000;
                    const processingStart = Date.now();
                    checkStatus();
                    function checkStatus() {
                        $.ajax({
                            method: 'post',
                            url: '$ajax_url',
                            data: {
                                action: 'cx_check_blik_status',
                                orderId: '$order_id',
                                orderKey: '$order_key'
                            },
                            dataType: 'json',
                            success: (result) => {
                                switch (result.status) {
                                    case 'SUCCESS':
                                        changeStatus('success');
                                        break;
                                    case 'WAITING':
                                        if (Date.now() - processingStart < waitingTime) {
                                            setTimeout(() => checkStatus($), 2000);
                                        } else {
                                            changeStatus('timeout');
                                        }
                                        break;
                                    case 'CODE_ERROR':
                                        changeStatus('code-error');
                                        break;
                                    case 'BANK_REJECTION':
                                        changeStatus('bank-rejection');
                                        break;
                                    case 'USER_REJECTION':
                                        changeStatus('user-rejection');
                                        break;
                                    case 'TOO_HIGH_AMOUNT':
                                        changeStatus('too-high-amount');
                                        break;
                                    case 'ALIAS_REJECTION':
                                        changeStatus('alias-rejection');
                                        break;
                                    case 'TIMEOUT':
                                        changeStatus('timeout');
                                        break;
                                    case 'ERROR':
                                        changeStatus('error');
                                        break;
                                    default:
                                        changeStatus('problem');
                                }
                            },
                            error: () => changeStatus('problem')
                        });
                    }
                    function changeStatus(newStatus) {
                        document.querySelectorAll('.js-cx-blik-status-' + status + '-element')
                            .forEach(element => hideElement(element));
                        status = newStatus;
                        replaceStatusIcon();
                        document.querySelectorAll('.js-cx-blik-status-' + status + '-element')
                            .forEach(element => showElement(element));
                        showActions();
                    }
                    function showElement(element) {
                        element.style.display = null;
                    }
                    function replaceStatusIcon() {
                        let statusIcon;
                        switch (status) {
                            case 'success':
                                statusIcon = document.getElementById('js-cx-blik-status-success-icon');
                                break;
                            case 'problem':
                                break;
                            default:
                                statusIcon = document.getElementById('js-cx-blik-status-error-icon');
                        }
                        if (statusIcon) {
                            const phoneIcon = document.getElementById('js-cx-blik-status-phone-icon');
                            hideElement(phoneIcon);
                            showElement(statusIcon);
                        }
                    }
                    function showActions() {
                        const container = document.getElementById('js-cx-blik-status-container');
                        const button = document.createElement('button');
                        button.classList.add('cx-blik-status-button');
                        switch (status) {
                            case 'code-error':
                            case 'bank-rejection':
                            case 'user-rejection':
                            case 'alias-rejection':
                            case 'timeout':
                            case 'error':
                                button.textContent = '$retry_text';
                                button.onclick = () => {
                                    button.disabled = true;
                                    button.innerHTML = '<div id="cx-blik-status-retry-spinner"></div>';
                                    retryPayment(
                                        (approveUrl) => location.href = approveUrl,
                                        () => {
                                            let errorMessage = document.getElementById('js-cx-blik-status-error-message');
                                            if (errorMessage) {
                                                errorMessage.textContent = '$retry_error_message';
                                            } else {
                                                errorMessage = document.createElement('span');
                                                errorMessage.id = 'js-cx-blik-status-error-message';
                                                errorMessage.textContent = '$retry_error_message';
                                                container.appendChild(errorMessage);
                                            }
                                            button.textContent = '$retry_text';
                                            button.disabled = false;
                                        }
                                    );
                                };
                                const redirect = document.createElement('a');
                                redirect.classList.add('cx-blik-status-text');
                                redirect.href = '$redirect_url';
                                redirect.textContent = '$redirect_text';
                                container.appendChild(button);
                                container.appendChild(redirect);
                                break;
                            default:
                                button.textContent = '$redirect_text';
                                button.onclick = () => location.href = '$redirect_url';
                                container.appendChild(button);
                        }
                    }
                    function hideElement(element) {
                        element.style.display = 'none';
                    }
                    function retryPayment(successCallback, errorCallback) {
                        $.ajax({
                            method: 'post',
                            url: '$ajax_url',
                            data: {
                                action: 'cx_retry_payment',
                                orderId: '$order_id',
                                orderKey: '$order_key'
                            },
                            dataType: 'json',
                            success: (result) => {
                                const approveUrl = result.approveUrl;
                                if (approveUrl) {
                                    successCallback(approveUrl);
                                } else {
                                    errorCallback();
                                }
                            },
                            error: () => errorCallback()
                        });
                    }
                });
            </script>
HTML;
        return self::sanitize_template($template);
    }

    /**
     * @param string $email
     * @return string
     */
    private static function get_success(string $email): string
    {
        $thank_you_for_your_payment = esc_html(__('Thank you for your payment', CONOTOXIA_PAY));
        $we_sent_your_payment_confirmation_by_email_to = esc_html(
            __('We sent your payment confirmation by email to', CONOTOXIA_PAY)
        );
        return <<<HTML
            <div class='cx-blik-status-primary-text js-cx-blik-status-success-element' style='display: none'
                 style='display: none'>
                $thank_you_for_your_payment
            </div>
            <div class='cx-blik-status-text js-cx-blik-status-success-element'
                 style='display: none'>$we_sent_your_payment_confirmation_by_email_to <b>$email</b>
            </div>
HTML;
    }

    /**
     * @param string $payment_id
     * @return string
     */
    private static function get_timeout(string $payment_id): string
    {
        $payment_failed = esc_html(__('Payment failed', CONOTOXIA_PAY));
        $reason = esc_html(__('Reason:', CONOTOXIA_PAY));
        $payment_confirmation_time_expired = esc_html(__('Payment confirmation time expired.', CONOTOXIA_PAY));
        $payment_number = esc_html(__('Payment number:', CONOTOXIA_PAY));
        $return_to_the_shop_to_renew_your_payment = esc_html(
            __('Return to the shop to renew your payment.', CONOTOXIA_PAY)
        );
        $if_you_experience_any_further_problems_please_contact_the_shop_support = esc_html(
            __('If you experience any further problems, please contact the shop support.', CONOTOXIA_PAY)
        );
        return <<<HTML
            <div class='cx-blik-status-primary-text js-cx-blik-status-timeout-element'
                 style='display: none'>
                $payment_failed
            </div>
            <div class='cx-blik-status-text js-cx-blik-status-timeout-element'
                 style='display: none'>
                <div class='cx-blik-status-additional-info'>
                    <div>
                        $reason <b>$payment_confirmation_time_expired</b>
                    </div>
                    <div>
                        $payment_number <b>$payment_id</b>
                    </div>
                </div>
            </div>
            <div class='cx-blik-status-text js-cx-blik-status-timeout-element'
                 style='display: none'>
                <div>
                    $return_to_the_shop_to_renew_your_payment
                </div>
                <div>
                    $if_you_experience_any_further_problems_please_contact_the_shop_support
                </div>
            </div>
HTML;
    }

    /**
     * @param string $payment_id
     * @return string
     */
    private static function get_error(string $payment_id): string
    {
        $primary_text = esc_html(__('Payment failed', CONOTOXIA_PAY));
        $text = esc_html(__('Please contact the shop to determine the reason.', CONOTOXIA_PAY));
        return self::get_default('error', $primary_text, $payment_id, $text);
    }

    /**
     * @param string $payment_id
     * @param string $email
     * @return string
     */
    private static function get_problem(string $payment_id, string $email): string
    {
        $primary_text = esc_html(__('Confirm your BLIK payment in your banking app', CONOTOXIA_PAY));
        $text = esc_html(
            __('A notification about your payment status will be sent to your email address:', CONOTOXIA_PAY)
        );
        $text = $text . ' <b>' . $email . '</b>';
        return self::get_default('problem', $primary_text, $payment_id, $text);
    }

    /**
     * @param string $payment_id
     * @return string
     */
    private static function get_code_error(string $payment_id): string
    {
        $primary_text = esc_html(__('Payment failed', CONOTOXIA_PAY));
        $text = esc_html(__('Incorrect BLIK code was entered.', CONOTOXIA_PAY));
        return self::get_default('code-error', $primary_text, $payment_id, $text);
    }

    /**
     * @param string $payment_id
     * @return string
     */
    private static function get_bank_rejection(string $payment_id): string
    {
        $primary_text = esc_html(__('Payment failed', CONOTOXIA_PAY));
        $text = esc_html(__('Check the reason in the banking app and try again.', CONOTOXIA_PAY));
        return self::get_default('bank-rejection', $primary_text, $payment_id, $text);
    }

    /**
     * @param string $payment_id
     * @return string
     */
    private static function get_user_rejection(string $payment_id): string
    {
        $primary_text = esc_html(__('Payment rejected in a banking app', CONOTOXIA_PAY));
        $text = esc_html(__('Try again.', CONOTOXIA_PAY));
        return self::get_default('user-rejection', $primary_text, $payment_id, $text);
    }

    /**
     * @param string $payment_id
     * @return string
     */
    private static function get_too_high_amount(string $payment_id): string
    {
        $primary_text = esc_html(__('Payment failed', CONOTOXIA_PAY));
        $text = esc_html(__('Payment amount too high.', CONOTOXIA_PAY));
        return static::get_default('too-high-amount', $primary_text, $payment_id, $text);
    }

    /**
     * @param string $payment_id
     * @return string
     */
    private static function get_alias_rejection(string $payment_id): string
    {
        $primary_text = esc_html(__('Payment failed', CONOTOXIA_PAY));
        $text = esc_html(__('Payment requires BLIK code.', CONOTOXIA_PAY));
        return static::get_default('alias-rejection', $primary_text, $payment_id, $text);
    }

    /**
     * @param string $status
     * @param string $primary_text
     * @param string $payment_id
     * @param string $text
     * @return string
     */
    private static function get_default(string $status, string $primary_text, string $payment_id, string $text): string
    {
        $payment_number = esc_html(__('Payment number:', CONOTOXIA_PAY));
        return <<<HTML
            <div class='cx-blik-status-primary-text js-cx-blik-status-$status-element'
                 style='display: none'>
                $primary_text
            </div>
            <div class='cx-blik-status-text js-cx-blik-status-$status-element'
                 style='display: none'>
                $payment_number <b>$payment_id</b>
            </div>
            <div class='cx-blik-status-text js-cx-blik-status-$status-element'
                 style='display: none'>
                $text
            </div>
HTML;
    }
}
