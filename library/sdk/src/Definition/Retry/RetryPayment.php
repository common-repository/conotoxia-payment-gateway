<?php

declare(strict_types=1);

namespace CKPL\Pay\Definition\Retry;

class RetryPayment implements RetryPaymentInterface
{
    /**
     * @var string
     */
    protected $paymentId;

    public function __construct($paymentId)
    {
        $this->paymentId = $paymentId;
    }

    /**
     * @return string
     */
    public function getPaymentId(): string
    {
        return $this->paymentId;
    }
}
