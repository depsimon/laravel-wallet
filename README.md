# Laravel Wallet

In a few projects I had to implement a virtual currency. The user would buy packs of credits with Stripe and then use them in the app in exchange of services or goods.
This package is a small and simple implementation of this concept with place for customization.

## Installation

Install the package with composer:

```bash
composer require depsimon/laravel-wallet
```

## Run Migrations

Publish the migrations with this artisan command:

```bash
php artisan vendor:publish --provider="Depsimon\Wallet\WalletServiceProvider" --tag=migrations
```

## Configuration

You can publish the config file with this artisan command:

```bash
php artisan vendor:publish --provider="Depsimon\Wallet\WalletServiceProvider" --tag=config
```

This will merge the `wallet.php` config file where you can specify the Users, Wallets & Transactions classes if you have custom ones.

## Usage

Add the `HasWallet` trait to your User model.

``` php

use Depsimon\Wallet\HasWallet;

class User extends Model
{
    use HasWallet;

    ...
}
```

Then you can easily make transactions from your user model.

``` php
$user = User::find(1);
$user->balance; // 0

$user->deposit(100);
$user->balance; // 100

$user->withdraw(50);
$user->balance; // 50

$user->forceWithdraw(200);
$user->balance; // -150
```

You can easily add meta information to the transactions to suit your needs.

``` php
$user = User::find(1);
$user->deposit(100, 'deposit', ['stripe_source' => 'ch_BEV2Iih1yzbf4G3HNsfOQ07h', 'description' => 'Deposit of 100 credits from Stripe Payment']);
$user->withdraw(10, 'withdraw', ['description' => 'Purchase of Item #1234']);
```

### Security

If you discover any security related issues, please email simon@webartisan.be instead of using the issue tracker.

## Credits

- [Simon Depelchin](https://github.com/depsimon)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
