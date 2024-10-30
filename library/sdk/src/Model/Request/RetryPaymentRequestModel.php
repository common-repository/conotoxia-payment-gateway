<?php

declare(strict_types=1);

namespace CKPL\Pay\Model\Request;

use CKPL\Pay\Endpoint\RetryPaymentEndpoint;
use CKPL\Pay\Model\RequestModelInterface;

class RetryPaymentRequestModel implements RequestModelInterface
{
    /**
     * @var string|null
     */
    protected $paymentId;

    /**
     * @return string|null
     */
    public function getPaymentId(): ?string
    {
        return $this->paymentId;
    }

    /**
     * @param string $paymentId
     * @return $this
     */
    public function setPaymentId(string $paymentId): RetryPaymentRequestModel
    {
        $this->paymentId = $paymentId;

        return $this;
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return RetryPaymentEndpoint::class;
    }

    /**
     * @return array
     */
    public function raw(): array
    {
        return ['paymentId' => $this->paymentId];
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return RequestModelInterface::JSON_OBJECT;
    }
}
