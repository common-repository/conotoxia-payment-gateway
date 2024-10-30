<?php

declare(strict_types=1);

namespace CKPL\Pay\Model\Request;

use CKPL\Pay\Endpoint\GetAvailablePaymentMethodsEndpoint;
use CKPL\Pay\Model\RequestModelInterface;

class GetAvailablePaymentMethodsRequestModel implements RequestModelInterface
{

    protected $currency;
    protected $pointOfSaleId;

    /**
     * @param string $currency
     *
     * @return GetAvailablePaymentMethodsRequestModel
     */
    public function setCurrency(string $currency): GetAvailablePaymentMethodsRequestModel
    {
        $this->currency = $currency;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCurrency(): ?string
    {
        return $this->currency;
    }

    /**
     * @param string $pointOfSaleId
     *
     * @return GetAvailablePaymentMethodsRequestModel
     */
    public function setPointOfSaleId(string $pointOfSaleId): GetAvailablePaymentMethodsRequestModel
    {
        $this->pointOfSaleId = $pointOfSaleId;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getPointOfSaleId(): ?string
    {
        return $this->pointOfSaleId;
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return GetAvailablePaymentMethodsEndpoint::class;
    }

    /**
     * @return array
     */
    public function raw(): array
    {
        $result = [];

        if (null !== $this->currency) {
            $result['currency'] = $this->currency;
        }

        if (null !== $this->pointOfSaleId) {
            $result['pointOfSaleId'] = $this->pointOfSaleId;
        }

        return $result;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return RequestModelInterface::FORM;
    }
}