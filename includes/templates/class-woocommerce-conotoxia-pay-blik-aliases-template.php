<?php

use CKPL\Pay\Model\Response\BlikAliasResponseModel;

class WC_Gateway_Conotoxia_Pay_Blik_Aliases_Template extends WC_Gateway_Conotoxia_Pay_Template
{
    /**
     * @param ArrayIterator $aliases
     * @param BlikAliasResponseModel|null $default_alias
     * @return string
     */
    public static function get(ArrayIterator $aliases, ?BlikAliasResponseModel $default_alias = null): string
    {
        if (count($aliases) < 1) {
            return '';
        }
        $default_alias = self::resolve_default_alias($aliases, $default_alias);
        $from_app = esc_html(__('from app', CONOTOXIA_PAY));
        $change = esc_html(__('Change', CONOTOXIA_PAY));
        $default_alias_key = esc_html($default_alias->getAliasKey());
        $default_alias_name = esc_html($default_alias->getAliasName());
        $by_paying_you_accept_the = esc_html(__('By paying you accept the', CONOTOXIA_PAY));
        $single_payment_transaction_terms_and_conditions_url = esc_url(
            __(
                'https://conotoxia.com/files/regulamin/Single_payment_transaction_terms_and_conditions.pdf',
                CONOTOXIA_PAY
            )
        );
        $single_payment_transaction_terms_and_conditions = esc_html(
            __('Single Payment Transaction Terms and Conditions.', CONOTOXIA_PAY)
        );
        $template = <<<HTML
            <style>
            #js-cx-default-blik-alias {
                font-size: 16px;
            }
            #js-cx-change-blik-alias {
                cursor: pointer;
                text-decoration: underline;
                font-size: 16px;
            }
            #js-cx-blik-aliases {
                font-size: 16px;
            }
            #cx-blik-one-click-terms-and-conditions {
                margin-top: 1rem;
                font-size: 16px;
            }
            </style>
            <div id='js-cx-default-blik-alias'>
                <input id='cx-blik-alias-0' name='cx-blik-alias' value='$default_alias_key' type='hidden'>
                <label for="cx-blik-alias-0">$from_app $default_alias_name</label>
HTML;
        if (count($aliases) > 1) {
            $aliases_template = '';
            $alias_index = 1;
            foreach ($aliases as $alias) {
                $aliases_template .= self::get_alias(
                    $alias_index,
                    $alias->getAliasKey(),
                    $alias->getAliasName(),
                    $alias->getAliasKey() === $default_alias->getAliasKey()
                );
                $alias_index++;
            }
            $template .= <<<HTML
                    <br/>
                    <span id='js-cx-change-blik-alias'>$change</span>
                </div>
                <div id='js-cx-blik-aliases' style='display: none'>
                    $aliases_template
                </div>
                <script>
                    jQuery(document).ready($ => {
                        $('#js-cx-change-blik-alias').click(function () {
                            $('#js-cx-default-blik-alias').remove();
                            $('#js-cx-blik-aliases').show();
                        });
                    });
                </script>
HTML;
        } else {
            $template .= '</div>';
        }
        $template .= <<<HTML
            <div id='cx-blik-one-click-terms-and-conditions'>
                $by_paying_you_accept_the&nbsp;
                <a href='$single_payment_transaction_terms_and_conditions_url'
                   target='_blank'
                   rel='noopener noreferrer'>
                    $single_payment_transaction_terms_and_conditions
                </a>
            </div>
            <input id='cx-blik-one-click-user-screen-resolution' name='cx-user-screen-resolution' type='hidden'>
            <input id='cx-blik-one-click-user-agent' name='cx-user-agent' type='hidden'>
            <script>
                jQuery(document).ready($ => {
                    $('#cx-blik-one-click-user-screen-resolution').val(screen.width + 'x' + screen.height);
                    $('#cx-blik-one-click-user-agent').val(navigator.userAgent);
                });
            </script>
HTML;
        return self::sanitize_template($template);
    }

    /**
     * @param int $alias_index
     * @param string $alias_key
     * @param string $alias_name
     * @param bool $checked
     * @return string
     */
    private static function get_alias(int $alias_index, string $alias_key, string $alias_name, bool $checked): string
    {
        $alias_index = esc_html($alias_index);
        $alias_key = esc_html($alias_key);
        $alias_name = esc_html($alias_name);
        $from_app = esc_html(__('from app', CONOTOXIA_PAY));
        $checked = $checked ? 'checked' : '';
        return <<<HTML
            <input type='radio' id='cx-blik-alias-$alias_index' name='cx-blik-alias' value='$alias_key' $checked>
            <label for='cx-blik-alias-$alias_index'>$from_app $alias_name</label><br/>
HTML;
    }

    private static function resolve_default_alias(
        ArrayIterator           $aliases,
        ?BlikAliasResponseModel $default_alias = null
    ): BlikAliasResponseModel
    {
        if ($default_alias === null) {
            return $aliases[0];
        }
        foreach ($aliases as $alias) {
            if ($alias->getAliasKey() === $default_alias->getAliasKey()) {
                return $alias;
            }
        }
        return $aliases[0];
    }
}
