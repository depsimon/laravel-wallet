<?php

namespace Depsimon\Wallet\Tests\Unit;

use Depsimon\Wallet\Models\Wallet;
use Depsimon\Wallet\Exceptions\UnacceptedTransactionException;
use Depsimon\Wallet\Tests\TestCase;
use Depsimon\Wallet\Tests\Models\User;
use Depsimon\Wallet\Models\Transaction;
use Illuminate\Support\Collection;
use Depsimon\Wallet\Jobs\RecalculateWalletBalance;
use Depsimon\Wallet\DebouncedJob;

class RecalculateWalletBalanceTest extends TestCase
{
    /** @test */
    public function dispatch()
    {
        config(['auto_recalculate_balance' => true]);
        $wallet = factory(Wallet::class)->create();
        Transaction::flushEventListeners();
        $transaction = $wallet->transactions()->make(['type' => 'deposit', 'amount' => 10]);
        $transaction->hash = uniqid();
        $transaction->save();
        $this->assertNotEquals(10, $wallet->balance);
        RecalculateWalletBalance::dispatch($wallet);
        $this->assertEquals(10, $wallet->refresh()->balance);
    }

}

