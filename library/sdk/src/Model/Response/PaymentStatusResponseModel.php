<?php

declare(strict_types=1);

namespace CKPL\Pay\Model\Response;

use CKPL\Pay\Endpoint\GetPaymentStatusEndpoint;
use CKPL\Pay\Model\ResponseModelInterface;

/**
 * Class RefundResponseModel.
 *
 * @package CKPL\Pay\Model\Response
 */
class PaymentStatusResponseModel implements ResponseModelInterface
{
    /**
     * @var string|null
     */
    protected $status;

    /**
     * @var string|null
     */
    protected $paymentId;

    /**
     * @var string|null
     */
    protected $reason;

    /**
     * @return string|null
     */
    public function getStatus(): ?string
    {
        return $this->status;
    }

    /**
     * @param string $status
     *
     * @return PaymentStatusResponseModel
     */
    public function setStatus(string $status): PaymentStatusResponseModel
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPaymentId(): ?string
    {
        return $this->paymentId;
    }

    /**
     * @param string $paymentId
     * @return PaymentStatusResponseModel
     */
    public function setPaymentId(string $paymentId): PaymentStatusResponseModel
    {
        $this->paymentId = $paymentId;

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
     * @return PaymentStatusResponseModel
     */
    public function setReason(?string $reason): PaymentStatusResponseModel
    {
        $this->reason = $reason;

        return $this;
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return GetPaymentStatusEndpoint::class;
    }
}