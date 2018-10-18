<?php

use Faker\Generator as Faker;
use Depsimon\Wallet\Transaction;
use Depsimon\Wallet\Wallet;

$factory->define(Transaction::class, function (Faker $faker, $attributes) {
    $wallet = array_has($attributes, 'wallet_id')
        ? Wallet::findOrFail($attributes['wallet_id'])
        : factory(Wallet::class)->create();
    $type = $faker->randomElement([
        'deposit',
        'withdraw',
    ]);
    $negativator = $type === 'withdraw' ? -1 : 1;
    return [
        'wallet_id' => $wallet->id,
        'type' => $type,
        'amount' => $faker->randomFloat(4, 0, 10000) * $negativator,
    ];
});