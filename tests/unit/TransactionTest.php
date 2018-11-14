<?php

namespace Depsimon\Wallet\Tests\Unit;

use Depsimon\Wallet\Wallet;
use Depsimon\Wallet\UnacceptedTransactionException;
use Depsimon\Wallet\Tests\TestCase;
use Depsimon\Wallet\Tests\Models\User;
use Depsimon\Wallet\Transaction;
use Illuminate\Support\Collection;

class TransactionTest extends TestCase
{

    /** @test */
    public function wallet()
    {
        $transaction = factory(Transaction::class)->create();
        $this->assertInstanceOf(Wallet::class, $transaction->wallet);
    }
}
