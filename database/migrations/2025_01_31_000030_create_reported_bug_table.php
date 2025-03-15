<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReportedBugTable extends Migration
{
    public function up()
    {
        Schema::create('reported_bug', function (Blueprint $table) {
            $table->increments('id_bug');
            $table->integer('id_compte')->unsigned()->nullable();
            $table->text('description')->nullable();
            $table->foreign('id_compte')->references('id_compte')->on('compte');
        });
    }

    public function down()
    {
        Schema::dropIfExists('reported_bug');
    }
}
