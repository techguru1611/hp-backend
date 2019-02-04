<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransactionFeesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transaction_fees', function (Blueprint $table) {
            $table->increments('id');
            $table->decimal('start_amount', 15, 5);
            $table->decimal('end_amount', 15, 5);
            $table->decimal('fees_amount', 15, 5)->default(0);
            $table->integer('fees_pecentage')->default(0);
            $table->timestamps();
            $table->softDeletes();
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
        Schema::dropIfExists('transaction_fees');
        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }
}
