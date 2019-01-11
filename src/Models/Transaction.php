<?php

namespace Depsimon\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    protected $table = 'wallet_transactions';

    protected $attributes = [
        'meta' => '{}',
    ];

    protected $fillable = [
        'wallet_id', 'amount', 'type', 'meta', 'deleted_at'
    ];

    protected $casts = [
        'meta' => 'array',
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

    /**
     * Retrieve the wallet from this transaction
     */
    public function wallet()
    {
        return $this->belongsTo(config('wallet.wallet_model', Wallet::class))->withTrashed();
    }

    /**
     * Retrieve the original version of the transaciton (if it has been replaced)
     */
    public function origin()
    {
        return $this->belongsTo(config('wallet.transaction_model', Transaction::class))->withTrashed();
    }

    /**
     * Creates a replication and updates it with the new
     * attributes, adds the old as origin relation
     * and then soft deletes the old.
     * Be careful if the old transaction was referenced
     * by other models.
     */
    public function replace($attributes)
    {
        return \DB::transaction(function () use ($attributes) {
            $newTransaction = $this->replicate();
            $newTransaction->created_at = $this->created_at;
            $newTransaction->fill($attributes);
            $newTransaction->origin()->associate($this);
            $newTransaction->save();
            $this->delete();
            return $newTransaction;
        });
    }

    public function getAmountAttribute()
    {
        return $this->getAmountWithSign();
    }

    public function setAmountAttribute($amount)
    {
        if ($this->shouldConvertToAbsoluteAmount()) {
            $amount = abs($amount);
        }
        $this->attributes['amount'] = ($amount);
    }

    public function getAmountWithSign($amount = null, $type = null)
    {
        $amount = $amount ? : $this->attributes['amount'];
        $type = $type ? : $this->type;
        $amount = $this->shouldConvertToAbsoluteAmount() ? abs($amount) : $amount;
        if (in_array($type, config('wallet.subtracting_transaction_types', []))) {
            return $amount * -1;
        }
        return $amount;
    }

    public function shouldConvertToAbsoluteAmount($type = null)
    {
        $type = $type ? : $this->type;
        return in_array($type, config('wallet.subtracting_transaction_types', [])) ||
            in_array($type, config('wallet.adding_transaction_types', []));
    }

}