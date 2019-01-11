<?php

namespace Depsimon\Wallet\Observers;

use Depsimon\Wallet\Models\Wallet;
use Depsimon\Wallet\Models\Transaction;
use Depsimon\Wallet\Jobs\RecalculateWalletBalance;

class WalletObserver
{
    public function saved(Wallet $wallet)
    {
        if ($wallet->getOriginal('balance') != $wallet->balance
            && config('auto_recalculate_balance', false)) {
            $job = new RecalculateWalletBalance($wallet);
            dispatch($job);
        }
    }
}