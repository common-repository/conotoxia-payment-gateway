<?php

namespace CKPL\Pay\Definition\Payment\Status;

class Reason
{
    const ER_WRONG_TICKET = 'ER_WRONG_TICKET';
    const ER_TIC_EXPIRED = 'ER_TIC_EXPIRED';
    const ER_TIC_STS = 'ER_TIC_STS';
    const ER_TIC_USED = 'ER_TIC_USED';
    const INSUFFICIENT_FUNDS = 'INSUFFICIENT_FUNDS';
    const LIMIT_EXCEEDED = 'LIMIT_EXCEEDED';
    const ER_BAD_PIN = 'ER_BAD_PIN';
    const USER_DECLINED = 'USER_DECLINED';
    const USER_TIMEOUT = 'USER_TIMEOUT';
    const TIMEOUT = 'TIMEOUT';
    const AM_TIMEOUT = 'AM_TIMEOUT';
    const ER_DATAAMT_HUGE = 'ER_DATAAMT_HUGE';
    const ALIAS_DECLINED = 'ALIAS_DECLINED';
    const ALIAS_NOT_FOUND = 'ALIAS_NOT_FOUND';

    /**
     * @param string|null $reason
     * @return bool
     */
    public static function isCodeError(?string $reason): bool
    {
        if (empty($reason)) {
            return false;
        }
        return in_array($reason, [self::ER_WRONG_TICKET, self::ER_TIC_EXPIRED, self::ER_TIC_STS, self::ER_TIC_USED]);
    }

    /**
     * @param string|null $reason
     * @return bool
     */
    public static function isBankRejection(?string $reason): bool
    {
        if (empty($reason)) {
            return false;
        }
        return in_array($reason, [self::INSUFFICIENT_FUNDS, self::LIMIT_EXCEEDED, self::ER_BAD_PIN]);
    }

    /**
     * @param string|null $reason
     * @return bool
     */
    public static function isUserRejection(?string $reason): bool
    {
        if (empty($reason)) {
            return false;
        }
        return $reason === self::USER_DECLINED;
    }

    /**
     * @param string|null $reason
     * @return bool
     */
    public static function isTimeout(?string $reason): bool
    {
        if (empty($reason)) {
            return false;
        }
        return in_array($reason, [self::USER_TIMEOUT, self::TIMEOUT, self::AM_TIMEOUT]);
    }

    /**
     * @param string|null $reason
     * @return bool
     */
    public static function isTooHighAmount(?string $reason): bool
    {
        if (empty($reason)) {
            return false;
        }
        return $reason === self::ER_DATAAMT_HUGE;
    }

    /**
     * @param string|null $reason
     * @return bool
     */
    public static function isAliasRejection(?string $reason): bool
    {
        if (empty($reason)) {
            return false;
        }
        return in_array($reason, [self::ALIAS_DECLINED, self::ALIAS_NOT_FOUND]);
    }
}
