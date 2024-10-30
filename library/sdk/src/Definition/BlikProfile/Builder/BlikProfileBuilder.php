<?php

namespace CKPL\Pay\Definition\BlikProfile\Builder;

use CKPL\Pay\Definition\BlikProfile\BlikProfile;
use CKPL\Pay\Definition\BlikProfile\BlikProfileInterface;
use CKPL\Pay\Exception\Definition\BlikProfileException;

class BlikProfileBuilder implements BlikProfileBuilderInterface
{
    /**
     * @var BlikProfile
     */
    protected $blikProfile;

    /**
     * BlikProfileBuilder constructor.
     */
    public function __construct()
    {
        $this->initializeBlikProfile();
    }

    /**
     * @param string $customerId
     * @return $this
     */
    public function setCustomerId(string $customerId): BlikProfileBuilder
    {
        $this->blikProfile->setCustomerId($customerId);

        return $this;
    }

    /**
     * @param string $pointOfSaleId
     * @return $this
     */
    public function setPointOfSaleId(string $pointOfSaleId): BlikProfileBuilder
    {
        $this->blikProfile->setPointOfSaleId($pointOfSaleId);

        return $this;
    }

    /**
     * @throws BlikProfileException
     */
    public function getBlikProfile(): BlikProfileInterface
    {
        if (null === $this->blikProfile->getPointOfSaleId()) {
            throw new BlikProfileException('Missing point of sale identifier in blik profile request.');
        }

        if (null === $this->blikProfile->getCustomerId()) {
            throw new BlikProfileException('Missing customer identifier in blik profile request.');
        }

        return $this->blikProfile;
    }

    /**
     * @return void
     */
    protected function initializeBlikProfile(): void
    {
        $this->blikProfile = new BlikProfile();
    }
}