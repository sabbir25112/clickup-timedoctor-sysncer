<?php namespace App\Http\Syncer;

use App\Models\UserMapper;
use App\Models\WorklogMapper;

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

    public static function syncWorkLog($logs)
    {
        foreach ($logs as $log)
        {
            dd($log);
            $has_worklog = WorklogMapper::where('time_doctor_id', $log['id'])->count();
            if ($has_worklog) continue;

//            WorklogMapper::create([
//                'time_doctor_id'        => $log['id'],
//                'time_doctor_response'  => json_encode($log)
//            ]);

            self::syncTask();
        }
    }

    public static function syncTask($log)
    {

    }
}
