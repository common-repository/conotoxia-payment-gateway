<?php

declare(strict_types=1);

namespace CKPL\Pay\Model\Collection;

use ArrayIterator;
use CKPL\Pay\Model\CollectionInterface;
use CKPL\Pay\Model\ModelInterface;
use CKPL\Pay\Model\ProcessedOutputInterface;
use CKPL\Pay\Model\Response\PaymentMethodResponseModel;
class AvailablePaymentMethodsResponseModelCollection implements CollectionInterface, ProcessedOutputInterface
{
    /**
     * @var array|PaymentMethodResponseModel[]
     */
    protected $data = [];
    
    public function add(ModelInterface $model): void
    {
        $this->data[] = $model;
    }

    public function all(): array
    {
       return $this->data;
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->all());
    }
}