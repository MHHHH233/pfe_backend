<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreateAcademieTable extends Migration
{
    public function up()
    {
        Schema::create('academie', function (Blueprint $table) {
            $table->increments('id_academie');
            $table->string('nom', 50)->nullable();
            $table->text('description')->nullable();
            $table->date('date_creation')->default(DB::raw('CURRENT_DATE'));
            $table->float('plan_base')->nullable();
            $table->float('plan_premium')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('academie');
    }
}
