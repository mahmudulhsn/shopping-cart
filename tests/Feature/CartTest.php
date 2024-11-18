<?php

use Mahmudulhsn\ShoppingCart\Cart;
use Mahmudulhsn\ShoppingCart\CartHelper;
use Mahmudulhsn\ShoppingCart\Data\ProductData;
use Mahmudulhsn\ShoppingCart\Repositories\SessionRepository;

it('adds a product to the cart', function () {
    $id = '123';
    $name = 'Product 1';
    $price = 10.0;
    $quantity = 2;
    $extraInfo = ['color' => 'red'];

    $cartHelper = mock(CartHelper::class);
    $sessionRepository = mock(SessionRepository::class);
    $cart = new Cart($sessionRepository, 'testSessionKey', $cartHelper);

    $cartHelper
        ->shouldReceive('generateRowId')
        ->with($id, [$id, $name, $price, $quantity])
        ->once()
        ->andReturn('rowId123');

    $cartHelper
        ->shouldReceive('getSessionData')
        ->with('testSessionKey.products', [])
        ->once()
        ->andReturn([]);

    $cartHelper
        ->shouldReceive('updateTotal')
        ->with('testSessionKey')
        ->once(); // Add this expectation for updateTotal

    $sessionRepository
        ->shouldReceive('put')
        ->with('testSessionKey.products', [
            'rowId123' => [
                'rowId' => 'rowId123',
                'id' => $id,
                'name' => $name,
                'price' => $price,
                'quantity' => $quantity,
                'subtotal' => $quantity * $price,
                'extraInfo' => $extraInfo,
            ],
        ])
        ->once();

    $cart->add($id, $name, $price, $quantity, $extraInfo);
});

it('retrieves a product by its rowId', function () {
    $rowId = 'rowId123';
    $productData = [
        'rowId' => $rowId,
        'id' => '123',
        'name' => 'Product 1',
        'price' => 10.0,
        'quantity' => 2,
        'subtotal' => 20.0,
        'extraInfo' => ['color' => 'red'],
    ];

    $cartHelper = mock(CartHelper::class);
    $sessionRepository = mock(SessionRepository::class);
    $cart = new Cart($sessionRepository, 'testSessionKey', $cartHelper);

    $cartHelper
        ->shouldReceive('getSessionData')
        ->with('testSessionKey.products', [])
        ->once()
        ->andReturn([$rowId => $productData]);

    $product = $cart->get($rowId);

    expect($product)->not->toBeNull();
    expect($product->rowId)->toBe($rowId);
});

it('updates a product in the cart', function () {
    $rowId = 'rowId123';

    // Pass required arguments to the constructor
    $productData = new ProductData(3, 15.0, ['color' => 'blue']);

    // Setting up mocks and expectations
    $cartHelper = mock(CartHelper::class);
    $sessionRepository = mock(SessionRepository::class);
    $cart = new Cart($sessionRepository, 'testSessionKey', $cartHelper);

    $cartHelper
        ->shouldReceive('getSessionData')
        ->with('testSessionKey.products', [])
        ->once()
        ->andReturn([
            $rowId => [
                'rowId' => $rowId,
                'id' => '123',
                'name' => 'Product 1',
                'price' => 10.0,
                'quantity' => 2,
                'subtotal' => 20.0,
                'extraInfo' => ['color' => 'red'],
            ],
        ]);

    $cartHelper
        ->shouldReceive('updateProductData')
        ->with(
            \Mockery::any(),  // Any product list
            $rowId,           // RowId to update
            3,                // Updated quantity
            15.0,             // Updated price
            ['color' => 'blue']  // Updated extraInfo
        )
        ->once();

    $cartHelper
        ->shouldReceive('updateTotal')
        ->with('testSessionKey')
        ->once();

    $sessionRepository
        ->shouldReceive('put')
        ->with('testSessionKey.products', \Mockery::any()) // Products should be updated
        ->once();

    $cart->update($rowId, $productData);
});

it('removes a product from the cart', function () {
    $rowId = 'rowId123';

    // Setup CartHelper mock
    $cartHelper = mock(CartHelper::class);
    $sessionRepository = mock(SessionRepository::class);
    $cart = new Cart($sessionRepository, 'testSessionKey', $cartHelper);

    // Mock the session data
    $cartHelper
        ->shouldReceive('getSessionData')
        ->with('testSessionKey.products', [])
        ->once()
        ->andReturn([
            $rowId => [
                'rowId' => $rowId,
                'id' => '123',
                'name' => 'Product 1',
                'price' => 10.0,
                'quantity' => 2,
                'subtotal' => 20.0,
                'extraInfo' => ['color' => 'red'],
            ],
        ]);

    // Expect updateTotal to be called after product removal
    $cartHelper
        ->shouldReceive('updateTotal')
        ->with('testSessionKey')
        ->once();

    // Mock sessionRepository to update the session with new products
    $sessionRepository
        ->shouldReceive('put')
        ->with('testSessionKey.products', \Mockery::any()) // Products should be updated
        ->once();

    // Call remove method to test
    $cart->remove($rowId);
});

it('clears the cart', function () {
    $sessionRepository = mock(SessionRepository::class);
    $cartHelper = mock(CartHelper::class);
    $cart = new Cart($sessionRepository, 'testSessionKey', $cartHelper);

    $sessionRepository
        ->shouldReceive('put')
        ->with('testSessionKey.products', [])
        ->once();
    $sessionRepository
        ->shouldReceive('put')
        ->with('testSessionKey.total', 0)
        ->once();
    $sessionRepository
        ->shouldReceive('put')
        ->with('testSessionKey.subtotal', 0)
        ->once();
    $sessionRepository
        ->shouldReceive('put')
        ->with('testSessionKey.discount', 0)
        ->once();

    $cart->destroy();
});

it('retrieves the total amount of the cart', function () {
    $cartHelper = mock(CartHelper::class);
    $sessionRepository = mock(SessionRepository::class);
    $cart = new Cart($sessionRepository, 'testSessionKey', $cartHelper);

    $cartHelper
        ->shouldReceive('getSessionData')
        ->with('testSessionKey.total', 0)
        ->once()
        ->andReturn(100.0);

    $total = $cart->total();

    expect($total)->toBe(100.0);
});

it('applies a discount to the cart', function () {
    $amount = 20.0;
    $discountType = 'fix';

    $cartHelper = mock(CartHelper::class);
    $sessionRepository = mock(SessionRepository::class);
    $cart = new Cart($sessionRepository, 'testSessionKey', $cartHelper);

    $cartHelper
        ->shouldReceive('updateDiscount')
        ->with('testSessionKey', $amount, $discountType)
        ->once();

    $cart->applyDiscount($amount, $discountType);
});
