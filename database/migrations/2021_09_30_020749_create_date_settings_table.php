<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDateSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('date_settings', function (Blueprint $table) {
            $table->id();
            $table->date('date')->unique();
            $table->boolean('is_pulled_from_time_doctor')->default(false);
            $table->boolean('is_pushed_to_click_up')->default(false);
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
        Schema::dropIfExists('date_settings');
    }
}
