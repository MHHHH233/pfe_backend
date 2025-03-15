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
            $table->string('name', 50)->nullable();
            $table->string('description', 100)->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('reviews');
    }
}
