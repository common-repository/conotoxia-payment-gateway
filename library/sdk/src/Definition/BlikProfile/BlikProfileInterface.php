<?php

namespace CKPL\Pay\Definition\BlikProfile;

interface BlikProfileInterface
{
    /**
     * @return string
     */
    public function getPointOfSaleId(): ?string;

    /**
     * @return string
     */
    public function getCustomerId(): ?string;


}