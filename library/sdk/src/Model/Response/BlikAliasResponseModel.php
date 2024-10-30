<?php

declare(strict_types=1);

namespace CKPL\Pay\Model\Response;

use CKPL\Pay\Endpoint\GetBlikProfilesEndpoint;
use CKPL\Pay\Model\ResponseModelInterface;

class BlikAliasResponseModel implements ResponseModelInterface
{
    /**
     * @var string|null
     */
    protected $aliasName;

    /**
     * @var string|null
     */
    protected $aliasKey;

    /**
     * @param string|null $aliasName
     *
     * @return BlikAliasResponseModel
     */
    public function setAliasName(string $aliasName): BlikAliasResponseModel
    {
        $this->aliasName = $aliasName;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAliasName(): ?string
    {
        return $this->aliasName;
    }

    /**
     * @param string|null $aliasKey
     *
     * @return BlikAliasResponseModel
     */
    public function setAliasKey(string $aliasKey): BlikAliasResponseModel
    {
        $this->aliasKey = $aliasKey;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAliasKey(): ?string
    {
        return $this->aliasKey;
    }

    public function getEndpoint(): string
    {
        return GetBlikProfilesEndpoint::class;
    }
}