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
        Schema::create('social_media', function (Blueprint $table) {
            $table->id();
            $table->string('instagram')->nullable();
            $table->string('facebook')->nullable();
            $table->string('x')->nullable();  // Formerly Twitter
            $table->string('whatsapp')->nullable();
            $table->string('email')->nullable();
            $table->string('localisation')->nullable();
            $table->string('telephone')->nullable();
            $table->text('address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('social_media');
    }
};
