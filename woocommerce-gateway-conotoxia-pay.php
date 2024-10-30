<?php

/**
 * Plugin Name: Conotoxia Payment Gateway
 * Plugin URI: https://conotoxia.com/payments/for-developers
 * Description: Conotoxia Pay payment gateway
 * Version: 1.42.7
 * Author: Conotoxia Sp. z o.o.
 * Author URI: https://conotoxia.com
 * License: GPLv2
 * Text Domain: conotoxia-pay
 * Domain Path: /lang
 *
 * WC requires at least: 4.2
 * WC tested up to: 9.0.2
 */

add_action('plugins_loaded', 'woocommerce_conotoxia_payment_gateway_init', 0);
add_action('wp_loaded', 'woocommerce_conotoxia_payment_gateway_add_custom_posts');
add_action('woocommerce_before_checkout_form', 'woocommerce_conotoxia_payment_gateway_set_default_payment_gateway');
add_action('before_woocommerce_pay', 'woocommerce_conotoxia_payment_gateway_set_default_payment_gateway');
add_action('admin_notices', 'woocommerce_conotoxia_payment_gateway_admin_notice_empty_configuration');
add_action('before_woocommerce_init', function () {
    if (class_exists(\Automattic\WooCommerce\Utilities\FeaturesUtil::class)) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', __FILE__, true);
    }
});

add_action('before_woocommerce_init', 'woocommerce_conotoxia_payment_gateway_declare_cart_checkout_blocks_compatibility');
add_action('woocommerce_blocks_loaded', 'woocommerce_conotoxia_payment_gateway_register_block_support');

register_activation_hook(__FILE__, 'woocommerce_conotoxia_payment_gateway_activate');
register_uninstall_hook(__FILE__, 'woocommerce_conotoxia_payment_gateway_uninstall');

const CONOTOXIA_PAY = 'conotoxia-pay';
const CONOTOXIA_PAY_VERSION = '1.42.7';

function woocommerce_conotoxia_payment_gateway_init()
{
    if (in_array('conotoxiapay/woocommerce-gateway-conotoxia-pay.php', apply_filters('active_plugins', get_option('active_plugins')))) {
        add_action('admin_notices', 'woocommerce_conotoxia_payment_gateway_admin_notice_old_plugin_version');
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');

        //Old plugin deactivation
        deactivate_plugins('conotoxiapay/woocommerce-gateway-conotoxia-pay.php', true);
        //Again activate new plugin
        activate_plugin('conotoxia-payment-gateway/woocommerce-gateway-conotoxia-pay.php', true);
        return;
    }

    if (!class_exists('WC_Payment_Gateway')) {
        require_once(ABSPATH . 'wp-admin/includes/plugin.php');

        add_action('admin_notices', 'woocommerce_conotoxia_payment_gateway_admin_notice_plugin_not_found');

        $plugin = 'conotoxia-payment-gateway/woocommerce-gateway-conotoxia-pay.php';

        if (is_plugin_active($plugin)) {
            deactivate_plugins($plugin);
        }

        unset($_GET['activate']);

        return;
    }

    require_once 'library/sdk/internals/autoload.php';

    load_plugin_textdomain(CONOTOXIA_PAY, false, dirname(plugin_basename(__FILE__)) . '/lang/');

    include_once('includes/templates/class-woocommerce-conotoxia-pay-template-abstract.php');
    include_once('includes/templates/class-woocommerce-conotoxia-pay-blik-aliases-template.php');
    include_once('includes/templates/class-woocommerce-conotoxia-pay-blik-form-template.php');
    include_once('includes/templates/class-woocommerce-conotoxia-pay-blik-status-template.php');
    include_once('includes/templates/class-woocommerce-conotoxia-pay-blik-without-code-notice.php');
    include_once('includes/class-woocommerce-conotoxia-pay-abstract.php');
    include_once('includes/class-woocommerce-conotoxia-pay-storage.php');
    include_once('includes/class-woocommerce-conotoxia-pay.php');
    include_once('includes/class-woocommerce-conotoxia-pay-blik.php');
    include_once('includes/class-woocommerce-conotoxia-pay-blik-one-click.php');
    include_once('includes/class-woocommerce-conotoxia-pay-blik-status.php');
    include_once('includes/class-woocommerce-conotoxia-pay-blik-status-handler.php');
    include_once('includes/class-woocommerce-conotoxia-pay-public-key-generation-handler.php');
    include_once('includes/class-woocommerce-conotoxia-pay-retry-handler.php');
    include_once('includes/class-woocommerce-conotoxia-pay-logger.php');
    include_once('includes/class-woocommerce-conotoxia-pay-identifiers.php');

    add_filter('plugin_row_meta', 'woocommerce_conotoxia_payment_gateway_add_custom_link', 10, 2);

    add_filter('woocommerce_payment_gateways', 'woocommerce_conotoxia_payment_gateway_add_gateway');

    $public_key_generation_handler = new WC_Gateway_Conotoxia_Pay_Public_Key_Generation_Handler();
    $public_key_generation_handler->initialize();
    $blik_status_handler = new WC_Gateway_Conotoxia_Pay_Blik_Status_Handler();
    $blik_status_handler->initialize();
    $blik_status = new WC_Gateway_Conotoxia_Pay_Blik_Status();
    $blik_status->initialize();
    $retry_handler = new WC_Gateway_Conotoxia_Pay_Retry_Handler();
    $retry_handler->initialize();
    $logger = new WC_Gateway_Conotoxia_Pay_Logger();
    $logger->initialize();
}

/**
 * @return void
 */
function woocommerce_conotoxia_payment_gateway_add_custom_posts(): void
{
    $blik_status_page = get_page_by_path('cx_blik_status');
    if (!isset($blik_status_page)) {
        wp_insert_post([
            'post_type' => 'page',
            'post_name' => 'cx_blik_status',
            'post_content' => '<!-- wp:shortcode -->[cx_blik_status]<!-- /wp:shortcode -->',
            'post_status' => 'publish',
        ]);
    }
}

/**
 * @return void
 */
function woocommerce_conotoxia_payment_gateway_set_default_payment_gateway(): void
{
    $available_payment_gateways = WC()->payment_gateways->get_available_payment_gateways();
    foreach (Identifier::get_all_ids() as $id) {
        if (!array_key_exists($id, $available_payment_gateways)) {
            continue;
        }
        $enabled = $available_payment_gateways[$id]->settings['enabled'] ?? 'no';
        $default = $available_payment_gateways[$id]->settings['asDefault'] ?? 'no';
        if ($enabled === 'yes' && $default === 'yes') {
            $default_payment_method_id = $id;
            break;
        }
    }
    if (!isset($default_payment_method_id)) {
        return;
    }
    $script_name = 'cx_default_payment_method';
    wp_enqueue_script(
        $script_name,
        plugins_url('scripts/default_payment_method.js', __FILE__),
        ['jquery']
    );
    wp_localize_script(
        $script_name,
        'defaultPaymentMethod',
        ['id' => $default_payment_method_id]
    );
}

function woocommerce_conotoxia_payment_gateway_add_custom_link($links, $file)
{
    if ('conotoxia-payment-gateway/woocommerce-gateway-conotoxia-pay.php' !== $file) {
        return $links;
    }

    $custom_links = array(
        'documentation' => '<a href="' . esc_url('https://docs.cinkciarz.pl/platnosci/wtyczki/woocommerce/') . '" aria-label="' . esc_attr__('View Conotoxia Pay documentation', CONOTOXIA_PAY) . '">' . esc_html__('Docs', CONOTOXIA_PAY) . '</a>',
    );

    return array_merge($links, $custom_links);
}

function woocommerce_conotoxia_payment_gateway_declare_cart_checkout_blocks_compatibility() {
    if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('cart_checkout_blocks', __FILE__, true);
    }
}

function woocommerce_conotoxia_payment_gateway_register_block_support() {
    if ( ! class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
        return;
    }

    include_once('includes/blocks/class-woocommerce-conotoxia-pay-abstract-block.php');
    include_once('includes/blocks/class-woocommerce-conotoxia-pay-block.php');
    include_once('includes/blocks/class-woocommerce-conotoxia-pay-blik-block.php');
    include_once('includes/blocks/class-woocommerce-conotoxia-pay-blik-one-click-block.php');
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function( Automattic\WooCommerce\Blocks\Payments\PaymentMethodRegistry $payment_method_registry ) {
            $payment_method_registry->register(new Conotoxia_Pay_Gateway_Block);
            $payment_method_registry->register(new Conotoxia_Pay_Blik_Block);
            $payment_method_registry->register(new Conotoxia_Pay_Blik_One_Click_Block);
        }
    );
}

function woocommerce_conotoxia_payment_gateway_add_gateway($methods)
{
    array_push(
        $methods,
        'WC_Gateway_Conotoxia_Pay',
        'WC_Gateway_Conotoxia_Pay_Blik_One_Click',
        'WC_Gateway_Conotoxia_Pay_Blik'
    );
    return $methods;
}

function woocommerce_conotoxia_payment_gateway_fields()
{
    return [
        'conotoxia-pay-version',
        'conotoxia-pay-token',
        'conotoxia-pay-payment_service_public_keys',
        'conotoxia-pay-public_key_id',
        'conotoxia-pay-public_key_checksum',
        'woocommerce_conotoxia_pay_settings',
        'woocommerce_conotoxia_pay_blik_settings',
        'woocommerce_conotoxia_pay_blik_one_click_settings',
    ];
}

function woocommerce_conotoxia_payment_gateway_activate()
{
    $fields = woocommerce_conotoxia_payment_gateway_fields();
    $fields = array_slice($fields, 0, -2);

    foreach ($fields as $field) {
        if (!get_option($field)) {
            $value = '';
            if ($field == 'conotoxia-pay-version') {
                $value = CONOTOXIA_PAY_VERSION;
            }

            delete_option($field);
            add_option($field, $value);
        }
    }
}

function woocommerce_conotoxia_payment_gateway_uninstall()
{
    $fields = woocommerce_conotoxia_payment_gateway_fields();
    foreach ($fields as $field) {
        delete_option($field);
    }

    $blik_status_page = get_page_by_path('cx_blik_status');
    if (isset($blik_status_page)) {
        wp_delete_post($blik_status_page->ID);
    }
}

function woocommerce_conotoxia_payment_gateway_admin_notice($message)
{
    echo '<div class="notice notice-warning is-dismissible">
             <p>' . $message . '</p>
          </div>';
}

function woocommerce_conotoxia_payment_gateway_admin_notice_old_plugin_version()
{
    $messagePartOne = __('We detected that you are using an older version of the plugin. We disabled it and automatically activated the latest one. Future updates will be automatically downloaded from the', CONOTOXIA_PAY);
    $messagePartTwo = __('If you want, you can disable them at any time.', CONOTOXIA_PAY);

    $websiteUrl = 'https://wordpress.org/plugins/conotoxia-payment-gateway/';
    $link = '<a href="' . esc_url($websiteUrl) . '" target="_blank" rel="noopener noreferrer">WordPress marketplace</a>';

    $message = esc_html($messagePartOne) . $link . esc_html($messagePartTwo);

    woocommerce_conotoxia_payment_gateway_admin_notice($message);
}

function woocommerce_conotoxia_payment_gateway_admin_notice_plugin_not_found()
{
    $message = esc_html(__('Before enabling the Conotoxia Pay payment gateway, you must first install and activate the WooCommerce plugin.', CONOTOXIA_PAY));

    woocommerce_conotoxia_payment_gateway_admin_notice($message);
}

function woocommerce_conotoxia_payment_gateway_admin_notice_empty_configuration()
{
    $plugin = 'conotoxia-payment-gateway/woocommerce-gateway-conotoxia-pay.php';

    if (!is_plugin_active($plugin)) {
        return;
    }

    $configuration = new WC_Gateway_Conotoxia_Pay();
    $configurationStatus = $configuration->is_configuration_completed();

    $page = $_GET['page'] ?? '';
    $section = $_GET['section'] ?? '';
    if (!$configurationStatus && $page == 'wc-settings' && $section == 'conotoxia_pay') {
        $message = esc_html(__('To complete the configuration, enter the required data.', CONOTOXIA_PAY));

        woocommerce_conotoxia_payment_gateway_admin_notice($message);
    }
}