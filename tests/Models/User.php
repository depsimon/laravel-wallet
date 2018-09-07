<?php

namespace Depsimon\Wallet\Tests\Models;

use Illuminate\Foundation\Auth\User as AuthUser;
use Depsimon\Wallet\HasWallet;

class User extends AuthUser
{
    use HasWallet;
}