<?php

declare(strict_types=1);

namespace CKPL\Pay\Model\Response;

use CKPL\Pay\Endpoint\GetAvailablePaymentMethodsEndpoint;
use CKPL\Pay\Model\ResponseModelInterface;

class PaymentMethodResponseModel  implements ResponseModelInterface
{

    /**
     * @var string|null
     */
    protected $type;

    /**
     * @var string|null
     */
    protected $status;

    /**
     * @var array|IssuerResponseModel[]
     */
    protected $issuers = [];

    /**
     * @return string|null
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     *
     * @return PaymentMethodResponseModel
     */
    public function setType(string $type): PaymentMethodResponseModel
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getStatus(): string
    {
        return $this->status;
    }

    /**
     * @param string|null $status
     *
     * @return PaymentMethodResponseModel
     */
    public function setStatus(string $status): PaymentMethodResponseModel
    {
        $this->status = $status;

        return $this;
    }

    /**
     * @return array|null
     */
    public function getIssuers(): array
    {
        return $this->issuers;
    }

    /**
     * @param array $issuers
     *
     * @return PaymentMethodResponseModel
     */
    public function setIssuers(array $issuers): PaymentMethodResponseModel
    {
        $this->issuers = $issuers;

        return $this;
    }

    /**
     * @param IssuerResponseModel $issuer
     *
     * @return PaymentMethodResponseModel
     */
    public function addIssuer(IssuerResponseModel $issuer): PaymentMethodResponseModel
    {
        $this->issuers[] = $issuer;

        return $this;
    }


    public function getEndpoint(): string
    {
        return GetAvailablePaymentMethodsEndpoint::class;
    }
}