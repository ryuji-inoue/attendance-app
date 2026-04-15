<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AttendanceCorrectionRequest;
use App\Models\Attendance;
use App\Models\BreakTime;
use Illuminate\Support\Facades\DB;

class AdminStampCorrectionController extends Controller
{
    // 管理者：申請一覧
    public function list(Request $request)
    {
        $status = $request->query('status', 'pending');

        $requests = AttendanceCorrectionRequest::where('status', $status)
            ->with(['user', 'attendance'])
            ->latest()
            ->paginate(10);

        return view('admin.correction_request.list', compact('requests', 'status'));
    }

    // 管理者：承認画面表示
    public function showApprove($id)
    {
        $correction = AttendanceCorrectionRequest::with(['user', 'attendance', 'correctionBreaks'])->findOrFail($id);
        return view('admin.correction_request.approve', compact('correction'));
    }

    // 管理者：承認処理
    public function approve(Request $request, $id)
    {
        $correction = AttendanceCorrectionRequest::with('correctionBreaks')->findOrFail($id);

        DB::transaction(function () use ($correction) {
            // 元の勤怠データを更新
            $attendance = Attendance::findOrFail($correction->attendance_id);
            $attendance->update([
                'clock_in' => $correction->clock_in,
                'clock_out' => $correction->clock_out,
                'status' => '退勤済',
            ]);

            // 休憩データの差し替え
            BreakTime::where('attendance_id', $attendance->id)->delete();
            foreach ($correction->correctionBreaks as $cBreak) {
                BreakTime::create([
                    'attendance_id' => $attendance->id,
                    'break_start' => $cBreak->break_start,
                    'break_end' => $cBreak->break_end,
                ]);
            }

            // 申請ステータスを承認済みに変更
            $correction->update(['status' => 'approved']);
        });

        return redirect('/stamp_correction_request/list')->with('success', '申請を承認しました。');
    }
}
