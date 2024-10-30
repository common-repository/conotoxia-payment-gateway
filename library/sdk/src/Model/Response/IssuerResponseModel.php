<?php

declare(strict_types=1);

namespace CKPL\Pay\Model\Response;

use CKPL\Pay\Endpoint\GetAvailablePaymentMethodsEndpoint;
use CKPL\Pay\Model\ResponseModelInterface;

class IssuerResponseModel implements ResponseModelInterface
{
    /**
     * @var string|null
     */
   protected $id;

    /**
     * @var string|null
     */
   protected $name;

    /**
     * @var string|null
     */
   protected $code;

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * @param string|null $id
     *
     * @return IssuerResponseModel
     */
    public function setId(?string $id): IssuerResponseModel
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     *
     * @return IssuerResponseModel
     */
    public function setName(?string $name): IssuerResponseModel
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * @param string|null $code
     *
     * @return void
     */
    public function setCode(?string $code): IssuerResponseModel
    {
        $this->code = $code;
        return $this;
    }
   
   

    public function getEndpoint(): string
    {
        return GetAvailablePaymentMethodsEndpoint::class;
    }
}