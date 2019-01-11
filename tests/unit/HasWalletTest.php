<?php

namespace Depsimon\Wallet\Tests\Unit;

use Depsimon\Wallet\Models\Wallet;
use Depsimon\Wallet\Exceptions\UnacceptedTransactionException;
use Depsimon\Wallet\Tests\TestCase;
use Depsimon\Wallet\Tests\Models\User;
use Depsimon\Wallet\Models\Transaction;
use Illuminate\Support\Collection;

class HasWalletTest extends TestCase
{
    /** @test */
    public function wallet()
    {
        $user = factory(User::class)->create();
        $this->assertInstanceOf(Wallet::class, $user->wallet);
        $this->assertFalse($user->wallet->exists());
        $this->assertTrue(0.0 === $user->wallet->balance);
    }

    /** @test */
    public function wallet_transactions()
    {
        $user1 = factory(User::class)->create();
        $wallet1 = factory(Wallet::class)->create(['owner_id' => $user1->id]);
        $transactions1 = factory(Transaction::class, 10)->create(['wallet_id' => $wallet1->id]);
        $this->assertInstanceOf(Collection::class, $user1->walletTransactions);
        $this->assertEquals(10, $user1->walletTransactions()->count());
        $this->assertEmpty($wallet1->transactions->diff($user1->walletTransactions));
        $user2 = factory(User::class)->create();
        $wallet2 = factory(Wallet::class)->create(['owner_id' => $user2->id]);
        $transactions2 = factory(Transaction::class, 5)->create(['wallet_id' => $wallet2->id]);
        $this->assertInstanceOf(Collection::class, $user1->walletTransactions);
        $this->assertEquals(10, $user1->walletTransactions()->count());
        $this->assertEmpty($wallet2->transactions->diff($user2->walletTransactions));
        $this->assertInstanceOf(Collection::class, $user2->walletTransactions);
        $this->assertEquals(5, $user2->walletTransactions()->count());
        $this->assertEmpty($wallet2->transactions->diff($user2->walletTransactions));
    }

}
