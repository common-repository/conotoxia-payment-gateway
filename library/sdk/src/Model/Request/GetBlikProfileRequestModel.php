<?php

declare(strict_types=1);

namespace CKPL\Pay\Model\Request;

use CKPL\Pay\Endpoint\GetBlikProfilesEndpoint;
use CKPL\Pay\Model\RequestModelInterface;

class GetBlikProfileRequestModel implements RequestModelInterface
{

    protected $customerId;

    /**
     * @param string $customerId
     * @return RequestModelInterface
     */
    public function setCustomerId(string $customerId): RequestModelInterface
    {
        $this->customerId = $customerId;

        return $this;
    }

    /**
     * @return string
     */
    public function getEndpoint(): string
    {
        return GetBlikProfilesEndpoint::class;
    }

    /**
     * @return array
     */
    public function raw(): array
    {
        return [
            'customerId' => $this->customerId,
        ];
    }

    public function getType(): int
    {
        return static::JSON_OBJECT;
    }
}