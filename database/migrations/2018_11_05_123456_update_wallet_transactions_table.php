<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateWalletTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $transactionModelClass = config('wallet.transaction_model');
        $transactionTable = (new $transactionModelClass())->getTable();
        Schema::table($transactionTable, function (Blueprint $table) use ($transactionTable) {
            $table->integer('origin_id')->unsigned()->nullable();
            $table->foreign('origin_id')->references('id')->on($transactionTable);
        });
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
        if (\DB::getDriverName() !== 'sqlite') {
            Schema::table($transactionTable, function (Blueprint $table) {
                $table->dropForeign(['origin_id']);
            });
        }
        Schema::table($transactionTable, function (Blueprint $table) {
            $table->dropColumn(['origin_id']);
        });

    }
}
