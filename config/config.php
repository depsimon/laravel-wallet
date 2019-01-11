<?php
use Depsimon\Wallet\Models\Wallet;
use Depsimon\Wallet\Models\Transaction;

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
    'wallet_model' => env('WALLET_MODEL', Wallet::class),

    /**
     * Change this if you need to extend the default Transaction Model
     */
    'transaction_model' => env('WALLET_TRANSACTION_MODEL', Transaction::class),

    /**
     * Activate automatic recalculation of the wallet balance
     * from the transaction history after wallet balance has
     * changed to ensure correct value.
     */
    'auto_recalculate_balance' => env('WALLET_AUTO_RECALCULATE_BALANCE', false),

    /**
     * Transaction types that are subtracted from the wallet balance.
     * All amounts will be converted to a positive value
     */
    'adding_transaction_types' => explode(',', env('WALLET_ADDING_TRANSACTION_TYPES', 'deposit,refund')),

    /**
     * Transaction types that are subtracted from the wallet balance
     * All amounts will be converted to a negative value
     */
    'subtracting_transaction_types' => explode(',', env('WALLET_SUBTRACTING_TRANSACTION_TYPES', 'withdraw,payout')),
];