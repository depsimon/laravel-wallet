<?php

use Faker\Generator as Faker;
use Depsimon\Wallet\Tests\Models\User;

$factory->define(User::class, function (Faker $faker) {
    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => bcrypt('test'),
        'remember_token' => str_random(10),
    ];
});