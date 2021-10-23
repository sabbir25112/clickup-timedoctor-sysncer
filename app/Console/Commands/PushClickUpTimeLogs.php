<?php

namespace App\Console\Commands;

use App\Http\Fetcher\TimeDoctorFetcher;
use App\Logger;
use App\Models\Settings;
use App\Models\WorklogMapper;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class PushClickUpTimeLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'push:clickUp-time-logs';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Push TimeLogs to ClickUp';

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
        $settings = Settings::clickup();
        $access_token = $settings->access_token;
        $teamId = env('CLICK_UP_TEAM_ID');
        $workLogs = WorklogMapper::where('synced_with_click_up', false)->get();
        foreach ($workLogs as $workLog)
        {
            $task = TimeDoctorFetcher::getTaskFromWorkLog($workLog);
            $user = TimeDoctorFetcher::getUserFromWorkLog($workLog);
            if ($task && $user) {
                $time_doctor_response = json_decode($workLog->time_doctor_response, true);
                $start_time = (int) Carbon::parse($time_doctor_response['start_time'])->format('U');
                $time = $time_doctor_response['length'] * 1000;
                $clickUpUserId = $user->click_up_user_id;
                $clickUpTaskId = $task->click_up_task_id;
                $requestRawBody = [
                    "description"   => "From SyncEr (API)",
                    "start"         => $start_time * 1000,
                    "duration"      => $time,
                    "billable"      => false,
                    "assignee"      => $clickUpUserId,
                    "tid"           => $clickUpTaskId
                ];


                $api = env('CLICK_UP_BASE_URL') . "/team/$teamId/time_entries/";

                $request = Http::withHeaders([
                    'Authorization' => $access_token
                ])->withBody(json_encode($requestRawBody), "application/json")->post($api);


                if ($request->header('X-RateLimit-Remaining') < 3) {
                    $resetTime = Carbon::createFromTimestamp($request->header('X-Ratelimit-Reset'));
                    $willBeBackIn = $resetTime->toDateTimeString();
                    Logger::verbose("RATE LIMIT BOUNDARY CROSSED. WILL RESUME IN $willBeBackIn");
                    sleep($resetTime->diffInSeconds() + 1);
                }


                if ($request->successful()) {
                    $workLog->update([
                        'click_up_response'     => json_encode($request->json()),
                        'synced_with_click_up'  => true,
                    ]);
                    Logger::info($workLog->id . " successfully synced");
                }
            }
        }
        dd("DONE");
    }
}
