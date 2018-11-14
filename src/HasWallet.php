<?php

namespace Depsimon\Wallet;

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

}
