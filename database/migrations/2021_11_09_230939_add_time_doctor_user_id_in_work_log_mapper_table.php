<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddTimeDoctorUserIdInWorkLogMapperTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('worklog_mappers', function (Blueprint $table) {
            $table->string('time_doctor_user_id')
                ->after('time_doctor_id')
                ->nullable();
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
            $table->dropColumn('time_doctor_user_id');
        });
    }
}
