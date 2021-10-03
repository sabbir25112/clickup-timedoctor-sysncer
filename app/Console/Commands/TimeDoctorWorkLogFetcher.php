<?php

namespace App\Console\Commands;

use App\Http\Fetcher\TimeDoctorFetcher;
use App\Http\Syncer\TimeDoctorSyncer;
use App\Logger;
use App\Models\DateSettings;
use Carbon\Carbon;
use Illuminate\Console\Command;

class TimeDoctorWorkLogFetcher extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:time-doctor';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch TimeDoctor WorkLog Data';

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
        // dd(Carbon::createFromTimeString("2021-09-21 07:58:40", 'America/New_York')->format('U'));
//        $task_url_array = explode('/', 'https://app.clickup.com/t/8jjj8z');
//        dd($task_url_array[count($task_url_array) - 1]);
        $users = TimeDoctorFetcher::getUsers();
        if ($users) {
            Logger::verbose("sync time-doctor users into database");

            TimeDoctorSyncer::syncUser($users);

            Logger::verbose("getting dates to pull");
            $dates = $this->getDatesToSync();
            Logger::info(count($dates). " date(s) found to pull");
            foreach ($dates as $date)
            {
                $worklogs = TimeDoctorFetcher::getWorkLog($date->date);
                TimeDoctorSyncer::syncWorkLog($worklogs);
                dd(count($worklogs));
            }
        } else {
            Logger::error("fetching process is stopped, maybe access_token is not working. Because didn't get any user data");
        }
    }

    private function getDatesToSync()
    {
        $dates = DateSettings::where('is_pulled_from_time_doctor', false)->get();
        if (!$dates->count()) {
            $last_date = DateSettings::orderBy('date', 'desc')->first();
            $after_last_date = Carbon::create($last_date->date)->addDay();
            if ($after_last_date->isPast() && !$after_last_date->isToday()) {
                $date = DateSettings::create([
                    'date' => $after_last_date->toDateString()
                ]);

                return [$date];
            }
            return [];
        }
        return $dates;
    }
}
