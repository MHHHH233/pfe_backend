<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlayerRequestTable extends Migration
{
    public function up()
    {
        Schema::create('player_request', function (Blueprint $table) {
            $table->increments('id_request');
            $table->integer('sender')->unsigned()->nullable();
            $table->integer('receiver')->unsigned()->nullable();
            $table->date('match_date')->nullable();
            $table->time('starting_time')->nullable();
            $table->string('message', 50)->nullable();
            $table->foreign('sender')->references('id_player')->on('players');
            $table->foreign('receiver')->references('id_player')->on('players');
        });
    }

    public function down()
    {
        Schema::dropIfExists('player_request');
    }
}
