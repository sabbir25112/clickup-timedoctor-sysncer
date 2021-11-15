<?php

namespace App\Console\Commands;

use App\Http\Fetcher\TimeDoctorFetcher;
use App\Logger;
use App\Models\Settings;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncErCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'run:SyncEr';

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
        $is_successful = TimeDoctorFetcher::setAccessToken(Settings::timedoctor());
        if (!$is_successful) {
            Logger::error("TimeDoctor AccessToken Can't Generate");
            return 0;
        }

        $this->call('sync:user-and-projects');
        $this->call('fetch:time-doctor');
        $this->call('push:clickUp-time-logs');

        Logger::info("### Scheduler Runs @ " . Carbon::now()->toDateTimeString());
    }
}
