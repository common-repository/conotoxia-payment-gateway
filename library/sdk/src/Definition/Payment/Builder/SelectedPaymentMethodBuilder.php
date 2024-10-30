<?php

namespace CKPL\Pay\Definition\Payment\Builder;

use CKPL\Pay\Definition\SelectedPaymentMethod\SelectedPaymentMethod;
use CKPL\Pay\Definition\SelectedPaymentMethod\SelectedPaymentMethodInterface;

/**
 * @package CKPL\Pay\Definition\Payment\Builder
 */
class SelectedPaymentMethodBuilder implements SelectedPaymentMethodBuilderInterface
{
    /**
     * @var SelectedPaymentMethodInterface
     */
    protected $selectedPaymentMethod;

    public function __construct()
    {
        $this->selectedPaymentMethod = new SelectedPaymentMethod();
    }

    /**
     * @param string|null $type
     * @return SelectedPaymentMethodBuilderInterface
     */
    public function setType(?string $type): SelectedPaymentMethodBuilderInterface
    {
        $this->selectedPaymentMethod->setType($type);
        return $this;
    }

    /**
     * @param string|null $issuer
     * @return SelectedPaymentMethodBuilderInterface
     */
    public function setIssuer(?string $issuer): SelectedPaymentMethodBuilderInterface
    {
        $this->selectedPaymentMethod->setIssuer($issuer);
        return $this;
    }

    /**
     * @return SelectedPaymentMethodInterface
     */
    public function getSelectedPaymentMethod(): SelectedPaymentMethodInterface
    {
        return $this->selectedPaymentMethod;
    }
}
