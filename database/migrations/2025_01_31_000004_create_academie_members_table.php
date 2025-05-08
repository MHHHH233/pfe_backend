<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAcademieMembersTable extends Migration
{
    public function up()
    {
        Schema::create('academie_members', function (Blueprint $table) {
            $table->increments('id_member');
            $table->unsignedInteger('id_compte')->nullable();
            $table->unsignedInteger('id_academie')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->enum('subscription_plan', ['base', 'premium'])->default('base');
            $table->timestamp('date_joined')->nullable();
            $table->timestamps();

            $table->foreign('id_compte')
                  ->references('id_compte')
                  ->on('compte')
                  ->onDelete('cascade');
            $table->foreign('id_academie')
                  ->references('id_academie')
                  ->on('academie')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('academie_members');
    }
} 