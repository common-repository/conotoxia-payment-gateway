<?php

declare(strict_types=1);

namespace CKPL\Pay\Definition\Retry;

interface RetryPaymentInterface
{
    /**
     * @return string
     */
    public function getPaymentId(): string;
}
