<?php

namespace Depsimon\Wallet\Tests\Unit;

use Depsimon\Wallet\Wallet;
use Depsimon\Wallet\UnacceptedTransactionException;
use Depsimon\Wallet\Tests\TestCase;
use Depsimon\Wallet\Tests\Models\User;
use Depsimon\Wallet\Transaction;
use Illuminate\Support\Collection;

class WalletTest extends TestCase
{
    /** @test */
    public function delete_and_restore_wallet()
    {
        $user = factory(User::class)->create();
        $transaction = $user->wallet->deposit(10);
        $wallet = $user->wallet;
        $this->assertEquals(1, $wallet->id);
        $this->assertInstanceOf(Wallet::class, $wallet);
        $this->assertEquals(1, Transaction::count());
        $wallet->delete();
        $this->assertEquals(0, Wallet::count());
        $this->assertEquals(0, Transaction::count());
        $wallet->restore();
        $this->assertEquals(1, Wallet::count());
        $this->assertEquals(1, Transaction::count());
    }

    /** @test */
    public function deposit()
    {
        $user = factory(User::class)->create();
        $this->assertFalse($user->wallet->exists);
        $transaction = $user->wallet->deposit(10);
        $this->assertTrue($user->wallet->exists);
        $this->assertTrue($transaction->amount === 10.0);
        $this->assertNotNull($transaction->hash);
        $this->assertEquals(1, $user->wallet->transactions()->withTrashed()->count());
        $this->assertEquals(1, $user->wallet->transactions->count());
        $this->assertEquals($user->balance, 10);
        $this->assertEquals($user->wallet->actualBalance(), 10);
        $user->wallet->deposit(100.75);
        $this->assertEquals($user->balance, 110.75);
        $this->assertEquals($user->wallet->actualBalance(), 110.75);
        $this->expectException(UnacceptedTransactionException::class);
        $transaction = $user->wallet->deposit(-30);
        $this->assertTrue($transaction->trashed());
    }

    /** @test */
    public function fail_deposit()
    {
        $user = factory(User::class)->create();
        $this->assertFalse($user->wallet->exists);
        $transaction = $user->wallet->failDeposit(10000);
        $this->assertTrue($transaction->trashed());
        $this->assertTrue($user->wallet->exists);
        $this->assertEquals(1, $user->wallet->transactions()->withTrashed()->count());
        $this->assertEquals(0, $user->wallet->transactions->count());
    }

    /** @test */
    public function force_withdraw()
    {
        $user = factory(User::class)->create();
        $this->assertFalse($user->wallet->exists);
        $transaction = $user->wallet->forceWithdraw(10000);
        $this->assertTrue($transaction->exists);
        $this->assertTrue($user->wallet->exists);
        $this->assertEquals(1, $user->wallet->transactions()->withTrashed()->count());
        $this->assertEquals(1, $user->wallet->transactions->count());
        $this->assertEquals(-10000, $user->fresh()->balance);
    }

    /** @test */
    public function can_withdraw()
    {
        $user = factory(User::class)->create();
        $this->assertFalse($user->wallet->exists);
        $this->assertFalse($user->wallet->canWithdraw());
        $this->assertFalse($user->wallet->canWithdraw(1));
        $this->assertFalse($user->wallet->canWithdraw(-1));
        $user->wallet->balance = 5;
        $user->wallet->save();
        $this->assertTrue($user->wallet->canWithdraw());
        $this->assertTrue($user->wallet->canWithdraw(3));
        $this->assertTrue($user->wallet->canWithdraw(-5));
        $this->assertFalse($user->wallet->canWithdraw(6));
        $this->assertFalse($user->wallet->canWithdraw(-6));

    }

    /** @test */
    public function withdraw()
    {
        $user = factory(User::class)->create();
        $this->assertFalse($user->wallet->exists);
        $this->expectException(UnacceptedTransactionException::class);
        $user->wallet->withdraw(10);
        $this->assertTrue($user->wallet->exists);
        $user->wallet->forceWithdraw(10);
        $this->assertEquals($user->balance, -10);
        $this->assertEquals($user->wallet->actualBalance(), -10);
        $this->assertEquals(1, $user->wallet->transactions->count());
        $this->assertEquals(2, $user->wallet->walletTransactions()->withTrashed()->count());
    }

    /** @test */
    function set_balance()
    {
        $user = factory(User::class)->create();
        $this->assertEquals(0, $user->balance);
        $offsetTransaction = $user->wallet->setBalance(0);
        $this->assertEquals(null, $offsetTransaction);
        $offsetTransaction = $user->wallet->setBalance(5, 'test');
        $this->assertEquals('deposit', $offsetTransaction->type);
        $this->assertEquals('test', $offsetTransaction->meta['comment']);
        $this->assertEquals(5, $offsetTransaction->amount);
        $this->assertEquals(5, $user->balance);
        $offsetTransaction = $user->wallet->setBalance(15, 'test');
        $this->assertEquals('deposit', $offsetTransaction->type);
        $this->assertEquals('test', $offsetTransaction->meta['comment']);
        $this->assertEquals(10, $offsetTransaction->amount);
        $this->assertEquals(15, $user->balance);
        $offsetTransaction = $user->wallet->setBalance(0);
        $this->assertEquals('withdraw', $offsetTransaction->type);
        $this->assertEquals(15, $offsetTransaction->amount);
        $this->assertEquals(0, $user->balance);
        $this->assertEquals('Manual offset transaction', $offsetTransaction->meta['comment']);
    }

}