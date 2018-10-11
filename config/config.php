<?php

return [
    /**
     * Disable auto-loading of the vendor migrations
     * You can then publish the migrations and
     * change them for more flexibility
     */
    'load_migrations' => env('WALLET_LOAD_MIGRATIONS', true),
    /**
     * Change this to specify the money amount column types
     * If not explicitly set to 'decimal' integer columns are used
     */
    'column_type' => env('WALLET_COLUMN_TYPE', 'decimal'),

    /**
     * Change this if you need to extend the default Wallet Model
     */
    'wallet_model' => env('WALLET_MODEL', 'Depsimon\Wallet\Wallet'),

    /**
     * Change this if you need to extend the default Transaction Model
     */
    'transaction_model' => env('WALLET_TRANSACTION_MODEL', 'Depsimon\Wallet\Transaction'),
];