<?php

namespace Depsimon\Wallet;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $table = 'wallet_transactions';

    protected $fillable = [
        'wallet_id', 'amount', 'hash', 'type', 'accepted', 'meta'
    ];

    protected $casts = [
        'amount' => 'float',
        'meta' => 'json'
    ];

    /**
     * Retrieve the wallet from this transaction
     */
    public function wallet()
    {
        return $this->belongsTo(config('wallet.wallet_model', Wallet::class));
    }

    /**
     * Retrieve the amount with the positive or negative sign
     */
    public function getAmountWithSignAttribute()
    {
        return in_array($this->type, ['deposit', 'refund'])
            ? '+' . $this->amount
            : '-' . $this->amount;
    }

}