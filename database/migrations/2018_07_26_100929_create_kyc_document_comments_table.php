<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateKycDocumentCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kyc_document_comments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('kyc_id')->unsigned();
            $table->integer('notes_by')->unsigned();
            $table->text('notes');

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('notes_by')->references('id')->on('users');
            $table->foreign('kyc_id')->references('id')->on('kyc_documents');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kyc_document_comments', function (Blueprint $table) {
            $table->dropForeign('kyc_document_comments_notes_by_foreign');
            $table->dropForeign('kyc_document_comments_kyc_id_foreign');
        });
        Schema::dropIfExists('kyc_document_comments');
    }
}
