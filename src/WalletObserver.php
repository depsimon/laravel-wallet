<?php

namespace Depsimon\Wallet;

use Depsimon\Wallet\Wallet;
use Depsimon\Wallet\Transaction;

class WalletObserver
{

    public function deleting(Wallet $wallet)
    {
        $wallet->transactions()->delete();
    }

    public function restoring(Wallet $wallet)
    {
        $wallet->transactions()->withTrashed()->restore();
    }
}