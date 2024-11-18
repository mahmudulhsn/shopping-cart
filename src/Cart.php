<?php

namespace Mahmudulhsn\ShoppingCart;

use Illuminate\Support\Collection;
use Mahmudulhsn\ShoppingCart\Data\ProductData;
use Mahmudulhsn\ShoppingCart\Exceptions\CartException;
use Mahmudulhsn\ShoppingCart\Repositories\SessionRepository;

class Cart
{
    private const SESSION_PRODUCTS = 'products';

    private const SESSION_TOTAL = 'total';

    private const SESSION_SUBTOTAL = 'subtotal';

    private const SESSION_DISCOUNT = 'discount';

    private const DEFAULT_DISCOUNT_TYPE = 'fix';

    protected string $rootSessionKey;

    protected CartHelper $cartHelper;

    protected SessionRepository $sessionRepository;

    /**
     * Cart constructor.
     */
    public function __construct(SessionRepository $sessionRepository, string $rootSessionKey, CartHelper $cartHelper)
    {
        $this->sessionRepository = $sessionRepository;
        $this->rootSessionKey = $rootSessionKey;
        $this->cartHelper = $cartHelper;
    }

    /**
     * Add a product to the cart.
     */
    public function add(string $id, string $name, float $price, int|float $quantity, array $extraInfo = []): void
    {
        $rowId = $this->cartHelper->generateRowId($id, [$id, $name, $price, $quantity]);
        $quantity = max(1, $quantity);

        $products = $this->cartHelper->getSessionData("{$this->rootSessionKey}." . self::SESSION_PRODUCTS, []);

        if (isset($products[$rowId])) {

            $newQuantity = $products[$rowId]['quantity'] + $quantity;
            $this->cartHelper->updateProductData($products, $rowId, $newQuantity, $price, $extraInfo);
        } else {

            $products[$rowId] = [
                'rowId' => $rowId,
                'id' => $id,
                'name' => $name,
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $quantity * $price,
                'extraInfo' => $extraInfo,
            ];
        }

        $this->sessionRepository->put("{$this->rootSessionKey}." . self::SESSION_PRODUCTS, $products);

        $this->cartHelper->updateTotal($this->rootSessionKey);
    }

    /**
     * Get details of a single product by its row ID.
     */
    public function get(string $rowId): ?object
    {
        $products = $this->cartHelper->getSessionData("{$this->rootSessionKey}." . self::SESSION_PRODUCTS, []);

        return isset($products[$rowId]) ? (object) $products[$rowId] : null;
    }

    /**
     * Update a product in the cart by its row ID.
     */
    public function update(string $rowId, ProductData $productData): void
    {
        $products = $this->cartHelper->getSessionData("{$this->rootSessionKey}." . self::SESSION_PRODUCTS, []);

        if (!isset($products[$rowId])) {
            throw new CartException("Product with row ID {$rowId} not found in cart.");
        }

        $quantity = $productData->quantity ?? $products[$rowId]['quantity'];
        $price = $productData->price ?? $products[$rowId]['price'];
        $extraInfo = $productData->extraInfo ?? null;

        $this->cartHelper->updateProductData($products, $rowId, $quantity, $price, $extraInfo);
        $this->sessionRepository->put("{$this->rootSessionKey}." . self::SESSION_PRODUCTS, $products);

        $this->cartHelper->updateTotal($this->rootSessionKey);
    }

    /**
     * Remove a product from the cart by its row ID.
     */
    public function remove(string $rowId): void
    {
        $products = $this->cartHelper->getSessionData("{$this->rootSessionKey}." . self::SESSION_PRODUCTS, []);

        if (!isset($products[$rowId])) {
            throw new CartException("Product with row ID {$rowId} not found in cart.");
        }

        unset($products[$rowId]);
        $this->sessionRepository->put("{$this->rootSessionKey}." . self::SESSION_PRODUCTS, $products);

        $this->cartHelper->updateTotal($this->rootSessionKey);
    }

    /**
     * Clear all products from the cart and reset totals.
     */
    public function destroy(): void
    {
        $this->sessionRepository->put("{$this->rootSessionKey}." . self::SESSION_PRODUCTS, []);
        $this->sessionRepository->put("{$this->rootSessionKey}." . self::SESSION_TOTAL, 0);
        $this->sessionRepository->put("{$this->rootSessionKey}." . self::SESSION_SUBTOTAL, 0);
        $this->sessionRepository->put("{$this->rootSessionKey}." . self::SESSION_DISCOUNT, 0);
    }

    /**
     * Get the total amount of the cart.
     */
    public function total(): int|float
    {
        return $this->cartHelper->getSessionData("{$this->rootSessionKey}." . self::SESSION_TOTAL, 0);
    }

    /**
     * Get the subtotal amount of the cart.
     */
    public function subtotal(): int|float
    {
        return $this->cartHelper->getSessionData("{$this->rootSessionKey}." . self::SESSION_SUBTOTAL, 0);
    }

    /**
     * Get the discount applied to the cart.
     */
    public function discountTotal(): int|float
    {
        return $this->cartHelper->getSessionData("{$this->rootSessionKey}." . self::SESSION_DISCOUNT, 0);
    }

    /**
     * Get all products in the cart.
     */
    public function content(): Collection
    {
        $products = $this->cartHelper->getSessionData("{$this->rootSessionKey}." . self::SESSION_PRODUCTS, []);

        $products = collect($products)->map(function ($product) {
            return (object) $product;
        });

        return $products;
    }

    /**
     * Apply a discount to the cart.
     */
    public function applyDiscount(int|float $amount, ?string $discountType = null): void
    {
        $this->cartHelper->updateDiscount($this->rootSessionKey, $amount, $discountType ?? self::DEFAULT_DISCOUNT_TYPE);
    }
}
