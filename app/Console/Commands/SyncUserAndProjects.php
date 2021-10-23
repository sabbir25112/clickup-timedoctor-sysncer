<?php

namespace App\Console\Commands;

use App\Http\Fetcher\ClickUpFetcher;
use App\Http\Fetcher\TimeDoctorFetcher;
use App\Http\Syncer\ClickUpSyncer;
use App\Http\Syncer\TimeDoctorSyncer;
use App\Logger;
use App\Models\Project;
use App\Models\Settings;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class SyncUserAndProjects extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sync:user-and-projects';

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
        $this->syncUsers();
        $this->syncProjects();
    }

    private function syncUsers()
    {
        Logger::verbose("sync time-doctor users into database");
        $users = TimeDoctorFetcher::getUsers();
        TimeDoctorSyncer::syncUser($users);
        Logger::verbose("time-doctor users sync complete");

        Logger::verbose("sync clickUp users into database");
        $team = ClickUpFetcher::getTeam();
        if ($team) {
            $teamId = $team['id'];

            Logger::verbose("processing teamId: $teamId");

            $members = $team['members'];
            ClickUpSyncer::syncUser($members);

            Logger::verbose("clickUp users sync complete");
        } else {
            Logger::error("Team Information Not Found");
        }
    }

    private function syncProjects()
    {
        $settings = Settings::timedoctor();
        $access_token = $settings->access_token;

        Logger::verbose("fetching time-doctor projects");

        $api = env('TIME_DOCTOR_BASE_URL') . '/companies/projects';
        $request = Http::get($api, [
            'access_token'  => $access_token,
            'limit'         => 500,
        ]);

        if ($request->successful()) {
            Logger::verbose("successfully got projects from time-doctor");
            $response = $request->json();

            foreach ($response['count'] as $project)
            {
                if (isset($project['integration']['source']) && $project['integration']['source'] == 'clickup') {
                    $projectId = $project['id'];
                    $projectMapper = Project::where('time_doctor_project_id', $projectId)->first();
                    if ($projectMapper == null) {
                        Project::create([
                            'time_doctor_project_id' => $projectId,
                            'time_doctor_response'   => json_encode($project),
                        ]);
                    }
                }
            }

            foreach ($response['projects'] as $project)
            {
                if ($project['project_source'] == 'clickup')
                {
                    $projectId = $project['id'];
                    $projectMapper = Project::where('time_doctor_project_id', $projectId)->first();
                    if ($projectMapper == null) {
                        Project::create([
                            'time_doctor_project_id' => $projectId,
                            'time_doctor_response'   => json_encode($project),
                        ]);
                    }
                }
            }

            Logger::verbose("Projects Sync Done");
        } else {
            Logger::verbose("Something went wrong");
        }
    }
}
