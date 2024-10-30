<?php

declare(strict_types=1);

namespace CKPL\Pay\Definition\BlikProfile;

class BlikProfile implements BlikProfileInterface
{
    /**
     * @var string
     */
    protected $pointOfSaleId;

    /**
     * @var string
     */
    protected $customerId;

    /**
     * @param string $pointOfSaleId
     * @return void
     */
    public function setPointOfSaleId(string $pointOfSaleId)
    {
        $this->pointOfSaleId = $pointOfSaleId;
    }

    /**
     * @return string|null
     */
    public function getPointOfSaleId(): ?string
    {
        return $this->pointOfSaleId;
    }

    /**
     * @param string $customerId
     * @return void
     */
    public function setCustomerId(string $customerId)
    {
        $this->customerId = $customerId;
    }

    /**
     * @return string|null
     */
    public function getCustomerId(): ?string
    {
        return $this->customerId;
    }
}