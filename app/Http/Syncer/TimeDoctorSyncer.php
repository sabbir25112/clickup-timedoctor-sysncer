<?php namespace App\Http\Syncer;

use App\Models\UserMapper;
use App\Models\WorklogMapper;
use Carbon\Carbon;

class TimeDoctorSyncer
{
    public static function syncUser($users)
    {
        foreach ($users as $user)
        {
            $mapped_user = UserMapper::where('email', $user['email'])->first();
            if (!$mapped_user) {
                $mapped_user = UserMapper::create([
                    'full_name'             => $user['full_name'],
                    'email'                 => $user['email'],
                    'time_doctor_user_id'   => $user['user_id'],
                    'time_doctor_response'  => json_encode($user),
                ]);
            }
            else {
                $mapped_user->update([
                    'time_doctor_response'  => json_encode($user),
                    'time_doctor_user_id'   => $user['user_id'],
                ]);
            }
        }
    }

    public static function storeWorkLogIntoDB($logs)
    {
        foreach ($logs as $log)
        {
            $logId = $log['id'];
            $userId = $log['user_id'];
            $has_workLog = WorklogMapper::where('time_doctor_id', $logId)->first();
            if ($has_workLog) continue;

            WorklogMapper::create([
                'date'                  => Carbon::parse($log['start_time'])->toDateString(),
                'time_doctor_id'        => $logId,
                'time_doctor_user_id'   => $userId,
                'time_doctor_response'  => json_encode($log),
            ]);
        }
    }
}
