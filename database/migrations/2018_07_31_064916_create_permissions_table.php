<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePermissionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->increments('id');
            
            $table->string('name', 100);
            $table->string('slug', 100)->unique();

            $table->integer('created_by')->unsigned()->nullable();
            $table->integer('updated_by')->unsigned()->nullable();
            
            $table->timestamps();            
            $table->softDeletes();

            $table->foreign('created_by')->references('id')->on('users');       
            $table->foreign('updated_by')->references('id')->on('users');       
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('permissions', function (Blueprint $table) {
            $table->dropForeign('role_permissions_created_by_foreign');
            $table->dropForeign('role_permissions_updated_by_foreign');
        });
        Schema::dropIfExists('permissions');
    }
}
