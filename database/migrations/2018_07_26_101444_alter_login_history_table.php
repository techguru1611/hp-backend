<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterLoginHistoryTable extends Migration
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
        if (Schema::hasTable('user_login_history')) {
            Schema::table('user_login_history', function (Blueprint $table) {
                $table->decimal('latitude', 10, 8)->nullable()->change();
                $table->decimal('longitude', 11, 8)->nullable()->change();
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
        if (Schema::hasTable('user_login_history')) {
            Schema::table('user_login_history', function (Blueprint $table) {
                $table->float('latitude', 10, 6)->nullable()->change();
                $table->float('longitude', 10, 6)->nullable()->change();
            });
        }
    }
}
