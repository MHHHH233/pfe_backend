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
            $table->unsignedInteger('sender')->nullable();
            $table->unsignedInteger('receiver')->nullable();
            $table->date('match_date')->nullable();
            $table->time('starting_time')->nullable();
            $table->string('message', 50)->nullable();
            $table->timestamps();

            $table->foreign('sender')
                  ->references('id_player')
                  ->on('players')
                  ->onDelete('set null');
            $table->foreign('receiver')
                  ->references('id_player')
                  ->on('players')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('player_request');
    }
}
