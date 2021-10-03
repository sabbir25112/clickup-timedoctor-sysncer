<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorklogMappersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('worklog_mappers', function (Blueprint $table) {
            $table->id();
            $table->integer('time_doctor_id')->nullable();
            $table->integer('click_up_id')->nullable();
            $table->json('time_doctor_response')->nullable();
            $table->json('click_up_response')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('worklog_mappers');
    }
}
