<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTerrainTable extends Migration
{
    public function up()
    {
        Schema::create('terrain', function (Blueprint $table) {
            $table->increments('id_terrain');
            $table->string('nom_terrain', 100)->nullable();
            $table->enum('capacite', ['5v5', '6v6', '7v7'])->nullable();
            $table->enum('type', ['indoor', 'outdoor'])->nullable();
            $table->float('prix');
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('terrain');
    }
}
