<?php namespace Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::create([
            'name'              => 'Chiheb Ben Aissa',
            'email'             => 'dev@zerda.digital',
            'password'          => bcrypt('admin'),
            'created_at'        => Carbon::now(),
            'updated_at'        => Carbon::now(),
            'email_verified_at' => Carbon::now(),
        ]);
    }
}
