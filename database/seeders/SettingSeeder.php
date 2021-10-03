<?php

namespace Database\Seeders;

use App\Models\Settings;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Settings::create([
            'name'          => 'timedoctor',
            'access_token'  => env('TIME_DOCTOR_ACCESS_TOKEN'),
            'refresh_token' => env('TIME_DOCTOR_REFRESH_TOKEN'),
        ]);

        Settings::create([
            'name'          => 'clickup',
            'access_token'  => env('CLICK_UP_ACCESS_TOKEN'),
        ]);
    }
}
