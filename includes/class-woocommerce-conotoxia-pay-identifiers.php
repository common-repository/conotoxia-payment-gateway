<?php

class Identifier
{
    /**
     * @type string
     */
    const CONOTOXIA_PAY = 'conotoxia_pay';

    /**
     * @type string
     */
    const CONOTOXIA_PAY_BLIK_ONE_CLICK = 'conotoxia_pay_blik_one_click';

    /**
     * @type string
     */
    const CONOTOXIA_PAY_BLIK = 'conotoxia_pay_blik';

    /**
     * @return string[]
     */
    public static function get_all_ids(): array
    {
        return [self::CONOTOXIA_PAY, self::CONOTOXIA_PAY_BLIK_ONE_CLICK, self::CONOTOXIA_PAY_BLIK];
    }

    /**
     * @return string[]
     */
    public static function get_blik_ids(): array
    {
        return [self::CONOTOXIA_PAY_BLIK_ONE_CLICK, self::CONOTOXIA_PAY_BLIK];
    }
}
