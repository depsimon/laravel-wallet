<?php

namespace Depsimon\Wallet\Tests\Unit;

use Depsimon\Wallet\Wallet;
use Depsimon\Wallet\UnacceptedTransactionException;
use Depsimon\Wallet\Tests\TestCase;
use Depsimon\Wallet\Tests\Models\User;
use Depsimon\Wallet\Transaction;
use Illuminate\Support\Collection;

class HasWalletTest extends TestCase
{
    /** @test */
    public function wallet()
    {
        $user = factory(User::class)->create();
        $this->assertInstanceOf(Wallet::class, $user->wallet);
        $this->assertFalse($user->wallet->exists());
        $user = factory(User::class)->create();
        $this->assertTrue(0.0 === $user->wallet->balance);
    }

    /** @test */
    public function transactions()
    {
        $user1 = factory(User::class)->create();
        $wallet1 = factory(Wallet::class)->create(['owner_id' => $user1->id]);
        $transactions1 = factory(Transaction::class, 10)->create(['wallet_id' => $wallet1->id]);
        $this->assertInstanceOf(Collection::class, $user1->transactions);
        $this->assertEquals(10, $user1->transactions()->count());
        $this->assertEmpty($wallet1->transactions->diff($user1->transactions));
        $user2 = factory(User::class)->create();
        $wallet2 = factory(Wallet::class)->create(['owner_id' => $user2->id]);
        $transactions2 = factory(Transaction::class, 5)->create(['wallet_id' => $wallet2->id]);
        $this->assertInstanceOf(Collection::class, $user1->transactions);
        $this->assertEquals(10, $user1->transactions()->count());
        $this->assertEmpty($wallet2->transactions->diff($user2->transactions));
        $this->assertInstanceOf(Collection::class, $user2->transactions);
        $this->assertEquals(5, $user2->transactions()->count());
        $this->assertEmpty($wallet2->transactions->diff($user2->transactions));
    }


    /** @test */
    public function deposit()
    {
        $user = factory(User::class)->create();
        $this->assertFalse($user->wallet->exists);
        $transaction = $user->deposit(10);
        $this->assertTrue($user->wallet->exists);
        $this->assertTrue($transaction->amount === 10.0);
        $this->assertNotNull($transaction->hash);
        $this->assertEquals(1, $user->wallet->transactions()->withTrashed()->count());
        $this->assertEquals(1, $user->wallet->transactions->count());
        $this->assertEquals($user->balance, 10);
        $this->assertEquals($user->actualBalance(), 10);
        $user->deposit(100.75);
        $this->assertEquals($user->balance, 110.75);
        $this->assertEquals($user->actualBalance(), 110.75);
        $this->expectException(UnacceptedTransactionException::class);
        $transaction = $user->deposit(-30);
        $this->assertTrue($transaction->trashed());
    }

    /** @test */
    public function fail_deposit()
    {
        $user = factory(User::class)->create();
        $this->assertFalse($user->wallet->exists);
        $transaction = $user->failDeposit(10000);
        $this->assertTrue($transaction->trashed());
        $this->assertTrue($user->wallet->exists);
        $this->assertEquals(1, $user->wallet->transactions()->withTrashed()->count());
        $this->assertEquals(0, $user->wallet->transactions->count());
    }

    /** @test */
    public function withdraw()
    {
        $user = factory(User::class)->create();
        $this->assertFalse($user->wallet->exists);
        $this->expectException(UnacceptedTransactionException::class);
        $user->withdraw(10);
        $this->assertTrue($user->wallet->exists);
        $user->forceWithdraw(10);
        $this->assertEquals($user->balance, -10);
        $this->assertEquals($user->actualBalance(), -10);
        $this->assertEquals(1, $user->wallet->transactions->count());
        $this->assertEquals(2, $user->wallet->transactions()->withTrashed()->count());
    }
}
