<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRatingTable extends Migration
{
    public function up()
    {
        Schema::create('rating', function (Blueprint $table) {
            $table->increments('id_rating');
            $table->integer('id_rating_player')->unsigned()->nullable();
            $table->integer('id_rated_player')->unsigned()->nullable();
            $table->unsignedInteger('id_rated_team');
            $table->enum('stars', ['1', '2', '3', '4', '5'])->default('5');
            $table->foreign('id_rating_player')->references('id_player')->on('players');
            $table->foreign('id_rated_player')->references('id_player')->on('players');
            $table->foreign('id_rated_team')->references('id_teams')->on('teams')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('rating');
    }
}
