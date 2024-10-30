<?php

declare(strict_types=1);

namespace CKPL\Pay\Model\Request;

use CKPL\Pay\Endpoint\MakePaymentEndpoint;
use CKPL\Pay\Model\RequestModelInterface;

/**
 * @package CKPL\Pay\Model\Request
 */
class SelectedPaymentMethodRequestModel implements RequestModelInterface
{
    /**
     * @var string|null
     */
    protected $type;

    /**
     * @var string|null
     */
    protected $issuer;

    /**
     * @param string|null $type
     * @return $this
     */
    public function setType(?string $type): SelectedPaymentMethodRequestModel
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param string|null $issuer
     * @return $this
     */
    public function setIssuer(?string $issuer): SelectedPaymentMethodRequestModel
    {
        $this->issuer = $issuer;
        return $this;
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return MakePaymentEndpoint::class;
    }

    /**
     * @return array
     */
    public function raw(): array
    {
        return [
            'type' => $this->type,
            'issuer' => $this->issuer,
        ];
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return RequestModelInterface::JSON_OBJECT;
    }
}
