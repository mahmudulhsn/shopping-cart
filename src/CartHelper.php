<?php

namespace Mahmudulhsn\ShoppingCart;

use Illuminate\Contracts\Session\Session;

class CartHelper
{
    protected $session;

    // Inject the session repository into the constructor
    public function __construct(Session $session)
    {
        $this->session = $session;
    }

    /**
     * Generate a unique ID for the cart item based on product details.
     *
     * @param  string  $id  Product ID.
     * @param  array  $productDetails  Details of the product to be hashed.
     * @return string Unique row ID for the product.
     */
    public function generateRowId(string $id, array $productDetails): string
    {
        ksort($productDetails);
        return md5($id . serialize($productDetails));
    }

    /**
     * Update the total amount of the cart and store it in the session.
     *
     * @param  string  $rootSessionKey  Root session key for the cart.
     * @return int|float The updated total of the cart.
     */
    public function updateTotal(string $rootSessionKey): int|float
    {
        $cart = $this->session->get($rootSessionKey);
        $products = $this->session->get("{$rootSessionKey}.products", []);
        $cartTotal = array_sum(array_column($products, 'subtotal'));

        $this->session->put("{$rootSessionKey}.total", $cartTotal);

        return $this->session->get("{$rootSessionKey}.total");
    }

    /**
     * Update the discount amount for the cart based on discount type and amount.
     *
     * @param  string  $rootSessionKey  Root session key for the cart.
     * @param  int|float  $amount  Discount amount to apply.
     * @param  string|null  $discountType  Type of discount ('fix' or 'percentage').
     *
     * @throws \Exception If discount information is invalid.
     */
    public function updateDiscount(string $rootSessionKey, int|float $amount, ?string $discountType = 'fix'): void
    {
        $total = $this->updateTotal($rootSessionKey);
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
        $this->session->put("{$rootSessionKey}.subtotal", $discountedTotal);
    }
}
