<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class RecreateAuditTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //
        Schema::dropIfExists('audit_transactions');
        Schema::create('audit_transactions', function (Blueprint $table) {
            $table->increments('id');

            $table->integer('transaction_type_id')->unsigned();
            $table->dateTime('transaction_date')->nullable();
            $table->integer('transaction_user')->unsigned();
            $table->integer('action_model_id')->unsigned()->comment('PK of current create/updated/deleted/restored table record.');
            $table->text('action_detail')->nullable()->comment('Action detail will hold the table row as JSON string.');

            $table->string('url', 255)->nullable();
            $table->string('ip_address', 50)->nullable();
            $table->string('user_agent', 150)->nullable();

            $table->timestamps();

            $table->foreign('transaction_user')->references('id')->on('users');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('audit_transactions', function (Blueprint $table) {
            $table->dropForeign('audit_transactions_transaction_user_foreign');
        });
        Schema::dropIfExists('audit_transactions');
    }
}
