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
            $table->string('nom')->default('user');
            $table->string('prenom');
            $table->string('age');
            $table->string('email')->unique();
            $table->string('password');
            $table->enum('role', ['user', 'admin'])->default('user');
            $table->string('pfp');
            $table->string('telephone');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('compte');
    }
}
