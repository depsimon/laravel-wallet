<?php

namespace Depsimon\Wallet;

use Exception;
use Illuminate\Support\Carbon;

trait HasWallet
{
    /**
     * Retrieve the balance of this user's wallet
     */
    public function getBalanceAttribute()
    {
        return $this->wallet->balance;
    }

    /**
     * Retrieve the wallet of this user
     */
    public function wallet()
    {
        return $this->morphOne(config('wallet.wallet_model', Wallet::class), 'owner')->withDefault();
    }

    /**
     * Retrieve all transactions of this user
     */
    public function walletTransactions()
    {
        return $this->hasManyThrough(
            config('wallet.transaction_model', Transaction::class),
            config('wallet.wallet_model', Wallet::class),
            'owner_id',
            'wallet_id'
        )->latest();
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
            $this->wallet->balance += $amount;
            $this->wallet->save();
        } elseif (!$this->wallet->exists) {
            $this->wallet->save();
        }

        $transaction = $this->wallet->transactions()
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
            $this->wallet->balance -= $amount;
            $this->wallet->save();
        } elseif (!$this->wallet->exists) {
            $this->wallet->save();
        }

        $transaction = $this->wallet->transactions()
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
     * Returns the actual balance for this wallet.
     * Might be different from the balance property if the database is manipulated
     * @return float balance
     */
    public function actualBalance()
    {
        $credits = $this->wallet->transactions()
            ->whereIn('type', ['deposit', 'refund'])
            ->sum('amount');

        $debits = $this->wallet->transactions()
            ->whereIn('type', ['withdraw', 'payout'])
            ->sum('amount');

        return $credits - $debits;
    }
}
