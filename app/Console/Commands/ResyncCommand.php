<?php

namespace App\Console\Commands;

use App\Http\Fetcher\TimeDoctorFetcher;
use App\Http\Syncer\TimeDoctorSyncer;
use App\Logger;
use App\Models\ClickUpDeletedResponse;
use App\Models\Settings;
use App\Models\WorklogMapper;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class ResyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 're:Sync {date}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Re-Sync for a specific date';

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
        $date = $this->argument('date');
        $carbonDate = Carbon::parse($date);
        $date = $carbonDate->toDateString();

        $workLogs = WorklogMapper::where('date', $date)
            ->where('synced_with_click_up', 1)
            ->whereNotNull('click_up_response')
            ->get();
        $this->deleteClickUpWorkLogs($workLogs);
        $this->reSyncWorkLog($date);
    }

    private function deleteClickUpWorkLogs($workLogs)
    {
        $teamId = (int) env('CLICK_UP_TEAM_ID');
        $settings = Settings::clickup();
        $access_token = $settings->access_token;

        Logger::verbose($workLogs->count() . ' worklog(s) need to delete');

        foreach ($workLogs as $workLog)
        {
            $click_up_response = json_decode($workLog->click_up_response, true);
            $intervalId = $click_up_response['data']['id'];
            $taskId = $click_up_response['data']['task']['id'];

            Logger::verbose('Deleting : IntervalID: ' . $intervalId . ' taskID: '. $taskId);

            $isAlreadyDeleted = ClickUpDeletedResponse::where('click_up_interval_id', $intervalId)->count();
            if ($isAlreadyDeleted) {
                Logger::info("IntervalId: $intervalId already deleted. Skipping");
                continue;
            }

            $api = env('CLICK_UP_BASE_URL') . "/team/$teamId/time_entries/$intervalId";

            $request = Http::withHeaders([
                'Authorization' => $access_token
            ])->delete($api);

            Log::info($request->json());

            if ($request->status() != 404 && $request->header('x-rateLimit-remaining') < 3) {
                $resetTime = Carbon::createFromTimestamp($request->header('x-ratelimit-reset'));
                $willBeBackIn = $resetTime->toDateTimeString();
                Logger::verbose("RATE LIMIT BOUNDARY CROSSED. WILL RESUME IN $willBeBackIn");
                sleep($resetTime->diffInSeconds() + 1);
            }

            if ($request->successful()) {
                $response = $request->json();
                if ($response['data'] == null) {
                    continue;
                }
                ClickUpDeletedResponse::create([
                    'click_up_team_id'      => $teamId,
                    'click_up_task_id'      => $taskId,
                    'click_up_interval_id'  => $intervalId,
                    'deleted_response'      => json_encode($response)
                ]);

                $workLog->delete();

                Logger::verbose("Deleted Successfully");
            }
        }
    }

    private function reSyncWorkLog($date)
    {
        $settings = Settings::clickup();
        $access_token = $settings->access_token;
        $teamId = env('CLICK_UP_TEAM_ID');
        $call_count = 0;

        $worklogs = TimeDoctorFetcher::getWorkLog($date);
        TimeDoctorSyncer::storeWorkLogIntoDB($worklogs['worklog']);
        $newWorkLogs = WorklogMapper::where('synced_with_click_up', false)
            ->where('date', $date)
            ->get();

        foreach ($newWorkLogs as $workLog)
        {
            $taskInfo = TimeDoctorFetcher::getTaskFromWorkLog($workLog);
            $task = $taskInfo['task'];
            $user = TimeDoctorFetcher::getUserFromWorkLog($workLog);

            $task_call = $taskInfo['call_count'];
            $call_count += $task_call;
            if ($call_count > 80)
            {
                $call_count = 0;
                sleep(30);
            }

            if ($task && $user)
            {
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
                    sleep($resetTime->diffInSeconds() + 1);
                }

                if ($request->successful()) {
                    $response = $request->json();
                    $workLog->update([
                        'click_up_id'           => $response['data']['id'],
                        'click_up_response'     => json_encode($response),
                        'synced_with_click_up'  => true,
                    ]);
                }
            } else {
                $workLog->update([
                    'synced_with_click_up'  => true,
                ]);
            }
        }
    }
}
