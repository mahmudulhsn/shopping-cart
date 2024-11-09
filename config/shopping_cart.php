<?php

return [
    /*
        |--------------------------------------------------------------------------
        | Default tax rate
        |--------------------------------------------------------------------------
        |
        | This default tax rate will be used when you make a class implement the
        | Taxable interface and use the HasTax trait.
        |
        */

    'tax' => 15,

    /*
    |--------------------------------------------------------------------------
    | ShoppingCart database settings
    |--------------------------------------------------------------------------
    |
    | Here you can set the connection that the ShoppingCart should use when
    | storing and restoring a cart.
    |
    */

    'database' => [
        'connection' => null,
        'table' => 'lara_simple_shopping_cart',
    ],

    /*
    |--------------------------------------------------------------------------
    | Destroy the cart on user logout
    |--------------------------------------------------------------------------
    |
    | When this option is set to 'true' the cart will automatically
    | destroy all cart instances when the user logs out.
    |
    */

    'destroy_on_logout' => false,

    /*
    |--------------------------------------------------------------------------
    | ShoppingCart storage settings
    |--------------------------------------------------------------------------
    |
    | Here you can set the storage type whether it will store in session or database
    | Type would be form: session, database
    |
    */
    'storage_type' => 'session'

];
