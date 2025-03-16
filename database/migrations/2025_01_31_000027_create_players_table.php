<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePlayersTable extends Migration
{
    public function up()
    {
        Schema::create('players', function (Blueprint $table) {
            $table->increments('id_player');
            $table->unsignedInteger('id_compte')->nullable();
            $table->string('position', 50)->nullable();
            $table->integer('total_matches')->nullable();
            $table->integer('rating')->nullable();
            $table->time('starting_time')->nullable();
            $table->time('finishing_time')->nullable();
            $table->integer('misses')->nullable();
            $table->integer('invites_accepted')->nullable();
            $table->integer('invites_refused')->nullable();
            $table->integer('total_invites')->nullable();
            $table->timestamps();

            $table->foreign('id_compte')
                  ->references('id_compte')
                  ->on('compte')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('players');
    }
}
