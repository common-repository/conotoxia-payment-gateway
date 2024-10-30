<?php

final class Conotoxia_Pay_Blik_Block extends AbstractBlock
{
    protected $name = Identifier::CONOTOXIA_PAY_BLIK;
    protected $script_name = 'blik_block_checkout';

    public function get_payment_method_data(): array {
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'supports' => $this->gateway->supports,
            'icon' => $this->gateway->icon,
            'emptyBlikCode' => $this->translate_text('Enter the BLIK code.'),
            'invalidBlikCode' => $this->translate_text('The BLIK code should have 6 digits.'),
            'enterCode' => $this->translate_text('Enter the BLIK code:'),
            'byPayingYouAccept' => $this->translate_text('By paying you accept the'),
            'blikCodeInstruction' => $this->translate_text('You can find the BLIK code in your banking app.'),
            'termsAndConditionsUrl' => $this->translate_text('https://conotoxia.com/files/regulamin/Single_payment_transaction_terms_and_conditions.pdf'),
            'termsAndConditionsText' => $this->translate_text('Single Payment Transaction Terms and Conditions.'),
        ];
    }
}