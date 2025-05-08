<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('player_team', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('id_player');
            $table->unsignedInteger('id_teams');
            $table->timestamps();

            $table->foreign('id_player')
                  ->references('id_player')
                  ->on('players')
                  ->onDelete('cascade');
            
            $table->foreign('id_teams')
                  ->references('id_teams')
                  ->on('teams')
                  ->onDelete('cascade');

            $table->unique(['id_player', 'id_teams']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('player_team');
    }
};
