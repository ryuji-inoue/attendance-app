<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceCorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'attendance_id',
        'user_id',
        'status',
        'clock_in',
        'clock_out',
        'note',
        'approved_by',
        'approved_at',
    ];

    /**
     * リレーション：修正対象の勤怠レコードを取得
     */
    public function attendance()
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * リレーション：修正申請を提出したユーザーを取得
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * リレーション：この申請を承認した管理者ユーザーを取得
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * リレーション：修正申請に紐づく修正用休憩データ（複数）を取得
     */
    public function correctionBreaks()
    {
        return $this->hasMany(CorrectionBreak::class, 'correction_id');
    }

    /**
     * リレーション（エイリアス）：コントローラーやビューでの共通処理用
     * 修正申請に紐づく修正用休憩データ（複数）を取得
     */
    public function breaks()
    {
        return $this->hasMany(CorrectionBreak::class, 'correction_id');
    }
}