<?php

namespace Mahmudulhsn\ShoppingCart;

use Illuminate\Support\ServiceProvider;
use Mahmudulhsn\ShoppingCart\Cart;

class ShoppingCartServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton('cart', function ($app) {
            $rootSessionKey = $this->getRootSessionKey();
            $this->initializeSession($rootSessionKey);

            return new Cart($rootSessionKey, new CartHelper());
        });

        // Alias the 'cart' binding for easy access via facade
        $this->app->alias('cart', \Mahmudulhsn\ShoppingCart\Facades\CartFacade::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish the configuration file for customization
        $this->publishes([
            __DIR__ . '/../config/shopping_cart.php' => config_path('shopping_cart.php')
        ], 'lara-simple-shopping-cart-config');
    }

    /**
     * Get a unique session key for the cart based on the application name.
     *
     * @return string
     */
    protected function getRootSessionKey(): string
    {
        return md5(config('app.name'));
    }

    /**
     * Initialize the session structure for the cart if it doesn't exist.
     *
     * @param string $rootSessionKey
     */
    protected function initializeSession(string $rootSessionKey): void
    {
        if (!session()->has($rootSessionKey)) {
            session()->put($rootSessionKey, [
                'products' => [],
                'discount_type' => 'fix',
                'discount' => 0,
                'total' => 0,
                'subtotal' => 0,
            ]);
        }
    }
}
