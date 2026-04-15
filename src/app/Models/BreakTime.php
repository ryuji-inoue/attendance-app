<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreakTime extends Model
{
    use HasFactory;

    protected $table = 'breaks';

    protected $fillable = [
        'attendance_id',
        'break_start',
        'break_end',
    ];

    /**
     * リレーション：この休憩が紐づく元の勤怠レコードを取得
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }
}