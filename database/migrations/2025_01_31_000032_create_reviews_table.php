<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateReviewsTable extends Migration
{
    public function up()
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->increments('id_review');
            $table->integer('id_compte')->unsigned();
            $table->string('name', 50)->nullable();
            $table->string('description', 100)->nullable();
            $table->timestamps();
            $table->foreign('id_compte')->references('id_compte')->on('compte')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('reviews');
    }
}
