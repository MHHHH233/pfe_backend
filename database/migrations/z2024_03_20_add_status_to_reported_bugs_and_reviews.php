<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('reported_bug', function (Blueprint $table) {
            $table->enum('status', ['pending', 'in_progress', 'resolved', 'rejected'])->default('pending');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
        });
    }

    public function down()
    {
        Schema::table('reported_bug', function (Blueprint $table) {
            $table->dropColumn('status');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
}; 