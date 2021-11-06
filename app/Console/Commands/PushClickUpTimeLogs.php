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
        $call_count = 0;
        $settings = Settings::clickup();
        $access_token = $settings->access_token;
        $teamId = env('CLICK_UP_TEAM_ID');
        $workLogs = WorklogMapper::where('synced_with_click_up', false)->get();
        foreach ($workLogs as $workLog)
        {
            $taskInfo = TimeDoctorFetcher::getTaskFromWorkLog($workLog);
            $task = $taskInfo['task'];

            $task_call = $taskInfo['call_count'];
            $call_count += $task_call;
            Logger::verbose("CallCount: $call_count");
            if ($call_count > 80)
            {
                $call_count = 0;
                Logger::verbose("CallCount Reset, Sleep for 30 seconds");
                sleep(30);
            }

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
                    $response = $request->json();
                    $workLog->update([
                        'click_up_id'           => $response['data']['id'],
                        'click_up_response'     => json_encode($response),
                        'synced_with_click_up'  => true,
                    ]);
                    Logger::info($workLog->id . " successfully synced");
                }
            } else {
                $workLog->update([
                    'synced_with_click_up'  => true,
                ]);
                Logger::info($workLog->id . " can not sync");
            }
        }
        Logger::verbose("Done");
    }
}
