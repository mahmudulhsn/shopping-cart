<?php

namespace Mahmudulhsn\ShoppingCart;

use Illuminate\Support\Collection;
use Mahmudulhsn\ShoppingCart\CartHelper;
use Mahmudulhsn\ShoppingCart\Exceptions\CartException;
use Mahmudulhsn\ShoppingCart\Repositories\SessionRepository;

class Cart
{
    /**
     * @var string Root session key for storing cart data.
     */
    protected string $rootSessionKey;

    /**
     * @var CartHelper Helper class for cart operations.
     */
    protected CartHelper $cartHelper;

    /**
     * @var SessionRepository Session repository for session interactions.
     */
    protected SessionRepository $sessionRepository;

    /**
     * Cart constructor.
     *
     * @param  SessionRepository  $sessionRepository  Instance of SessionRepository.
     * @param  string  $rootSessionKey  Root session key.
     * @param  CartHelper  $cartHelper  Instance of CartHelper.
     */
    public function __construct(SessionRepository $sessionRepository, string $rootSessionKey, CartHelper $cartHelper)
    {
        $this->sessionRepository = $sessionRepository;
        $this->rootSessionKey = $rootSessionKey;
        $this->cartHelper = $cartHelper;
    }

    /**
     * Add product to cart.
     *
     * @param  string  $id  Product ID.
     * @param  string  $name  Product name.
     * @param  float  $price  Product price.
     * @param  int|float  $quantity  Product quantity.
     * @param  array  $extraInfo  Additional product info.
     * @return object Added product as an object.
     */
    public function add(string $id, string $name, float $price, int|float $quantity, array $extraInfo = []): void
    {
        $rowId = $this->cartHelper->generateRowId($id, [$id, $name, $price, $quantity]);
        $quantity = max(1, $quantity); // Ensure quantity is at least 1
        $products = $this->sessionRepository->get("{$this->rootSessionKey}.products", []);

        $products[$rowId] = [
            'rowId' => $rowId,
            'id' => $id,
            'name' => $name,
            'price' => $price,
            'quantity' => $quantity,
            'subtotal' => $quantity * $price,
        ];

        if (!empty($extraInfo)) {
            $products[$rowId]['extraInfo'] = $extraInfo;
        }

        $this->sessionRepository->put("{$this->rootSessionKey}.products", $products);
        $this->cartHelper->updateTotal($this->rootSessionKey);
    }

    /**
     * Get single product details by row ID.
     *
     * @param  string  $rowId  Row ID of the product.
     * @return object|null Product as an object or null if not found.
     */
    public function get(string $rowId): ?object
    {
        $products = $this->sessionRepository->get("{$this->rootSessionKey}.products", []);

        return isset($products[$rowId]) ? (object) $products[$rowId] : null;
    }

    /**
     * Update cart item by row ID.
     *
     * @param  string  $rowId  Row ID of the product.
     * @param  array  $productData  Data to update (quantity, price, extraInfo).
     * @return object Updated product as an object.
     *
     * @throws CartException If product is not found in cart.
     */
    public function update(string $rowId, array $productData): object
    {
        $products = $this->sessionRepository->get("{$this->rootSessionKey}.products", []);
        if (array_key_exists($rowId, $products)) {
            $quantity = $productData['quantity'] ?? $products[$rowId]['quantity'];
            $price = $productData['price'] ?? $products[$rowId]['price'];

            $products[$rowId]['price'] = $price;
            $products[$rowId]['quantity'] = $quantity;
            $products[$rowId]['subtotal'] = $quantity * $price;

            if (isset($productData['extraInfo']) && !empty($productData['extraInfo'])) {
                $products[$rowId]['extraInfo'] = $productData['extraInfo'];
            }

            $this->sessionRepository->put("{$this->rootSessionKey}.products", $products);
            $this->cartHelper->updateTotal($this->rootSessionKey);

            return $this->get($rowId);
        }

        throw new CartException("Product with row ID {$rowId} not found in cart.");
    }

    /**
     * Remove item from cart by row ID.
     *
     * @param  string  $rowId  Row ID of the product to remove.
     *
     * @throws CartException If product is not found in cart.
     */
    public function remove(string $rowId): void
    {
        $products = $this->sessionRepository->get("{$this->rootSessionKey}.products", []);
        if (array_key_exists($rowId, $products)) {
            unset($products[$rowId]);
            $this->sessionRepository->put("{$this->rootSessionKey}.products", $products);
            $this->cartHelper->updateTotal($this->rootSessionKey);
        } else {
            throw new CartException("Product with row ID {$rowId} not found in cart.");
        }
    }

    /**
     * Clear the cart.
     */
    public function destroy(): void
    {
        $this->sessionRepository->put("{$this->rootSessionKey}.products", []);
        $this->sessionRepository->put("{$this->rootSessionKey}.total", 0);
        $this->sessionRepository->put("{$this->rootSessionKey}.subtotal", 0);
        $this->sessionRepository->put("{$this->rootSessionKey}.discount", 0);
    }

    /**
     * Get total of the cart.
     *
     * @return int|float Total amount of the cart.
     */
    public function total(): int|float
    {
        return $this->sessionRepository->get("{$this->rootSessionKey}.total", 0);
    }

    /**
     * Get subtotal of the cart.
     *
     * @return int|float Subtotal amount of the cart.
     */
    public function subtotal(): int|float
    {
        return $this->sessionRepository->get("{$this->rootSessionKey}.subtotal", 0);
    }

    /**
     * Get discount applied to the cart.
     *
     * @return int|float Discount amount applied to the cart.
     */
    public function discountTotal(): int|float
    {
        return $this->sessionRepository->get("{$this->rootSessionKey}.discount", 0);
    }

    /**
     * Get all products in the cart.
     *
     * @return \Illuminate\Support\Collection Collection of products.
     */
    public function content(): Collection
    {
        $products = $this->sessionRepository->get("{$this->rootSessionKey}.products", []);

        return collect($products);
    }

    /**
     * Apply discount to the entire cart.
     *
     * @param  int|float  $amount  Discount amount to apply.
     * @param  string|null  $discountType  Type of discount (e.g., 'fix' or 'percentage').
     */
    public function applyDiscount(int|float $amount, ?string $discountType = 'fix'): void
    {
        $this->cartHelper->updateDiscount($this->rootSessionKey, $amount, $discountType);
    }
}
