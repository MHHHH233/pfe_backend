<?php 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAboutusTable extends Migration
{
    public function up()
    {
        Schema::create('aboutus', function (Blueprint $table) {
            $table->string('name', 255)->nullable();
            $table->text('description')->nullable();
        });
    }

    public function down()
    {
        Schema::dropIfExists('aboutus');
    }
}
