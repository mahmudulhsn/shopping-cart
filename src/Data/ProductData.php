<?php

namespace Mahmudulhsn\ShoppingCart\Data;

class ProductData
{
    public int $quantity;

    public int|float|null $price;

    public ?array $extraInfo;

    public function __construct(int $quantity, int|float|null $price = null, ?array $extraInfo = null)
    {
        $this->quantity = $quantity;
        $this->price = $price;
        $this->extraInfo = $extraInfo;
    }
}
