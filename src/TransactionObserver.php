<?php

namespace Depsimon\Wallet;

use Depsimon\Wallet\Wallet;
use Depsimon\Wallet\Transaction;

class TransactionObserver
{
    public function creating($transaction)
    {
        $transaction->hash = uniqid();
    }
}