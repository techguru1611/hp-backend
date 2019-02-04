<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('from_user_id')->unsigned();
            $table->integer('to_user_id')->unsigned();
            $table->decimal('amount', 15, 5);
            $table->text('description')->nullable();
            $table->string('transaction_id',200);
            $table->tinyInteger('transaction_status')->default(1)->comment('1 - Pending, 2 - Success, 3 - Failed, 4 - Rejected, 5 - Expired');
            $table->tinyInteger('transaction_type')->default(1)->comment('1 - Add Money, 2 - Withdraw Money, 3 - One to one Transaction, 4 - Cash In, 5 - e-voucher, 6 - Redeem, 7 - e-voucher cash out, 8 - Cash Out, 9 - Add commission to wallet, 10 - Withdraw commission');
            $table->tinyInteger('transaction_by')->nullable()->comment('1 - Credit Card, 2 - Debit Card, 3 - Net Banking');
            $table->timestamps();
            $table->softDeletes();
            
            $table->foreign('from_user_id')->references('id')->on('users');
            $table->foreign('to_user_id')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        Schema::dropIfExists('user_transactions');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
