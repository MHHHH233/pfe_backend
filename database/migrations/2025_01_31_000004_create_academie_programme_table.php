<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAcademieProgrammeTable extends Migration
{
    public function up()
    {
        Schema::create('academie_programme', function (Blueprint $table) {
            $table->increments('id_programme');
            $table->integer('id_academie')->unsigned()->nullable();
            $table->string('jour', 20)->nullable();
            $table->time('horaire')->nullable();
            $table->string('programme', 100)->nullable();

            $table->foreign('id_academie')->references('id_academie')->on('academie');
        });
    }

    public function down()
    {
        Schema::dropIfExists('academie_programme');
    }
}
