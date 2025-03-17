<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTournoiTeamsTable extends Migration
{
    public function up()
    {
        Schema::create('tournoi_teams', function (Blueprint $table) {
            $table->increments('id_teams');
            $table->unsignedInteger('id_tournoi');
            $table->string('team_name');
            $table->text('descrption')->nullable();
            $table->unsignedInteger('capitain');
            $table->timestamps();

            $table->foreign('id_tournoi')
                  ->references('id_tournoi')
                  ->on('tournoi')
                  ->onDelete('cascade');
            $table->foreign('capitain')
                  ->references('id_compte')
                  ->on('compte')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tournoi_teams');
    }
} 