<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivitesMembersTable extends Migration
{
    public function up()
    {
        Schema::create('activites_members', function (Blueprint $table) {
            $table->increments('id_member');
            $table->unsignedInteger('id_compte')->nullable();
            $table->unsignedInteger('id_activites')->nullable();
            $table->timestamps();

            $table->foreign('id_compte')
                  ->references('id_compte')
                  ->on('compte')
                  ->onDelete('cascade');
            $table->foreign('id_activites')
                  ->references('id_activites')
                  ->on('academie_activites')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('activites_members');
    }
}
