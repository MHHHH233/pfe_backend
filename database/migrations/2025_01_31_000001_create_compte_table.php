<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompteTable extends Migration
{
    public function up()
    {
        Schema::create('compte', function (Blueprint $table) {
            $table->increments('id_compte');
            // ...existing code...
        });
    }

    public function down()
    {
        Schema::dropIfExists('compte');
    }
}
