<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClickUpDeletedResponsesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('click_up_deleted_responses', function (Blueprint $table) {
            $table->id();
            $table->string('click_up_team_id');
            $table->string('click_up_task_id');
            $table->string('click_up_interval_id');
            $table->json('deleted_response');
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
        // Schema::dropIfExists('click_up_deleted_responses');
    }
}
