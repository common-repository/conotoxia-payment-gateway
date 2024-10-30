<?php

namespace CKPL\Pay\Definition\BlikProfile\Builder;

interface BlikProfileBuilderInterface
{
    /**
     * @param string $pointOfSaleId
     * @return BlikProfileBuilder
     */
    public function setPointOfSaleId(string $pointOfSaleId): BlikProfileBuilder;

    /**
     * @param string $customerId
     * @return BlikProfileBuilder
     */
    public function setCustomerId(string $customerId): BlikProfileBuilder;
}