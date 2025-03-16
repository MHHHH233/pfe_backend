<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAcademieCoachTable extends Migration
{
    public function up()
    {
        Schema::create('academie_coach', function (Blueprint $table) {
            $table->increments('id_coach');
            $table->unsignedInteger('id_academie')->nullable();
            $table->string('nom', 20)->nullable();
            $table->text('pfp')->nullable();
            $table->text('description')->nullable();
            $table->text('instagram')->nullable();
            $table->timestamps();

            $table->foreign('id_academie')
                  ->references('id_academie')
                  ->on('academie')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('academie_coach');
    }
}
