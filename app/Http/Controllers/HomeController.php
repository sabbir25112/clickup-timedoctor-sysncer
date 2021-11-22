<?php

namespace App\Http\Controllers;

use App\Http\Fetcher\TimeDoctorFetcher;
use App\Http\Syncer\TimeDoctorSyncer;
use App\Logger;
use App\Models\ClickUpDeletedResponse;
use App\Models\Settings;
use App\Models\UserMapper;
use App\Models\WorklogMapper;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $users = UserMapper::whereNotNull('time_doctor_user_id')
            ->whereNotNull('click_up_user_id')
            ->get();

        return view('home', compact('users'));
    }

    public function manualAdjustment(Request $request)
    {
        set_time_limit(0);

        $this->validate($request, [
            'user'  => 'required',
            'date'  => 'required|before:today',
        ]);
        $user = UserMapper::find($request->user);
        $date = $request->date;

        $timeDoctorUserId = $user->time_doctor_user_id;
        $workLogs = WorklogMapper::where('time_doctor_user_id', $timeDoctorUserId)
            ->where('date', $date)
            ->where('synced_with_click_up', 1)
            ->whereNotNull('click_up_response')
            ->get();

        $this->deleteClickUpWorkLogs($workLogs);
        $this->reSyncWorkLog($user, $date);
        Session::flash('status', 'Resync Successfully');

        return redirect()->back();
    }

    private function deleteClickUpWorkLogs($workLogs)
    {
        $teamId = (int) env('CLICK_UP_TEAM_ID');
        $settings = Settings::clickup();
        $access_token = $settings->access_token;
        foreach ($workLogs as $workLog)
        {
            $click_up_response = json_decode($workLog->click_up_response, true);
            $intervalId = $click_up_response['data']['id'];
            $taskId = $click_up_response['data']['task']['id'];

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
            }
        }
    }

    private function reSyncWorkLog($user, $date)
    {
        $settings = Settings::clickup();
        $access_token = $settings->access_token;
        $teamId = env('CLICK_UP_TEAM_ID');
        $call_count = 0;
        $clickUpUserId = $user->click_up_user_id;

        $worklogs = TimeDoctorFetcher::getWorkLog($date, [$user->time_doctor_user_id]);
        TimeDoctorSyncer::storeWorkLogIntoDB($worklogs['worklog']);
        $newWorkLogs = WorklogMapper::where('synced_with_click_up', false)
            ->where('date', $date)
            ->where('time_doctor_user_id', $user->time_doctor_user_id)
            ->get();

        foreach ($newWorkLogs as $workLog)
        {
            $taskInfo = TimeDoctorFetcher::getTaskFromWorkLog($workLog);
            $task = $taskInfo['task'];

            $task_call = $taskInfo['call_count'];
            $call_count += $task_call;
            if ($call_count > 80)
            {
                $call_count = 0;
                sleep(30);
            }

            if ($task)
            {
                $time_doctor_response = json_decode($workLog->time_doctor_response, true);
                $start_time = (int) Carbon::parse($time_doctor_response['start_time'])->format('U');
                $time = $time_doctor_response['length'] * 1000;
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
