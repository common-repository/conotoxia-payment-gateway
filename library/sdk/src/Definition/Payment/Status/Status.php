<?php

namespace CKPL\Pay\Definition\Payment\Status;

class Status
{
    const INITIATED = 'INITIATED';
    const AUTHORIZATION_REQUESTED = 'AUTHORIZATION_REQUESTED';
    const WAITING_FOR_NOTIFICATION = 'WAITING_FOR_NOTIFICATION';
    const CONFIRMED = 'CONFIRMED';

    /**
     * @param string|null $status
     * @return bool
     */
    public static function isWaiting(?string $status): bool
    {
        if (empty($status)) {
            return false;
        }
        return in_array($status, [self::INITIATED, self::AUTHORIZATION_REQUESTED, self::WAITING_FOR_NOTIFICATION]);
    }

    /**
     * @param string|null $status
     * @return bool
     */
    public static function isConfirmed(?string $status): bool
    {
        if (empty($status)) {
            return false;
        }
        return $status === self::CONFIRMED;
    }
}
