<?php

namespace Mahmudulhsn\ShoppingCart\Data;

class CartItem
{
    public string $rowId;

    public string $id;

    public string $name;

    public float $price;

    public float|int $quantity;

    public float|int $subtotal;

    public array $extraInfo;

    public function __construct(array $data)
    {
        $this->rowId = $data['rowId'];
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->price = $data['price'];
        $this->quantity = $data['quantity'];
        $this->subtotal = $data['subtotal'];
        $this->extraInfo = $data['extraInfo'] ?? [];
    }
}
