<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMatchesTable extends Migration
{
    public function up()
    {
        Schema::create('matches', function (Blueprint $table) {
            $table->increments('id_match');
            $table->unsignedInteger('id_tournoi')->nullable();
            $table->unsignedInteger('team1_id')->nullable();
            $table->unsignedInteger('team2_id')->nullable();
            $table->dateTime('match_date')->nullable();
            $table->integer('score_team1')->nullable();
            $table->integer('score_team2')->nullable();
            $table->unsignedInteger('stage')->nullable();
            $table->timestamps();

            $table->foreign('id_tournoi')
                  ->references('id_tournoi')
                  ->on('tournoi')
                  ->onDelete('set null');
            $table->foreign('team1_id')
                  ->references('id_teams')
                  ->on('tournoi_teams')
                  ->onDelete('set null');
            $table->foreign('team2_id')
                  ->references('id_teams')
                  ->on('tournoi_teams')
                  ->onDelete('set null');
            $table->foreign('stage')
                  ->references('id_stage')
                  ->on('stages')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('matches');
    }
} 