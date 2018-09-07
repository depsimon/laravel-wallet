<?php

namespace Depsimon\Wallet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    protected $table = 'wallet_transactions';

    protected $fillable = [
        'wallet_id', 'amount', 'hash', 'type', 'meta', 'deleted_at'
    ];

    protected $casts = [
        'amount' => 'float',
        'meta' => 'json',
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