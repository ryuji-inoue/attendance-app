<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CorrectionBreak extends Model
{
    use HasFactory;

    protected $fillable = [
        'correction_id',
        'break_start',
        'break_end',
    ];

    /**
     * リレーション：この修正用休憩データが紐づく修正申請を取得
     */
    public function correctionRequest()
    {
        return $this->belongsTo(AttendanceCorrectionRequest::class, 'correction_id');
    }
}