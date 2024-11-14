<?php

namespace Mahmudulhsn\ShoppingCart;

use Illuminate\Support\ServiceProvider;
use Mahmudulhsn\ShoppingCart\CartHelper;
use Mahmudulhsn\ShoppingCart\Facades\CartFacade;
use Mahmudulhsn\ShoppingCart\Repositories\SessionRepository;

class ShoppingCartServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bind the session repository to the service container
        $this->app->singleton(SessionRepository::class, function ($app) {
            return new SessionRepository();
        });

        // Bind the 'cart' singleton with session repository and helper
        $this->app->singleton('cart', function ($app) {
            $sessionRepository = $app->make(SessionRepository::class); // Get the SessionRepository instance
            $rootSessionKey = $this->getRootSessionKey();
            $this->initializeSession($sessionRepository, $rootSessionKey); // Pass the repository into initializeSession
            return new Cart($sessionRepository, $rootSessionKey, new CartHelper);
        });

        // Alias the 'cart' binding for easy access via facade
        $this->app->alias('cart', CartFacade::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Publish the configuration file for customization
        $this->publishes([
            __DIR__ . '/../config/shopping_cart.php' => config_path('shopping_cart.php'),
        ], 'lara-simple-shopping-cart-config');
    }

    /**
     * Get a unique session key for the cart based on the application name.
     */
    protected function getRootSessionKey(): string
    {
        return md5(config('app.name'));
    }

    /**
     * Initialize the session structure for the cart if it doesn't exist.
     */
    protected function initializeSession(SessionRepository $sessionRepository, string $rootSessionKey): void
    {
        if (!$sessionRepository->get($rootSessionKey)) {
            $sessionRepository->put($rootSessionKey, [
                'products' => [],
                'discount_type' => 'fix',
                'discount' => 0,
                'total' => 0,
                'subtotal' => 0,
            ]);
        }
    }
}
