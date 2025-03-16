<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnalyticsTable extends Migration
{
    public function up()
    {
        Schema::create('analytics', function (Blueprint $table) {
            $table->increments('analytic_id');
            $table->string('analytic_name', 255);
            $table->float('total');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('analytics');
    }
}
