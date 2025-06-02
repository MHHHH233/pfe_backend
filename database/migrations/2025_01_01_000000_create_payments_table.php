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
        // Create the payments table
        Schema::create('payments', function (Blueprint $table) {
            $table->id('id_payment');
            $table->string('stripe_payment_intent_id')->nullable();
            $table->string('stripe_payment_method_id')->nullable();
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('pending');
            $table->string('payment_method')->default('stripe');
            $table->string('currency')->default('usd');
            $table->unsignedBigInteger('id_reservation')->nullable();
            $table->unsignedBigInteger('id_academie')->nullable();
            $table->unsignedBigInteger('id_compte');
            $table->text('payment_details')->nullable();
            $table->timestamps();
        });

        // We'll add foreign keys in a separate migration after all tables are created
        // This ensures we don't have dependencies between migrations
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
}; 