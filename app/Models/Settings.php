<?php namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function scopeName($query, $name)
    {
        return $query->where('name', $name)->first();
    }

    public static function timedoctor()
    {
        return self::name('timedoctor');
    }

    public static function clickup()
    {
        return self::name('clickup');
    }
}
