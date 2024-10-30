<?php

class WC_Gateway_Conotoxia_Pay_Blik_Without_Code_Notice_Template extends WC_Gateway_Conotoxia_Pay_Template
{
    /**
     * @return void
     */
    public static function get(string $alias_name): string
    {
        $title = esc_html(__('You no longer need to enter the BLIK code', CONOTOXIA_PAY));
        $message = esc_html(
            sprintf(
                __('You have connected your account %s to this shop during one of your previous payments. You can deactivate this connection in your banking app at any time. We care about your security - purchases still need to be confirmed in the banking app.', CONOTOXIA_PAY),
                $alias_name
            )
        );
        $button = esc_html(__('I understand', CONOTOXIA_PAY));
        $blik_one_click_element_id = esc_html('payment_method_' . Identifier::CONOTOXIA_PAY_BLIK_ONE_CLICK);
        $blik_one_click_id = esc_html(Identifier::CONOTOXIA_PAY_BLIK_ONE_CLICK);
        $template = <<<HTML
            <style>
                #js-cx-blik-without-code-notice-background {
                    position: fixed;
                    left: 0;
                    top: 0;
                    z-index: 2147483647;
                    width: 100%;
                    height: 100%;
                    overflow: auto;
                    background-color: rgba(0, 0, 0, 0.5);
                }
                #cx-blik-without-code-notice-container {
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    text-align: center;
                    padding: 40px 40px 56px;
                    margin: 40px auto;
                    max-width: 600px;
                    gap: 32px;
                    background: white;
                    border-radius: 24px;
                }
                #cx-blik-without-code-notice-title {
                    font-weight: 800;
                    font-size: 32px;
                    line-height: 38px;
                    color: #333333;
                }
                #cx-blik-without-code-notice-message {
                    line-height: 24px;
                    color: #333333;
                }
                #js-cx-blik-without-code-notice-button {
                    height: 48px;
                    min-width: 136px;
                    border: none;
                    border-radius: 4px;
                    background-color: #0b49db;
                    color: white;
                    font-size: 14px;
                    line-height: 16px;
                    cursor: pointer;
                    white-space: nowrap;
                }
                @media only screen and (max-width: 576px) {
                    #cx-blik-without-code-notice-container {
                        padding: 24px 24px 40px;
                        margin: 5px;
                        gap: 28px;
                    }
                    #cx-blik-without-code-notice-title {
                        font-size: 30px;
                        line-height: 36px;
                    }
                }
            </style>
            <div id='js-cx-blik-without-code-notice-background' style='display: none;'>
                <div id='cx-blik-without-code-notice-container'>
                    <div id='cx-blik-without-code-notice-title'>
                        $title
                    </div>
                    <div id='cx-blik-without-code-notice-message'>
                        $message
                    </div>
                    <div>
                        <button id='js-cx-blik-without-code-notice-button'>$button</button>
                    </div>
                </div>
            </div>
            <script>
                jQuery(document).ready($ => {
                    let acceptance = isAcceptance();
                    $('#$blik_one_click_element_id').change(function() {
                        showNoticeIfNeeded();
                    });
                    const selectedPaymentMethod = $('input[name=payment_method]:checked').val();
                    if (selectedPaymentMethod === '$blik_one_click_id') {
                        showNoticeIfNeeded();
                    }
                    $('#js-cx-blik-without-code-notice-button').click(function() {
                        setAcceptance();
                        hideNotice();
                    });
                    function showNoticeIfNeeded() {
                        const notice = $('#js-cx-blik-without-code-notice-background');
                        if (!acceptance && !notice.is(':visible')) {
                            notice.show();
                        }
                    }
                    function hideNotice() {
                        const notice = $('#js-cx-blik-without-code-notice-background');
                        notice.hide();
                    }
                    function isAcceptance() {
                        const prefix = "cx_blik_one_click_acceptance=";
                        const cookies = decodeURIComponent(document.cookie).split(';');
                        for (let cookie of cookies) {
                            cookie = cookie.trim();
                            if (cookie.startsWith(prefix) && cookie.substring(prefix.length) === 'true') {
                                return true;
                            }
                        }
                        return false;
                    }
                    function setAcceptance() {
                        const expires = new Date();
                        expires.setFullYear(expires.getFullYear() + 1);
                        document.cookie = "cx_blik_one_click_acceptance=true;expires=" + expires.toUTCString();
                        acceptance = true;
                    }
                });
            </script>
HTML;
        return self::sanitize_template($template);
    }
}
