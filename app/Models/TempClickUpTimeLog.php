<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TempClickUpTimeLog extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'click_up_time_log' => 'json'
    ];
}
