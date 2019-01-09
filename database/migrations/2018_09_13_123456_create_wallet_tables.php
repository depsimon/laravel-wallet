<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateWalletTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $walletModelClass = config('wallet.wallet_model');
        $walletTable = (new $walletModelClass())->getTable();
        if (!Schema::hasTable($walletTable)) {
            Schema::create($walletTable, function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('owner_id')->nullable();
                $table->string('owner_type')->nullable();
                $type = config('wallet.column_type');
                if ($type == 'decimal') {
                    $table->decimal('balance', 12, 4)->default(0); // amount is an decimal, it could be "dollars" or "cents"
                } else if ($type == 'integer') {
                    $table->integer('balance');
                }

                $table->timestamps();
                $table->softDeletes();
            });
        }
        $transactionModelClass = config('wallet.transaction_model');
        $transactionTable = (new $transactionModelClass())->getTable();
        if (!Schema::hasTable($transactionTable)) {
            Schema::create($transactionTable, function (Blueprint $table) {
                $table->increments('id');
                $table->unsignedInteger('wallet_id');

                if (config('wallet.column_type') == 'decimal') {
                    $table->decimal('amount', 12, 4); // amount is an decimal, it could be "dollars" or "cents"
                } else {
                    $table->integer('amount');
                }

                $table->string('hash', 60); // hash is a uniqid for each transaction
                $table->string('type', 30); // type can be anything in your app, by default we use "deposit" and "withdraw"
                $table->json('meta')->nullable(); // Add all kind of meta information you need

                $table->timestamps();
                $table->softDeletes();
                $table->foreign('wallet_id')->references('id')->on('wallets')->onDelete('cascade');
            });
        }


    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $transactionModelClass = config('wallet.transaction_model');
        $transactionTable = (new $transactionModelClass())->getTable();
        Schema::dropIfExists($transactionTable);
        $walletModelClass = config('wallet.transaction_model');
        $walletTable = (new $walletModelClass())->getTable();
        Schema::dropIfExists($walletTable);
    }
}
