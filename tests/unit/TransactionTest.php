<?php

namespace Depsimon\Wallet\Tests\Unit;

use Depsimon\Wallet\Models\Wallet;
use Depsimon\Wallet\Exceptions\UnacceptedTransactionException;
use Depsimon\Wallet\Tests\TestCase;
use Depsimon\Wallet\Tests\Models\User;
use Depsimon\Wallet\Models\Transaction;
use Illuminate\Support\Collection;

class TransactionTest extends TestCase
{

    /** @test */
    public function wallet()
    {
        $transaction = factory(Transaction::class)->create();
        $this->assertInstanceOf(Wallet::class, $transaction->wallet);
    }

    /** @test */
    public function origin()
    {
        $origin = factory(Transaction::class)->create();
        $transaction = factory(Transaction::class)->create();
        $transaction->origin()->associate($origin);
        $transaction->save();
        $transaction = $transaction->fresh();
        $this->assertInstanceOf(Transaction::class, $transaction->origin);
        $this->assertTrue($origin->is($transaction->origin));
    }

    /** @test */
    public function children()
    {
        $origin = factory(Transaction::class)->create();
        $transaction = factory(Transaction::class)->create();
        $origin->children()->save($transaction);
        $this->assertInstanceOf(Collection::class, $transaction->children);
        $child = $origin->children()->where('id', $transaction->id)->first();
        $this->assertTrue($transaction->is($child));
        $this->assertTrue($origin->is($transaction->origin));
    }


    /** @test */
    public function update()
    {
        $transaction = factory(Transaction::class)->create(['amount' => 20, 'type' => 'deposit']);
        $this->assertEquals(20, $transaction->wallet->balance);
        $transaction->update(['amount' => 100]);
        $this->assertEquals(100, $transaction->wallet->refresh()->balance);
        $transaction->update(['amount' => 20]);
        $this->assertEquals(20, $transaction->wallet->refresh()->balance);
        $transaction->update(['amount' => -20]);
        $this->assertEquals(20, $transaction->wallet->refresh()->balance);
        $transaction->update(['amount' => -20, 'type' => 'withdraw']);
        $this->assertEquals(-20, $transaction->wallet->refresh()->balance);
    }

    /** @test */
    public function create_converts_amount_to_absolute_value()
    {
        $wallet = factory(Wallet::class)->create();
        $transaction = $wallet->transactions()->create(['type' => 'withdraw', 'amount' => -20]);
        $this->assertEquals(20, $transaction->getAttributes()['amount']);
    }

    /** @test */
    public function delete()
    {
        $transaction = factory(Transaction::class)->create(['amount' => 20, 'type' => 'deposit']);
        $this->assertEquals(20, $transaction->wallet->refresh()->balance);
        $transaction->delete();
        $this->assertTrue($transaction->trashed());
        $this->assertEquals(0, $transaction->wallet->refresh()->balance);
        $transaction = factory(Transaction::class)->create(['amount' => 20, 'type' => 'withdraw']);
        $this->assertEquals(-20, $transaction->wallet->refresh()->balance);
    }

    /** @test */
    public function replace()
    {
        $timestamp = now()->subHours(1);
        $transaction = factory(Transaction::class)->create([
            'amount' => 20,
            'type' => 'deposit',
            'created_at' => $timestamp,
            'updated_at' => $timestamp
        ]);
        $this->assertTrue($timestamp->diffInSeconds($transaction->refresh()->updated_at) < 1);
        $this->assertEquals(20, $transaction->wallet->refresh()->balance);
        $replacement = $transaction->replace(['amount' => 100]);
        $this->assertEquals(100, $transaction->wallet->refresh()->balance);
        $this->assertEquals(2, Transaction::withTrashed()->count());
        $this->assertTrue($transaction->refresh()->created_at->diffInSeconds($replacement->refresh()->created_at) < 1);
        $this->assertTrue($transaction->is($replacement->origin));
        $this->assertTrue($replacement->origin->trashed());
    }

}

