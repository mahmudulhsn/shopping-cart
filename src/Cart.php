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
     * Cart constructor initializes the cart with session key, session repository, and helper for cart functionalities.
     *
     * @param SessionRepository $sessionRepository  The session repository to handle session storage.
     * @param string $rootSessionKey  The root key for cart session data.
     * @param CartHelper $cartHelper  The helper class to manage cart data manipulation.
     */
    public function __construct(SessionRepository $sessionRepository, string $rootSessionKey, CartHelper $cartHelper)
    {
        $this->sessionRepository = $sessionRepository;
        $this->rootSessionKey = $rootSessionKey;
        $this->cartHelper = $cartHelper;
    }

    /**
     * Add a product to the cart, or update it if already present.
     *
     * This method checks if the product already exists in the cart. If it does, it updates the quantity.
     * If not, it adds the product to the cart with the specified details.
     *
     * @param string $id  The product ID.
     * @param string $name  The product name.
     * @param float $price  The product price.
     * @param int|float $quantity  The quantity of the product.
     * @param array $extraInfo  Any additional information for the product (optional).
     * @return void
     */
    public function add($id, $name, $price, $quantity, $extraInfo = []): void
    {
        $rowId = $this->cartHelper->generateRowId($id, [$id, $name]);
        $quantity = max(1, $quantity);
        $products = $this->cartHelper->getSessionData("{$this->rootSessionKey}." . self::SESSION_PRODUCTS);

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
     * Get the details of a specific product in the cart by its row ID.
     *
     * This method returns the details of a product if it exists in the cart, otherwise returns null.
     *
     * @param string $rowId  The row ID of the product.
     * @return object|null  The product details or null if not found.
     */
    public function get($rowId): ?object
    {

        $products = $this->cartHelper->getSessionData("{$this->rootSessionKey}." . self::SESSION_PRODUCTS, []);


        return isset($products[$rowId]) ? (object) $products[$rowId] : null;
    }

    /**
     * Update the details of a product in the cart by its row ID.
     *
     * This method allows you to update the quantity, price, or extra information of an existing product in the cart.
     * If the product is not found, an exception is thrown.
     *
     * @param string $rowId  The row ID of the product.
     * @param ProductData $productData  The updated product data including quantity, price, and extra information.
     * @return void
     * @throws CartException  If the product with the given row ID does not exist in the cart.
     */
    public function update($rowId, $productData): void
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
     *
     * This method removes the product from the cart and recalculates the total and subtotal values.
     * If the product is not found, an exception is thrown.
     *
     * @param string $rowId  The row ID of the product to be removed.
     * @return void
     * @throws CartException  If the product with the given row ID does not exist in the cart.
     */
    public function remove($rowId): void
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
     * Clear all products from the cart and reset all totals and discounts.
     *
     * This method removes all products from the cart and resets the subtotal, total, and discount values.
     *
     * @return void
     */
    public function destroy(): void
    {
        $this->sessionRepository->put("{$this->rootSessionKey}." . self::SESSION_PRODUCTS, []);
        $this->sessionRepository->put("{$this->rootSessionKey}." . self::SESSION_TOTAL, 0);
        $this->sessionRepository->put("{$this->rootSessionKey}." . self::SESSION_SUBTOTAL, 0);
        $this->sessionRepository->put("{$this->rootSessionKey}." . self::SESSION_DISCOUNT, 0);
    }

    /**
     * Get the total amount of the cart after applying any discounts.
     *
     * This method retrieves the total value of the cart, including any applicable discounts.
     *
     * @return int|float  The total amount of the cart.
     */
    public function total(): int|float
    {
        return $this->cartHelper->getSessionData("{$this->rootSessionKey}." . self::SESSION_TOTAL, 0);
    }

    /**
     * Get the subtotal amount of the cart before applying any discounts.
     *
     * This method retrieves the subtotal value of the cart, which is the total of all product subtotals.
     *
     * @return int|float  The subtotal amount of the cart.
     */
    public function subtotal(): int|float
    {
        return $this->cartHelper->getSessionData("{$this->rootSessionKey}." . self::SESSION_SUBTOTAL, 0);
    }

    /**
     * Get the discount amount applied to the cart.
     *
     * This method retrieves the total discount applied to the cart.
     *
     * @return int|float  The discount amount applied to the cart.
     */
    public function discountTotal(): int|float
    {
        return $this->cartHelper->getSessionData("{$this->rootSessionKey}." . self::SESSION_DISCOUNT, 0);
    }

    /**
     * Get all products currently in the cart.
     *
     * This method retrieves all products stored in the cart and returns them as a collection.
     *
     * @return Collection  A collection of all products in the cart.
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
     * Apply a discount to the cart based on the amount and discount type.
     *
     * This method applies a discount to the cart. The discount can be a fixed amount or a percentage.
     *
     * @param int|float $amount  The amount or percentage of the discount.
     * @param string|null $discountType  The type of discount ('fix' for fixed amount, 'percentage' for percentage-based).
     * @return void
     */
    public function applyDiscount($amount, $discountType = null): void
    {
        $this->cartHelper->updateDiscount($this->rootSessionKey, $amount, $discountType ?? self::DEFAULT_DISCOUNT_TYPE);
    }
}
