<?php

final class Conotoxia_Pay_Blik_One_Click_Block extends AbstractBlock
{
    protected $name = Identifier::CONOTOXIA_PAY_BLIK_ONE_CLICK;
    protected $script_name = 'blik_one_click_block_checkout';

    public function get_payment_method_data(): array
    {
        return [
            'title' => $this->gateway->title,
            'description' => $this->gateway->description,
            'supports' => $this->gateway->supports,
            'icon' => $this->gateway->icon,
            'aliases' => $this->convert_aliases_to_array($this->fetch_aliases()),
            'defaultAlias' => $this->get_default_alias(),
            'noticeTitle' => $this->translate_text('You no longer need to enter the BLIK code'),
            'noticeMessage' => $this->translate_text('You have connected your account %s to this shop during one of your previous payments. You can deactivate this connection in your banking app at any time. We care about your security - purchases still need to be confirmed in the banking app.'),
            'noticeButton' => $this->translate_text('I understand'),
            'fromApp' => $this->translate_text('from app'),
            'change' => $this->translate_text('Change'),
            'byPayingYouAccept' => $this->translate_text('By paying you accept the'),
            'termsAndConditionsUrl' => $this->translate_text('https://conotoxia.com/files/regulamin/Single_payment_transaction_terms_and_conditions.pdf'),
            'termsAndConditionsText' => $this->translate_text('Single Payment Transaction Terms and Conditions.')
        ];
    }

    public function convert_aliases_to_array(ArrayIterator $aliases): array
    {
        $arr = [];
        foreach ($aliases as $alias) {
            $arr[] = [
                'aliasName' => $alias->getAliasName(),
                'aliasKey' => $alias->getAliasKey()
            ];
        }
        return $arr;
    }

    public function fetch_aliases(): ArrayIterator
    {
        if ($this->is_active() && $this->gateway->is_checkout()) {
            $user_id = get_current_user_id();
            if (!$user_id) {
                return new ArrayIterator();
            }
            $aliases = $this->gateway->get_aliases($user_id);
            $this->gateway->save_aliases($user_id, $aliases);
            return $aliases;
        }
        return new ArrayIterator();
    }

    public function get_default_alias(): array
    {
        $user_id = get_current_user_id();
        if (!$user_id) {
            return [];
        }
        $aliases = $this->gateway->get_saved_aliases($user_id);
        if (!empty($aliases)) {
            $default_alias = $this->gateway->get_saved_default_alias($user_id);
            if (!empty($default_alias)) {
                return [
                    'aliasName' => $default_alias->getAliasName(),
                    'aliasKey' => $default_alias->getAliasKey()
                ];
            }
        }
        return [];
    }
}
