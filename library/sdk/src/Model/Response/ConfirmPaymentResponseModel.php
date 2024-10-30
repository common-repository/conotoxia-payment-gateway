<?php

namespace CKPL\Pay\Model\Response;

use CKPL\Pay\Endpoint\ConfirmPaymentEndpoint;
use CKPL\Pay\Model\ResponseModelInterface;

/**
 * Class PaymentConfirmResponseModel.
 *
 * @package CKPL\Pay\Model\Response
 */
class ConfirmPaymentResponseModel implements ResponseModelInterface
{
    /**
     * @var string|null
     */
    protected $paymentStatus;

    /**
     * @var string|null
     */
    protected $reason;

    /**
     * @return string|null
     */
    public function getPaymentStatus(): ?string
    {
        return $this->paymentStatus;
    }

    /**
     * @param string $paymentStatus
     * @return ConfirmPaymentResponseModel
     */
    public function setPaymentStatus(string $paymentStatus): ConfirmPaymentResponseModel
    {
        $this->paymentStatus = $paymentStatus;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getReason(): ?string
    {
        return $this->reason;
    }

    /**
     * @param string|null $reason
     * @return ConfirmPaymentResponseModel
     */
    public function setReason(?string $reason): ConfirmPaymentResponseModel
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return ConfirmPaymentEndpoint::class;
    }
}
