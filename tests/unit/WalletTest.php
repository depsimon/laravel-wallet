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
    public function owner()
    {
        $wallet = factory(Wallet::class)->create();
        $this->assertInstanceOf(User::class, $wallet->owner);
    }

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
        $this->assertFalse($wallet->exists());
        $this->assertTrue($wallet->trashed());
        $this->assertEquals(0, Wallet::count());
        $this->assertEquals(1, Transaction::count());
        $wallet->deposit(10);
        $this->assertEquals(20, $wallet->refresh()->balance);
        $wallet->restore();
        $this->assertTrue($wallet->exists());
        $this->assertFalse($wallet->trashed());
        $this->assertEquals(1, Wallet::count());
        $this->assertEquals(2, Transaction::count());
    }

    /** @test */
    public function deposit()
    {
        $user = factory(User::class)->create();
        $this->assertFalse($user->wallet->exists());
        $transaction = $user->wallet->deposit(10);
        $this->assertTrue($user->wallet->exists());
        $this->assertEquals($transaction->amount, 10);
        $this->assertNotNull($transaction->hash);
        $this->assertEquals(1, $user->wallet->transactions()->count());
        $this->assertEquals(10, $user->balance);
        $this->assertEquals(10, $user->wallet->actualBalance());
        $user->wallet->deposit(100.75);
        $this->assertEquals(110.75, $user->balance);
        $this->assertEquals(110.75, $user->wallet->actualBalance());
        $user->wallet->setBalance(-50);
        $this->assertEquals(-50, $user->wallet->balance);
        $user->wallet->deposit(25);
        $this->assertEquals(-25, $user->wallet->balance);
    }


    /** @test */
    public function deposit_negative_amount()
    {
        $user = factory(User::class)->create();
        $this->assertFalse($user->wallet->exists);
        $transaction = $user->wallet->failDeposit(-30);
        $this->assertTrue($transaction->trashed());
        $this->expectException(UnacceptedTransactionException::class);
        $transaction = $user->wallet->deposit(-30);
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
        $offsetTransaction = $user->wallet->setBalance(15.45, 'test');
        $this->assertEquals('deposit', $offsetTransaction->type);
        $this->assertEquals('test', $offsetTransaction->meta['comment']);
        $this->assertEquals(10.45, $offsetTransaction->amount);
        $this->assertEquals(15.45, $user->balance);
        $offsetTransaction = $user->wallet->setBalance(0);
        $this->assertEquals('withdraw', $offsetTransaction->type);
        $this->assertEquals(-15.45, $offsetTransaction->amount);
        $this->assertEquals(0, $user->balance);
        $this->assertEquals('Manual offset transaction', $offsetTransaction->meta['comment']);
    }

    /** @test */
    function actual_balance()
    {
        $user = factory(User::class)->create();
        $transactions = factory(Transaction::class, 10)->create(['amount' => 50, 'type' => 'deposit']);
        $transactions = factory(Transaction::class, 10)->create(['amount' => 25, 'type' => 'withdraw']);#
        $expectedBalance = 10 * 50 - 10 * 25;
        $this->assertEquals($expectedBalance, $user->balance);
        $this->assertEquals($expectedBalance, $user->wallet->balance);
        $this->assertEquals($expectedBalance, $user->wallet->actualBalance());
    }

    /** @test */
    function test_recalculation_performance()
    {
        $user = factory(User::class)->create();
        Transaction::flushEventListeners();
        $numbers = [1, 10, 100, 1000, 10000];
        $result = collect($numbers)->mapWithKeys(function ($number) use ($user) {
            $alreadyExist = Transaction::count();
            $transactions = factory(Transaction::class, $number - $alreadyExist)->create();
            $start = microtime(true);
            $actualBalance = $user->wallet->actualBalance();
            return [$number => microtime(true) - $start];
        });
        $this->assertTrue(true);
        \Log::info($result);
    }

}