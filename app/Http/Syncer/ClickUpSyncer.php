<?php

namespace App\Http\Syncer;

use App\Models\UserMapper;

class ClickUpSyncer
{
    public static function syncUser($users)
    {
        foreach ($users as $user)
        {
            $mapped_user = UserMapper::where('email', $user['user']['email'])->first();
            if (!$mapped_user) {
                $mapped_user = UserMapper::create([
                    'full_name'         => $user['user']['username'],
                    'email'             => $user['user']['email'],
                    'click_up_user_id'  => $user['user']['id'],
                    'click_up_response' => json_encode($user),
                ]);
            }
            else {
                $mapped_user->update([
                    'click_up_response'  => json_encode($user),
                    'click_up_user_id'  => $user['user']['id'],
                ]);
            }
        }
    }
}
