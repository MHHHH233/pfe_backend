<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReservationTable extends Migration
{
    public function up()
    {
        Schema::create('reservation', function (Blueprint $table) {
            $table->increments('id_reservation');
            $table->integer('id_client')->unsigned()->nullable();
            $table->unsignedInteger('id_terrain');
            $table->date('date')->nullable();
            $table->time('heure')->nullable();
            $table->enum('etat', ['reserver', 'en attente'])->default('en attente');
            $table->string('Name', 20)->nullable();
            $table->foreign('id_client')->references('id_compte')->on('compte');
            $table->foreign('id_terrain')->references('id_terrain')->on('terrain')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('reservation');
    }
}
