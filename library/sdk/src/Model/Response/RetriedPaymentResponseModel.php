<?php

declare(strict_types=1);

namespace CKPL\Pay\Model\Response;

use CKPL\Pay\Endpoint\RetryPaymentEndpoint;
use CKPL\Pay\Model\ResponseModelInterface;

class RetriedPaymentResponseModel implements ResponseModelInterface
{
    /**
     * @var string|null
     */
    protected $paymentId;

    /**
     * @var string|null
     */
    protected $approveUrl;

    /**
     * @var string|null
     */
    protected $token;

    /**
     * @return string|null
     */
    public function getPaymentId(): ?string
    {
        return $this->paymentId;
    }

    /**
     * @param string|null $paymentId
     *
     * @return ResponseModelInterface
     */
    public function setPaymentId(string $paymentId): ResponseModelInterface
    {
        $this->paymentId = $paymentId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getApproveUrl(): ?string
    {
        return $this->approveUrl;
    }

    /**
     * @param string|null $approveUrl
     *
     * @return ResponseModelInterface
     */
    public function setApproveUrl(string $approveUrl): ResponseModelInterface
    {
        $this->approveUrl = $approveUrl;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getToken(): ?string
    {
        return $this->token;
    }

    /**
     * @param string|null $token
     *
     * @return ResponseModelInterface
     */
    public function setToken(string $token): ResponseModelInterface
    {
        $this->token = $token;

        return $this;
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return RetryPaymentEndpoint::class;
    }
}
