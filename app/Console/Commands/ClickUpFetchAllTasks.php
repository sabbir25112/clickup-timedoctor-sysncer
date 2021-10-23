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
        Logger::verbose("getting team information");

        $team = ClickUpFetcher::getTeam();
        if ($team) {
            $teamId = $team['id'];

            Logger::verbose("processing teamId: $teamId");

            $members = $team['members'];
            ClickUpSyncer::syncUser($members);

            Logger::verbose("getting spaces");

            $spaces = ClickUpFetcher::getSpaces($teamId);

            foreach ($spaces as $space)
            {
                $spaceId = $space['id'];

                Logger::verbose("processing spaceId: $spaceId");
                Logger::verbose("getting folders for spaceId: $spaceId");

                $folders = ClickUpFetcher::getFolders($spaceId);

                foreach ($folders as $folder)
                {
                    $folderId = $folder['id'];
                    Logger::verbose("processing folderId: $folderId");

                    $lists = $folder['lists'];

                    Logger::verbose("processing ". count($lists) ." lists for folderId: $folderId");

                    $this->syncTaskOfLists($lists);
                }

                Logger::verbose("getting folder-less lists for spaceId: $spaceId");
                $lists = ClickUpFetcher::getFolderLessList($spaceId);

                Logger::verbose("processing ". count($lists) ." folder-less lists");
                $this->syncTaskOfLists($lists);
            }
        }
    }

    private function syncTaskOfLists($lists)
    {
        foreach ($lists as $list)
        {
            $listId = $list['id'];
            Logger::verbose("processing listId: $listId");

            $max_page = (int) ($list['task_count'] / 100);
            $tasks = ClickUpFetcher::getTasks($listId, $max_page);

            ClickUpSyncer::syncTasks($tasks);
        }
    }
}
