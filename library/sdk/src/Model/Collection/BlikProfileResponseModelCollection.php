<?php

declare(strict_types=1);

namespace CKPL\Pay\Model\Collection;

use ArrayIterator;
use CKPL\Pay\Model\CollectionInterface;
use CKPL\Pay\Model\ModelInterface;
use CKPL\Pay\Model\ProcessedOutputInterface;
use CKPL\Pay\Model\Response\BlikAliasResponseModel;

class BlikProfileResponseModelCollection implements CollectionInterface, ProcessedOutputInterface
{

    /**
     * @var array|BlikAliasResponseModel[]
     */
    protected $aliases = [];

    public function add(ModelInterface $model): void
    {
        $this->aliases[] = $model;
    }

    public function all(): array
    {
       return $this->aliases;
    }

    /**
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->all());
    }
}