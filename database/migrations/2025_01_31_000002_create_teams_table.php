<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTeamsTable extends Migration
{
    public function up()
    {
        Schema::create('teams', function (Blueprint $table) {
            $table->increments('id_teams');
            $table->integer('capitain')->unsigned()->nullable();
            $table->integer('total_matches')->nullable();
            $table->integer('rating')->nullable();
            $table->time('starting_time')->nullable();
            $table->time('finishing_time')->nullable();
            $table->integer('misses')->nullable();
            $table->integer('invites_accepted')->nullable();
            $table->integer('invites_refused')->nullable();
            $table->integer('total_invites')->nullable();
            $table->foreign('capitain')->references('id_compte')->on('compte');
        });
    }

    public function down()
    {
        Schema::dropIfExists('teams');
    }
}
