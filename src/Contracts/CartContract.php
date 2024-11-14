<?php

namespace Mahmudulhsn\ShoppingCart\Contracts;

interface CartContract
{
    public function add(string $id, string $name, float $price, int|float $quantity, array $extraInfo = []): object;

    public function get(string $rowId): ?object;

    public function update(string $rowId, array $productData): object;

    public function remove(string $rowId): void;

    public function destroy(): void;

    public function total(): int|float;

    public function subtotal(): int|float;

    public function discountTotal(): int|float;

    public function content(): \Illuminate\Support\Collection;

    public function applyDiscount(int|float $amount, ?string $discountType = 'fix'): void;
}
