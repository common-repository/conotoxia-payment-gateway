<?php

final class Conotoxia_Pay_Gateway_Block extends AbstractBlock
{
    protected $name = Identifier::CONOTOXIA_PAY;
    protected $script_name = 'gateway_block_checkout';

    public function get_payment_method_data(): array {
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'supports' => $this->gateway->supports,
            'icon' => $this->gateway->icon,
            'icons' => $this->gateway->get_payment_methods_icons()
        ];
    }
}