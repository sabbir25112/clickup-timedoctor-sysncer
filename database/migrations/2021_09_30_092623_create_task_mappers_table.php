<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskMappersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('task_mappers', function (Blueprint $table) {
            $table->id();
            $table->integer('time_doctor_task_id')->nullable();
            $table->string('click_up_task_id')->nullable();
            $table->json('time_doctor_response')->nullable();
            $table->json('click_up_response')->nullable();
            $table->string('click_task_url')->nullable();
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
        Schema::dropIfExists('task_mappers');
    }
}
