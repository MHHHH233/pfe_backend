<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCacheLocksTable extends Migration
{
    public function up()
    {
        Schema::create('cache_locks', function (Blueprint $table) {
            $table->string('key', 255)->primary();
            $table->string('owner', 255);
            $table->integer('expiration');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cache_locks');
    }
}
