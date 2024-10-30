<?php

declare(strict_types=1);

namespace CKPL\Pay\Definition\SelectedPaymentMethod;

/**
 * @package CKPL\Pay\Definition\SelectedPaymentMethod
 */
interface SelectedPaymentMethodInterface
{
    /**
     * @return string|null
     */
    public function getType(): ?string;

    /**
     * @return string|null
     */
    public function getIssuer(): ?string;
}
