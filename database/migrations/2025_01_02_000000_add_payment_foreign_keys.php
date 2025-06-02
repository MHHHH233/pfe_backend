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
        // Add foreign keys after all tables have been created
        Schema::table('payments', function (Blueprint $table) {
            if (Schema::hasTable('reservation') && Schema::hasColumn('reservation', 'id_reservation')) {
                $table->foreign('id_reservation')
                    ->references('id_reservation')
                    ->on('reservation')
                    ->onDelete('cascade');
            }
            
            if (Schema::hasTable('academie') && Schema::hasColumn('academie', 'id_academie')) {
                $table->foreign('id_academie')
                    ->references('id_academie')
                    ->on('academie')
                    ->onDelete('cascade');
            }
            
            if (Schema::hasTable('compte') && Schema::hasColumn('compte', 'id_compte')) {
                $table->foreign('id_compte')
                    ->references('id_compte')
                    ->on('compte')
                    ->onDelete('cascade');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['id_reservation']);
            $table->dropForeign(['id_academie']);
            $table->dropForeign(['id_compte']);
        });
    }
}; 