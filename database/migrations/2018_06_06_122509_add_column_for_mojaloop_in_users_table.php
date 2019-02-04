<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddColumnForMojaloopInUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('mojaloop_identifier', 20)->nullable()->after('email');
                $table->string('mojaloop_identifier_type_code')->nullable()->after('mojaloop_identifier');
                $table->string('mojaloop_account_name')->nullable()->after('mojaloop_identifier_type_code');
                $table->string('mojaloop_role_name')->nullable()->after('mojaloop_account_name');
                $table->string('mojaloop_currency_code')->nullable()->after('mojaloop_role_name');
                $table->string('mojaloop_actor_id')->nullable()->after('mojaloop_currency_code');
                $table->string('mojaloop_account_number')->nullable()->after('mojaloop_actor_id');
                $table->string('mojaloop_account_link')->nullable()->after('mojaloop_account_number');
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
        if (Schema::hasTable('users')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn([
                    'mojaloop_identifier',
                    'mojaloop_identifier_type_code',
                    'mojaloop_account_name',
                    'mojaloop_role_name',
                    'mojaloop_currency_code',
                    'mojaloop_actor_id',
                    'mojaloop_account_number',
                    'mojaloop_account_link',
                ]);
            });
        }
    }
}
