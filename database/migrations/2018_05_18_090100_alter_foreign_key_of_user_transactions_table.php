<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterForeignKeyOfUserTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('user_transactions')) {
            Schema::table('user_transactions', function (Blueprint $table) {
                $table->integer('from_user_id')->unsigned()->nullable()->change();
                $table->integer('to_user_id')->unsigned()->nullable()->change();
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
        if (Schema::hasTable('user_transactions')) {
            Schema::table('user_transactions', function (Blueprint $table) {
                $table->integer('from_user_id')->unsigned()->nullable(false)->change();
                $table->integer('to_user_id')->unsigned()->nullable(false)->change();
            });
        }
    }
}
