<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTournoiTable extends Migration
{
    public function up()
    {
        Schema::create('tournoi', function (Blueprint $table) {
            $table->increments('id_tournoi');
            $table->string('name');
            $table->text('description')->nullable();
            $table->integer('capacite');
            $table->enum('type', ['5v5', '6v6', '7v7']);
            $table->date('date_debut');
            $table->date('date_fin');
            $table->decimal('frais_entree', 10, 2);
            $table->decimal('award', 10, 2);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('tournoi');
    }
} 