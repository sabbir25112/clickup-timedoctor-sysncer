<?php

namespace App\Console\Commands;

use App\Http\Fetcher\TimeDoctorFetcher;
use App\Http\Syncer\TimeDoctorSyncer;
use App\Logger;
use App\Models\DateSettings;
use App\Models\Settings;
use App\Models\WorklogMapper;
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
//        $date = Carbon::create(2021, 6, 1);
//        while(!$date->isToday()) {
//            $setting = DateSettings::where('date', $date->toDateString())->first();
//            if (!$setting) {
//                Logger::verbose("Creating " . $date->toDateString());
//                DateSettings::create([
//                    'date' => $date->toDateString(),
//                ]);
//            }
//            $date = $date->addDay(1);
//        }
//        dd("DONE");

        $is_successful = TimeDoctorFetcher::setAccessToken(Settings::timedoctor());
        if (!$is_successful) {
            Logger::error("TimeDoctor AccessToken Can't Generate");
            return 0;
        }

        $call_count = 0;
        Logger::verbose("getting dates to pull");
        $dates = $this->getDatesToSync();
        Logger::info(count($dates). " date(s) found to pull");
        foreach ($dates as $date)
        {
            Logger::verbose("Pulling Data For " . $date->date);
            $worklogs = TimeDoctorFetcher::getWorkLog($date->date);
            Logger::verbose($worklogs['call_count'] . " Call(s) have been made to get the WorkLog");
            $call_count += $worklogs['call_count'];
            Logger::verbose("CallCount: $call_count");
            Logger::verbose("Syncing Data to DB");
            TimeDoctorSyncer::storeWorkLogIntoDB($worklogs['worklog']);
            $date->update(['is_pulled_from_time_doctor' => true]);
            if ($call_count > 80) {
                $call_count = 0;
                Logger::verbose("CallCount Reset, Sleep for 30 seconds");
                sleep(30);
            }
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

                return collect($date);
            }
            return collect([]);
        }
        return $dates;
    }
}
