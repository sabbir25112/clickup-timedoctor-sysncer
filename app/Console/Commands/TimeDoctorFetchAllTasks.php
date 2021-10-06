<?php

namespace App\Console\Commands;

use App\Http\Fetcher\TimeDoctorFetcher;
use App\Http\Syncer\TimeDoctorSyncer;
use App\Logger;
use App\Models\UserMapper;
use Illuminate\Console\Command;

class TimeDoctorFetchAllTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:time-doctor-tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $users = TimeDoctorFetcher::getUsers();
        if ($users) {
            TimeDoctorSyncer::syncUser($users);
            $timeDoctorUsers = UserMapper::whereNotNull('time_doctor_user_id')->get();

            foreach ($timeDoctorUsers as $index => $user)
            {
                $userId = $user['time_doctor_user_id'];
                $tasks = TimeDoctorFetcher::getTasks($userId);
                Logger::verbose("$userId has " . count($tasks) . " tasks");
                if ($index > 2) break;
            }
        }

        return 0;
    }
}
