<?php

namespace Mahmudulhsn\ShoppingCart;

use Mahmudulhsn\ShoppingCart\Repositories\SessionRepository;

class CartHelper
{
    protected SessionRepository $session;

    /**
     * CartHelper constructor.
     *
     * @param  SessionRepository  $session  Laravel session instance.
     */
    public function __construct(SessionRepository $session)
    {
        $this->session = $session;
    }

    /**
     * Generate a unique row ID for the cart item based on product details.
     *
     * @param  string  $id  Product ID.
     * @param  array  $productDetails  Details of the product to be hashed.
     * @return string Unique row ID for the product.
     */
    public function generateRowId($id, $productDetails): string
    {
        ksort($productDetails);

        return md5($id . serialize($productDetails));
    }

    /**
     * Update the total amount of the cart and store it in the session.
     *
     * @param  string  $rootSessionKey  Root session key for the cart.
     */
    public function updateTotal($rootSessionKey): void
    {
        $products = $this->getSessionData("{$rootSessionKey}.products", []);
        $cartTotal = array_sum(array_column($products, 'subtotal'));

        $this->session->put("{$rootSessionKey}.subtotal", $cartTotal);

        $discount = $this->getSessionData("{$rootSessionKey}.discount", 0);
        $this->session->put("{$rootSessionKey}.total", $cartTotal - $discount);
    }

    /**
     * Update the discount amount for the cart based on the type and amount.
     *
     * @param  string  $rootSessionKey  Root session key for the cart.
     * @param  int|float  $amount  Discount amount to apply.
     * @param  string|null  $discountType  Type of discount ('fix' or 'percentage').
     *
     * @throws \Exception If discount information is invalid.
     */
    public function updateDiscount($rootSessionKey, $amount, $discountType = 'fix'): void
    {
        $total = $this->getSessionData("{$rootSessionKey}.subtotal", 0);
        $discountAmount = 0;

        if ($discountType === 'percentage' && $amount <= 100) {
            $discountAmount = ($total * $amount) / 100;
        } elseif ($discountType === 'fix' && $amount <= $total) {
            $discountAmount = $amount;
        } else {
            throw new \Exception('Invalid discount information in cart.');
        }

        $discountedTotal = $total - $discountAmount;

        $this->session->put("{$rootSessionKey}.discount_type", $discountType);
        $this->session->put("{$rootSessionKey}.discount", $discountAmount);
        $this->session->put("{$rootSessionKey}.total", $discountedTotal);
    }

    /**
     * Retrieve session data with a default value.
     *
     * @param  string  $key  Session key.
     * @param  mixed  $default  Default value if key does not exist.
     * @return mixed Session data.
     */
    public function getSessionData($key, $default = null)
    {
        return $this->session->get($key, $default);
    }

    /**
     * Update product data in the session.
     *
     * @param  array  &$products  Reference to the products array.
     * @param  string  $rowId  Row ID of the product.
     * @param  int|float  $quantity  Product quantity.
     * @param  float  $price  Product price.
     * @param  array|null  $extraInfo  Additional product info.
     */
    public function updateProductData(array &$products, $rowId, $quantity, $price, $extraInfo = null): void
    {
        $products[$rowId]['price'] = $price;
        $products[$rowId]['quantity'] = $quantity;
        $products[$rowId]['subtotal'] = $quantity * $price;
        if (!empty($extraInfo)) {
            $products[$rowId]['extraInfo'] = $extraInfo;
        }
    }
}
