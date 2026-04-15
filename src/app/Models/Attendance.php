<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'date',
        'clock_in',
        'clock_out',
        'status',
        'note',
    ];

    /**
     * リレーション：この勤怠の持ち主（ユーザー）を取得
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * リレーション：この勤怠に紐づく休憩時間（複数）を取得
     */
    public function breaks()
    {
        return $this->hasMany(BreakTime::class);
    }

    /**
     * リレーション：この勤怠に対する修正申請（複数）を取得
     */
    public function correctionRequests()
    {
        return $this->hasMany(AttendanceCorrectionRequest::class);
    }

    /**
     * 表示用の日付フォーマット（MM/DD(ddd)）
     */
    public function getFormattedDateAttribute()
    {
        return Carbon::parse($this->date)->isoFormat('MM/DD(ddd)');
    }

    /**
     * 表示用の出勤時刻フォーマット（H:i）
     */
    public function getFormattedClockInAttribute()
    {
        return $this->clock_in ? Carbon::parse($this->clock_in)->format('H:i') : '';
    }

    /**
     * 表示用の退勤時刻フォーマット（H:i）
     */
    public function getFormattedClockOutAttribute()
    {
        return $this->clock_out ? Carbon::parse($this->clock_out)->format('H:i') : '';
    }

    /**
     * 合計休憩時間の計算（H:i）
     */
    public function getTotalBreakAttribute()
    {
        $totalMinutes = 0;
        foreach ($this->breaks as $break) {
            if ($break->break_start && $break->break_end) {
                $totalMinutes += Carbon::parse($break->break_end)->diffInMinutes(Carbon::parse($break->break_start));
            }
        }
        return sprintf('%d:%02d', intdiv($totalMinutes, 60), $totalMinutes % 60);
    }

    /**
     * 合計勤務時間の計算（H:i）
     */
    public function getTotalWorkingAttribute()
    {
        if (!$this->clock_in || !$this->clock_out) {
            return '';
        }

        // 休憩時間を算出
        $totalBreakMinutes = 0;
        foreach ($this->breaks as $break) {
            if ($break->break_start && $break->break_end) {
                $totalBreakMinutes += Carbon::parse($break->break_end)->diffInMinutes(Carbon::parse($break->break_start));
            }
        }

        $workingMinutes = Carbon::parse($this->clock_out)->diffInMinutes(Carbon::parse($this->clock_in)) - $totalBreakMinutes;
        return sprintf('%d:%02d', intdiv($workingMinutes, 60), $workingMinutes % 60);
    }
}