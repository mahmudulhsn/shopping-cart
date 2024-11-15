<?php

use Mahmudulhsn\ShoppingCart\Cart;
use Mahmudulhsn\ShoppingCart\CartHelper;
use Mahmudulhsn\ShoppingCart\Repositories\SessionRepository;
use Mahmudulhsn\ShoppingCart\Exceptions\CartException;

beforeEach(function () {
    // Mock SessionRepository and CartHelper
    $this->sessionRepository = Mockery::mock(SessionRepository::class);
    $this->cartHelper = Mockery::mock(CartHelper::class);

    // Root session key
    $this->rootSessionKey = 'cart_test';

    // Create Cart instance
    $this->cart = new Cart($this->sessionRepository, $this->rootSessionKey, $this->cartHelper);
});

it('can add a product to the cart', function () {
    $rowId = 'row_1';
    $productId = '1';
    $productName = 'Product Name';
    $productPrice = 100.00;
    $productQuantity = 2;
    $extraInfo = ['color' => 'red'];

    // Mock the generateRowId method to return a specific row ID
    $this->cartHelper
        ->shouldReceive('generateRowId')
        ->with($productId, [$productId, $productName, $productPrice, $productQuantity])
        ->andReturn($rowId);

    // Mock session get method to return an empty products array initially
    $this->sessionRepository
        ->shouldReceive('get')
        ->with("{$this->rootSessionKey}.products", [])
        ->andReturn([]);

    // Mock session put method to store the product in the session
    $expectedProduct = [
        $rowId => [
            'rowId' => $rowId,
            'id' => $productId,
            'name' => $productName,
            'price' => $productPrice,
            'quantity' => $productQuantity,
            'subtotal' => $productQuantity * $productPrice,
            'extraInfo' => $extraInfo,
        ]
    ];

    $this->sessionRepository
        ->shouldReceive('put')
        ->with("{$this->rootSessionKey}.products", $expectedProduct)
        ->once();

    // Mock the updateTotal method
    $this->cartHelper
        ->shouldReceive('updateTotal')
        ->with($this->rootSessionKey)
        ->once();

    // Mock the session get call after adding the product
    $this->sessionRepository
        ->shouldReceive('get')
        ->with("{$this->rootSessionKey}.products")
        ->andReturn($expectedProduct);

    // Call the add method
    $this->cart->add($productId, $productName, $productPrice, $productQuantity, $extraInfo);

    // Assert that the product was added to the session correctly
    $addedProduct = $this->sessionRepository->get("{$this->rootSessionKey}.products")[$rowId];

    expect($addedProduct['rowId'])->toBe($rowId);
    expect($addedProduct['name'])->toBe($productName);
    expect($addedProduct['quantity'])->toBe($productQuantity);
    expect($addedProduct['subtotal'])->toBe($productQuantity * $productPrice);
    expect($addedProduct['extraInfo'])->toBe($extraInfo);
});

it('can update a product in the cart', function () {
    // Initial product in cart
    $this->sessionRepository
        ->shouldReceive('get')
        ->with("{$this->rootSessionKey}.products", [])
        ->andReturn([
            'row_1' => [
                'rowId' => 'row_1',
                'id' => '1',
                'name' => 'Product Name',
                'price' => 100.00,
                'quantity' => 2,
                'subtotal' => 200.00,
            ]
        ]);

    // Mock session put method for updated product
    $this->sessionRepository
        ->shouldReceive('put')
        ->with("{$this->rootSessionKey}.products", Mockery::type('array'))
        ->once();

    $this->cartHelper
        ->shouldReceive('updateTotal')
        ->with($this->rootSessionKey)
        ->once();

    $product = $this->cart->update('row_1', ['quantity' => 3]);

    expect($product->quantity)->toBe(2);
    expect($product->subtotal)->toBe(200.00);
});


it('throws exception when trying to update non-existent product', function () {
    // Non-existent product in the cart
    $this->sessionRepository
        ->shouldReceive('get')
        ->with("{$this->rootSessionKey}.products", [])
        ->andReturn([]);

    $this->cart->update('non_existent_row', ['quantity' => 1]);
})->throws(CartException::class, 'Product with row ID non_existent_row not found in cart.');

it('can remove a product from the cart', function () {
    // Initial product in cart
    $this->sessionRepository
        ->shouldReceive('get')
        ->with("{$this->rootSessionKey}.products", [])
        ->andReturn([
            'row_1' => [
                'rowId' => 'row_1',
                'id' => '1',
                'name' => 'Product Name',
                'price' => 100.00,
                'quantity' => 2,
                'subtotal' => 200.00,
            ]
        ]);

    $this->sessionRepository
        ->shouldReceive('put')
        ->with("{$this->rootSessionKey}.products", [])
        ->once();

    $this->cartHelper
        ->shouldReceive('updateTotal')
        ->with($this->rootSessionKey)
        ->once();

    $this->cart->remove('row_1');

    // After removing, the cart should have no products
    $this->sessionRepository
        ->shouldReceive('get')
        ->with("{$this->rootSessionKey}.products", [])
        ->andReturn([]);
});

it('can clear the cart', function () {
    $this->sessionRepository
        ->shouldReceive('put')
        ->with("{$this->rootSessionKey}.products", [])
        ->once();
    $this->sessionRepository
        ->shouldReceive('put')
        ->with("{$this->rootSessionKey}.total", 0)
        ->once();
    $this->sessionRepository
        ->shouldReceive('put')
        ->with("{$this->rootSessionKey}.subtotal", 0)
        ->once();
    $this->sessionRepository
        ->shouldReceive('put')
        ->with("{$this->rootSessionKey}.discount", 0)
        ->once();

    $this->cart->destroy();
});

it('can get the total of the cart', function () {
    $this->sessionRepository
        ->shouldReceive('get')
        ->with("{$this->rootSessionKey}.total", 0)
        ->andReturn(300.00);

    $total = $this->cart->total();

    expect($total)->toBe(300.00);
});

it('can apply a discount to the cart', function () {
    $this->cartHelper
        ->shouldReceive('updateDiscount')
        ->with($this->rootSessionKey, 50, 'fix')
        ->once();

    $this->cart->applyDiscount(50, 'fix');
});
