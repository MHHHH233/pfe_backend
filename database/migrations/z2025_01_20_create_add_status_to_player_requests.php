<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusToPlayerRequests extends Migration
{
    public function up()
    {
        Schema::table('player_request', function (Blueprint $table) {
            $table->string('status')->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->index(['status', 'expires_at']);
        });
    }

    public function down()
    {
        Schema::table('player_request', function (Blueprint $table) {
            $table->dropColumn(['status', 'expires_at']);
        });
    }
} 