<?php

namespace App\Console\Commands;

use App\Logger;
use App\Models\TempClickUpTimeLog;
use Illuminate\Console\Command;

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
        $tempTimeLogs = TempClickUpTimeLog::all();

        foreach ($tempTimeLogs as $tempTimeLog) {
            $taskId = $tempTimeLog->click_up_task_id;
            $data_count = count($tempTimeLog->click_up_time_log['data']);
            if ($data_count > 1) {
                // dd($tempTimeLog->click_up_time_log);
                Logger::verbose("$taskId -> $data_count");
            }
        }

        return 0;
    }
}
