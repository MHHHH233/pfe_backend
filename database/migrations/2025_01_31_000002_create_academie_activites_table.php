<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAcademieActivitesTable extends Migration
{
    public function up()
    {
        Schema::create('academie_activites', function (Blueprint $table) {
            $table->increments('id_activites');
            $table->unsignedInteger('id_academie')->nullable();
            $table->string('title', 100)->nullable();
            $table->text('description')->nullable();
            $table->date('date_debut')->nullable();
            $table->date('date_fin')->nullable();
            $table->timestamps();

            $table->foreign('id_academie')
                  ->references('id_academie')
                  ->on('academie')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('academie_activites');
    }
}
