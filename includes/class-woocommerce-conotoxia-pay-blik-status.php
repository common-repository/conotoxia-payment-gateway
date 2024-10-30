<?php

class WC_Gateway_Conotoxia_Pay_Blik_Status
{
    /**
     * @return void
     */
    public function initialize(): void
    {
        add_shortcode('cx_blik_status', [$this, 'display_blik_status']);
    }

    /**
     * @return false|string
     */
    public function display_blik_status()
    {
        $order_id = sanitize_text_field($_GET['order_id']) ?? '';
        $order_key = sanitize_text_field($_GET['order_key']) ?? '';
        if (empty($order_id) || empty($order_key)) {
            return false;
        }
        $order = wc_get_order($order_id);
        if (!$order || !in_array($order->get_payment_method(), Identifier::get_blik_ids()) || $order->get_order_key() !== $order_key) {
            return false;
        }
        $gateway = new WC_Gateway_Conotoxia_Pay_Blik();
        ob_start();
        echo WC_Gateway_Conotoxia_Pay_Blik_Status_Template::get($order, $gateway->is_redirect_to_order_enabled());
        return ob_get_clean();
    }
}
