<?php

namespace Depsimon\Wallet;

use Depsimon\Wallet\Wallet;
use Depsimon\Wallet\Transaction;

class WalletObserver
{
    public function saved(Wallet $wallet)
    {
        if ($wallet->getOriginal('balance') != $wallet->balance) {
            // TODO: release a job that recalculates
            // the correct balance asynchronously
            // Action needs to be rebounced to
            // avoid unnecessary executions
        }
    }
}