<?php

namespace CKPL\Pay\Definition\Payment\Builder;

use CKPL\Pay\Definition\SelectedPaymentMethod\SelectedPaymentMethodInterface;

/**
 * @package CKPL\Pay\Definition\Payment\Builder
 */
interface SelectedPaymentMethodBuilderInterface
{
    /**
     * @param string|null $type
     * @return SelectedPaymentMethodBuilderInterface
     */
    public function setType(?string $type): SelectedPaymentMethodBuilderInterface;

    /**
     * @param string|null $issuer
     * @return SelectedPaymentMethodBuilderInterface
     */
    public function setIssuer(?string $issuer): SelectedPaymentMethodBuilderInterface;

    /**
     * @return SelectedPaymentMethodInterface
     */
    public function getSelectedPaymentMethod(): SelectedPaymentMethodInterface;
}
