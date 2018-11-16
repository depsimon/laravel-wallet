<?php

namespace Depsimon\Wallet;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Exception;

class Wallet extends Model
{
    use SoftDeletes;

    protected $attributes = [
        'balance' => 0,
    ];

    public function __construct(array $attributes = [])
    {
        $type = config('wallet.column_type');
        if ($type == 'decimal') {
            $this->casts['balance'] = 'float';
        } else if ($type == 'integer') {
            $this->casts['balance'] = 'integer';
        }
        parent::__construct($attributes);
    }

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
    public function owner()
    {
        return $this->morphTo();
    }


    /**
     * Determine if the user can withdraw the given amount
     * @param  integer $amount
     * @return boolean
     */
    public function canWithdraw($amount = null)
    {
        return $amount ? $this->balance >= abs($amount) : $this->balance > 0;
    }


    /**
     * Move credits to this account
     * @param  integer $amount
     * @param  string  $type
     * @param  array   $meta
     * @return Depsimon\Wallet\Transaction
     */
    public function deposit($amount, $meta = [], $type = 'deposit', $forceFail = false)
    {
        $accepted = $amount >= 0 && !$forceFail ? true : false;

        if ($accepted) {
            $this->balance = $this->actualBalance() + $amount;
            $this->save();
        } elseif (!$this->exists) {
            $this->save();
        }

        $transaction = $this->transactions()
            ->create([
                'amount' => $amount,
                'type' => $type,
                'meta' => $meta,
                'deleted_at' => $accepted ? null : Carbon::now(),
            ]);

        if (!$accepted && !$forceFail) {
            throw new UnacceptedTransactionException($transaction, 'Deposit not accepted!');
        }
        return $transaction;
    }

    /**
     * Fail to move credits to this account
     * @param  integer $amount
     * @param  string  $type
     * @param  array   $meta
     * @return Depsimon\Wallet\Transaction
     */
    public function failDeposit($amount, $meta = [], $type = 'deposit')
    {
        return $this->deposit($amount, $meta, $type, true);
    }

    /**
     * Attempt to move credits from this account
     * @param  integer $amount Only the absolute value will be considered
     * @param  string  $type
     * @param  array   $meta
     * @param  boolean $shouldAccept
     * @return Depsimon\Wallet\Transaction
     */
    public function withdraw($amount, $meta = [], $type = 'withdraw', $shouldAccept = true)
    {
        $amount = abs($amount);
        $accepted = $shouldAccept ? $this->canWithdraw($amount) : true;

        if ($accepted) {
            $this->balance = $this->actualBalance() - $amount;
            $this->save();
        } elseif (!$this->exists) {
            $this->save();
        }

        $transaction = $this->transactions()
            ->create([
                'amount' => $amount,
                'type' => $type,
                'meta' => $meta,
                'deleted_at' => $accepted ? null : Carbon::now(),
            ]);

        if (!$accepted) {
            throw new UnacceptedTransactionException($transaction, 'Withdrawal not accepted due to insufficient funds!');
        }
        return $transaction;
    }

    /**
     * Move credits from this account
     * @param  integer $amount
     * @param  string  $type
     * @param  array   $meta
     */
    public function forceWithdraw($amount, $meta = [], $type = 'withdraw')
    {
        return $this->withdraw($amount, $meta, $type, false);
    }

    /**
     * Set wallet balance to desired value.
     * Will automatically create the necessary transaction
     * @param integer $balance
     * @param string $comment
     */
    public function setBalance($amount, $comment = 'Manual offset transaction')
    {
        $actualBalance = $this->actualBalance();
        $difference = $amount - $actualBalance;
        if ($difference == 0) {
            return;
        };
        $type = $difference > 0 ? 'deposit' : 'forceWithdraw';
        $this->balance = $actualBalance;
        $this->save();
        return $this->{$type}($difference, ['comment' => $comment]);
    }

    /**
     * Returns the actual balance for this wallet.
     * Might be different from the balance property if the database is manipulated
     * @return float balance
     */
    public function actualBalance(bool $save = false)
    {
        $credits = $this->transactions()
            ->whereIn('type', ['deposit', 'refund'])
            ->sum('amount');

        $debits = $this->transactions()
            ->whereIn('type', ['withdraw', 'payout'])
            ->sum('amount');

        $balance = $credits - $debits;

        if ($save) {
            $this->balance = $balance;
            $this->save();
        }
        return $balance;
    }

}