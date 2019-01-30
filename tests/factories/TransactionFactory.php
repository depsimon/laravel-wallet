<?php

use Faker\Generator as Faker;
use Depsimon\Wallet\Models\Transaction;
use Depsimon\Wallet\Models\Wallet;

// $factory->define(Transaction::class, function (Faker $faker, $attributes) {
//     $wallet = array_has($attributes, 'wallet_id')
//         ? Wallet::findOrFail($attributes['wallet_id'])
//         : (Wallet::first() ? : factory(Wallet::class)->create());
//     $type = $faker->randomElement([
//         'deposit',
//         'withdraw',
//     ]);
//     $negativator = in_array($type, config('wallet.subtracting_transaction_types', [])) ? -1 : 1;
//     return [
//         'wallet_id' => $wallet->id,
//         'type' => $type,
//         'amount' => $faker->randomFloat(4, 0, 10000) * $negativator,
//         'hash' => uniqid()
//     ];
// });


$factory->define(config('wallet.transaction_model'), function (Faker $faker, $attributes) {
    $walletModel = config('wallet.wallet_model');
    $wallet = array_has($attributes, 'wallet_id')
        ? $walletModel::findOrFail($attributes['wallet_id'])
        : ($walletModel::first() ? : factory($walletModel)->create());
    $type = $faker->randomElement([
        'deposit',
        'withdraw',
    ]);
    return [
        'wallet_id' => $wallet->id,
        'type' => $type,
        'amount' => $faker->randomFloat(4, 0, 10000),
    ];
});

$factory->state(config('wallet.transaction_model'), 'withdraw', function ($faker, $attributes) {
    return ['type' => 'withdraw'];
});

$factory->state(config('wallet.transaction_model'), 'deposit', function ($faker, $attributes) {
    return ['type' => 'deposit'];
});