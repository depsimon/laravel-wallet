<?php

namespace Depsimon\Wallet;

use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{

    /**
     * Retrieve all transactions
     */
    public function transactions()
    {
        return $this->hasMany(config('wallet.transaction_model', Transaction::class));
    }

    /**
     * Retrieve owner
     */
    public function user()
    {
        return $this->belongsTo(config('wallet.user_model', \App\User::class));
    }

}