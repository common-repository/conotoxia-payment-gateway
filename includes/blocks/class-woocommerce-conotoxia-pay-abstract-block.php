<?php

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

abstract class AbstractBlock extends AbstractPaymentMethodType
{
    protected $gateway;
    protected $name;
    protected $script_name;

    public function initialize()
    {
        $this->settings = get_option("woocommerce_{$this->name}_settings", []);
        $gateways = WC()->payment_gateways->payment_gateways();
        $this->gateway = $gateways[$this->name];
    }

    public function is_active(): bool
    {
        return $this->gateway->is_available();
    }

    public function get_payment_method_script_handles(): array
    {
        $script_handler = $this->name . '_blocks_integration';
        wp_register_script(
            $script_handler,
            plugins_url('scripts/blocks/' . $this->script_name . '.js', dirname(__DIR__)),
            array(),
            null,
            true
        );
        return [$script_handler];
    }

    public function translate_text($key): string
    {
        return __($key, CONOTOXIA_PAY);
    }
}