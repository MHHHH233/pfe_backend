<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateActivitesMembersTable extends Migration
{
    public function up()
    {
        Schema::create('activites_members', function (Blueprint $table) {
            $table->increments('id_activity_member');
            $table->unsignedInteger('id_member_ref')->nullable();
            $table->unsignedInteger('id_activites')->nullable();
            $table->timestamp('date_joined')->nullable();
            $table->timestamps();

            $table->foreign('id_member_ref')
                  ->references('id_member')
                  ->on('academie_members')
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
