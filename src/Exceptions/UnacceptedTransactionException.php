<?php

namespace Depsimon\Wallet\Exceptions;

use Exception;
use Depsimon\Wallet\Models\Transaction;

class UnacceptedTransactionException extends Exception
{
    protected $transaction;

    public function __construct(Transaction $transaction, $message = 'The transaction has not been accepted')
    {
        parent::__construct($message);
        $this->transaction = $transaction;
    }

    public function getTransaction()
    {
        return $this->transaction;
    }
}