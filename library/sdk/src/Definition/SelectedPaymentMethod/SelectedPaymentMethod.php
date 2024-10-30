<?php

declare(strict_types=1);

namespace CKPL\Pay\Definition\SelectedPaymentMethod;

/**
 * @package CKPL\Pay\Definition\SelectedPaymentMethod
 */
class SelectedPaymentMethod implements SelectedPaymentMethodInterface
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
     * @return string|null
     */
    public function getType(): ?string
    {
        return $this->type;
    }

    /**
     * @param string|null $type
     * @return SelectedPaymentMethod
     */
    public function setType(?string $type): SelectedPaymentMethod
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getIssuer(): ?string
    {
        return $this->issuer;
    }

    /**
     * @param string|null $issuer
     * @return SelectedPaymentMethod
     */
    public function setIssuer(?string $issuer): SelectedPaymentMethod
    {
        $this->issuer = $issuer;
        return $this;
    }
}
