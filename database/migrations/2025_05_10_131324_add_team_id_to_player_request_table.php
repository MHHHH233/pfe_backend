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
        Schema::table('player_request', function (Blueprint $table) {
            // Add team_id column if it doesn't exist
            if (!Schema::hasColumn('player_request', 'team_id')) {
                $table->unsignedBigInteger('team_id')->nullable();
            }
            
            // Add request_type column if it doesn't exist
            if (!Schema::hasColumn('player_request', 'request_type')) {
                $table->string('request_type')->default('match');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('player_request', function (Blueprint $table) {
            if (Schema::hasColumn('player_request', 'team_id')) {
                $table->dropColumn('team_id');
            }
            
            if (Schema::hasColumn('player_request', 'request_type')) {
                $table->dropColumn('request_type');
            }
        });
    }
};
