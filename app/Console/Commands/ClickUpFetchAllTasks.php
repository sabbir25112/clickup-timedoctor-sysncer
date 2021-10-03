<?php

namespace App\Console\Commands;

use App\Http\Fetcher\ClickUpFetcher;
use App\Http\Syncer\ClickUpSyncer;
use App\Logger;
use Illuminate\Console\Command;

class ClickUpFetchAllTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fetch:clickUp-tasks';

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
        $team = ClickUpFetcher::getTeam();
        if ($team) {
            $teamId = $team['id'];
            $members = $team['members'];
            ClickUpSyncer::syncUser($members);

            ClickUpFetcher::getSpaces($teamId);
        }
    }
}
