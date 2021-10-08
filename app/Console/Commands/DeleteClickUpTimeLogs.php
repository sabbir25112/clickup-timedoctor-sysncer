<?php

namespace App\Console\Commands;

use App\Logger;
use App\Models\ClickUpDeletedResponse;
use App\Models\Settings;
use App\Models\TempClickUpTimeLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DeleteClickUpTimeLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'delete:clickUp-timeLogs';

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
        // $tempTimeLogs = TempClickUpTimeLog::where('click_up_task_id', 'uddnaz')->get();
        $tempTimeLogs = TempClickUpTimeLog::all();
        $userIdThatShouldNotTouch = explode(',', env('UNTOUCHABLE_CLICK_UP_USER_IDS'));
        $teamId = (int) env('CLICK_UP_TEAM_ID');
        $settings = Settings::clickup();
        $access_token = $settings->access_token;

        Logger::verbose("Have to Process ". count($tempTimeLogs) . " Tasks");
        foreach ($tempTimeLogs as $tempTimeLog) {
            $taskId = $tempTimeLog->click_up_task_id;

            Logger::verbose("Processing TaskId: $taskId");

            $click_up_time_log = json_decode($tempTimeLog->click_up_time_log, true);

            Logger::verbose("Have to Process TimeLog of ". count($click_up_time_log['data']) . " Users");

            foreach ($click_up_time_log['data'] as $time_log)
            {
                $userId = $time_log['user']['id'];
                Logger::verbose("Processing UserId: $userId");

                if (in_array($userId, $userIdThatShouldNotTouch)) {
                    Logger::verbose("Skipping UserId: $userId Because It's Untouchable");
                    continue;
                }

                $intervals = $time_log['intervals'];

                Logger::verbose("Have to Delete ". count($intervals) . " TimeLogs");
                foreach ($intervals as $interval)
                {
                    $intervalId = $interval['id'];

                    $isAlreadyDeleted = ClickUpDeletedResponse::where('click_up_interval_id', $intervalId)->count();

                    if ($isAlreadyDeleted) {
                        Logger::verbose("IntervalId: $intervalId already deleted. Skipping");
                        continue;
                    }

                    Logger::verbose("Deleting IntervalId: $intervalId");

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
                            Logger::verbose("Response Data is Null");
                            continue;
                        }
                        ClickUpDeletedResponse::create([
                            'click_up_team_id'      => $teamId,
                            'click_up_task_id'      => $taskId,
                            'click_up_interval_id'  => $intervalId,
                            'deleted_response'      => json_encode($response)
                        ]);

                        Logger::verbose("Deleted and Response Stored");
                    }
                }
            }
        }

        return 0;
    }
}
