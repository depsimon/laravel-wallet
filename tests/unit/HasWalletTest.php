<?php

namespace Depsimon\Wallet\Tests\Unit;

use Depsimon\Wallet\Wallet;
use Depsimon\Wallet\Tests\TestCase;
use Depsimon\Wallet\Tests\Models\User;
class HasWalletTest extends TestCase
{

    /** @test */
    public function wallet()
    {
        $user = factory(User::class)->create();
        $this->assertInstanceOf(Wallet::class, $user->wallet);
    }

    /** @test */
    public function deposit()
    {
        $user = factory(User::class)->create();
        $this->assertFalse($user->wallet->exists);
        $user->deposit(10);
        $this->assertTrue($user->wallet->exists);
        $this->assertEquals(1, $user->wallet->transactions()->withTrashed()->count());
        $this->assertEquals(1, $user->wallet->transactions->count());
        $this->assertEquals($user->balance, 10);
        $this->assertEquals($user->actualBalance(), 10);
    }

    /** @test */
    public function withdraw()
    {
        $user = factory(User::class)->create();
        $this->assertFalse($user->wallet->exists);
        $this->expectException(\Exception::class);
        $user->withdraw(10);
        $this->assertTrue($user->wallet->exists);
        $user->forceWithdraw(10);
        $this->assertEquals($user->balance, -10);
        $this->assertEquals(1, $user->wallet->transactions->count());
        $this->assertEquals(2, $user->wallet->transactions()->withTrashed()->count());
    }
}
