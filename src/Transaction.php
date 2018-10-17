<?php

namespace Depsimon\Wallet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    protected $table = 'wallet_transactions';

    protected $fillable = [
        'wallet_id', 'amount', 'type', 'meta', 'deleted_at'
    ];

    protected $casts = [
        'meta' => 'json',
    ];

    public function __construct(array $attributes = [])
    {
        $type = config('wallet.column_type');
        if ($type == 'decimal') {
            $this->casts['amount'] = 'float';
        } else if ($type == 'integer') {
            $this->casts['amount'] = 'integer';
        }
        parent::__construct($attributes);
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($transaction) {
            $transaction->hash = uniqid();
        });
    }

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