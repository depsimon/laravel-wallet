<?php

namespace Depsimon\Wallet\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Exception;
use Depsimon\Wallet\Exceptions\UnacceptedTransactionException;

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
     * Move credits to this account
     * @param  integer $amount
     * @param  string  $type
     * @param  array   $meta
     * @return Depsimon\Wallet\Models\Transaction
     */
    public function deposit($amount, $meta = [], $type = 'deposit', $forceFail = false)
    {
        $accepted = $amount >= 0 && !$forceFail ? true : false;

        if (!$this->exists) {
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
        $this->refresh();
        return $transaction;
    }

    /**
     * Fail to move credits to this account
     * @param  integer $amount
     * @param  string  $type
     * @param  array   $meta
     * @return Depsimon\Wallet\Models\Transaction
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
     * @return Depsimon\Wallet\Models\Transaction
     */
    public function withdraw($amount, $meta = [], $type = 'withdraw', $shouldAccept = true)
    {
        $accepted = $shouldAccept ? $this->canWithdraw($amount) : true;

        if (!$this->exists) {
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
        $this->refresh();
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
     * Determine if the user can withdraw the given amount
     * @param  integer $amount
     * @return boolean
     */
    public function canWithdraw($amount = null)
    {
        return $amount ? $this->balance >= abs($amount) : $this->balance > 0;
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
        $undefined = $this->transactions()
            ->whereNotIn('type', array_merge(
                config('wallet.adding_transaction_types'),
                config('wallet.subtracting_transaction_types')
            ))
            ->sum('amount');
        $credits = $this->transactions()
            ->whereIn('type', config('wallet.adding_transaction_types'))
            ->sum(\DB::raw('abs(amount)'));

        $debits = $this->transactions()
            ->whereIn('type', config('wallet.subtracting_transaction_types'))
            ->sum(\DB::raw('abs(amount)'));
        $balance = $undefined + $credits - $debits;

        if ($save) {
            $this->balance = $balance;
            $this->save();
        }
        return $balance;
    }

}