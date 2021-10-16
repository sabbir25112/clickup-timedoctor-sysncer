<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsPushedToClickupFieldIntoWorklogMappersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worklog_mappers', function (Blueprint $table) {
            $table->boolean('synced_with_click_up')
                ->default(false)
                ->after('click_up_response');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('worklog_mappers', function (Blueprint $table) {
            $table->removeColumn('synced_with_click_up');
        });
    }
}
