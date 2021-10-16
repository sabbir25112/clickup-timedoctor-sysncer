<?php

namespace App\Console\Commands;

use App\Logger;
use App\Models\Settings;
use App\Models\TaskMapper;
use App\Models\TempClickUpTimeLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class FetchClickUpTimeLogs extends Command
{

    const RATE_LIMIT_BOUNDARY = 3;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:clickUp-time-logs';

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
//        Logger::verbose("processing completed");
//        return 0;

        $clickUp_settings = Settings::clickup();
        $access_token = $clickUp_settings->access_token;

        $temp = TempClickUpTimeLog::pluck('click_up_task_id')->toArray();
        $clickUp_tasks = TaskMapper::whereNotIn('click_up_task_id', $temp)->get();
        $count = 0;
        foreach ($clickUp_tasks as $task)
        {
            $taskId = $task->click_up_task_id;
            Logger::verbose("processing taskId: $taskId");

            $api = env('CLICK_UP_BASE_URL') . "/task/$taskId/time";

            $request = Http::withHeaders([
                'Authorization' => $access_token
            ])->get($api);

            if ($request->header('X-RateLimit-Remaining') < self::RATE_LIMIT_BOUNDARY) {
                $resetTime = Carbon::createFromTimestamp($request->header('X-Ratelimit-Reset'));
                $willBeBackIn = $resetTime->toDateTimeString();
                Logger::verbose("RATE LIMIT BOUNDARY CROSSED. WILL RESUME IN $willBeBackIn");
                sleep($resetTime->diffInSeconds() + 1);
            }

            if ($request->successful()) {
                Logger::verbose("Storing TimeLog for taskId: $taskId");
                $tempClickUpTimeLog = TempClickUpTimeLog::where('click_up_task_id', $taskId)->first();
                if ($tempClickUpTimeLog) {
                    $tempClickUpTimeLog->update(['click_up_time_log' => json_encode($request->json())]);
                } else {
                    TempClickUpTimeLog::create([
                        'click_up_time_log' => json_encode($request->json()),
                        'click_up_task_id'  => $taskId,
                    ]);
                }
                $count++;
                Logger::verbose("$count item stored / updated");
            } else {
                Logger::error("Not Successful for taskId: $taskId");
            }
        }
        Logger::verbose("WorkLog of $count tasks are stored");
        return 0;
    }
}
