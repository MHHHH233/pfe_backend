<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStagesTable extends Migration
{
    public function up()
    {
        Schema::create('stages', function (Blueprint $table) {
            $table->increments('id_stage');
            $table->string('stage_name', 50);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('stages');
    }
}
