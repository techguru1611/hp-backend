<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddExternalUserIdCloumnInUserTransactionsTable extends Migration
{
    public function __construct()
    {
        \DB::getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
    }
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('user_transactions', function (Blueprint $table) {
            $table->integer('external_user_id')->unsigned()->nullable()->after('to_user_id');

            $table->foreign('external_user_id')->references('id')->on('external_users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_transactions', function (Blueprint $table) {
            $table->dropForeign('user_transactions_external_user_id_foreign');
            $table->dropColumn(['external_user_id']);
        });
    }
}
