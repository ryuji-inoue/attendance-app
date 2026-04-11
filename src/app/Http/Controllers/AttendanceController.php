<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use App\Models\BreakTime;
use App\Models\AttendanceCorrectionRequest;
use App\Models\CorrectionBreak;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\CorrectionRequest;

class AttendanceController extends Controller
{
    // 打刻メイン画面の表示
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today();
        
        // 本日の最新の勤怠レコードを取得
        $attendance = Attendance::where('user_id', $user->id)
                                ->where('date', $today)
                                ->latest()
                                ->first();

        $status = $attendance ? $attendance->status : '勤務外';

        return view('attendance.index', compact('attendance', 'status'));
    }

    // 出勤処理
    public function clockIn(Request $request)
    {
        $user = Auth::user();
        $now = Carbon::now();

        // 1日に1回だけ押下できる (FN020.2)
        $exists = Attendance::where('user_id', $user->id)
                            ->where('date', $now->toDateString())
                            ->exists();

        if ($exists) {
            return redirect('/attendance')->with('error', '本日は既に出勤済みです。');
        }

        Attendance::create([
            'user_id' => $user->id,
            'date' => $now->toDateString(),
            'clock_in' => $now->toTimeString(),
            'status' => '出勤中',
        ]);

        return redirect('/attendance');
    }

    // 退勤処理
    public function clockOut(Request $request)
    {
        $user = Auth::user();
        $attendance = Attendance::where('user_id', $user->id)
                                ->where('status', '出勤中')
                                ->latest()
                                ->first();

        if ($attendance) {
            $attendance->update([
                'clock_out' => Carbon::now()->toTimeString(),
                'status' => '退勤済',
            ]);
        }

        return redirect('/attendance')->with('success', 'お疲れ様でした。');
    }

    // 休憩開始
    public function breakStart(Request $request)
    {
        $user = Auth::user();
        $attendance = Attendance::where('user_id', $user->id)
                                ->where('status', '出勤中')
                                ->latest()
                                ->first();

        if ($attendance) {
            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_start' => Carbon::now()->toTimeString(),
            ]);

            $attendance->update(['status' => '休憩中']);
        }

        return redirect('/attendance');
    }

    // 休憩終了
    public function breakEnd(Request $request)
    {
        $user = Auth::user();
        $attendance = Attendance::where('user_id', $user->id)
                                ->where('status', '休憩中')
                                ->latest()
                                ->first();

        if ($attendance) {
            $break = BreakTime::where('attendance_id', $attendance->id)
                              ->whereNull('break_end')
                              ->latest()
                              ->first();

            if ($break) {
                $break->update([
                    'break_end' => Carbon::now()->toTimeString(),
                ]);
            }

            $attendance->update(['status' => '出勤中']);
        }

        return redirect('/attendance');
    }

    // 勤怠一覧表示
    public function list(Request $request)
    {
        $user = Auth::user();
        $month = $request->query('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month . '-01');

        $attendances = Attendance::where('user_id', $user->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->with('breaks')
            ->orderBy('date', 'asc')
            ->get();

        // 各レコードの合計休憩時間と勤務時間を計算
        foreach ($attendances as $attendance) {
            $totalBreakMinutes = 0;
            foreach ($attendance->breaks as $break) {
                if ($break->break_start && $break->break_end) {
                    $start = Carbon::parse($break->break_start);
                    $end = Carbon::parse($break->break_end);
                    $totalBreakMinutes += $end->diffInMinutes($start);
                }
            }
            
            $attendance->total_break = sprintf('%d:%02d', intdiv($totalBreakMinutes, 60), $totalBreakMinutes % 60);

            if ($attendance->clock_in && $attendance->clock_out) {
                $in = Carbon::parse($attendance->clock_in);
                $out = Carbon::parse($attendance->clock_out);
                $workingMinutes = $out->diffInMinutes($in) - $totalBreakMinutes;
                $attendance->total_working = sprintf('%d:%02d', intdiv($workingMinutes, 60), $workingMinutes % 60);
            } else {
                $attendance->total_working = '';
            }
        }

        $prevMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');
        $currentMonthDisplay = $date->format('Y/m');

        return view('attendance.list', compact('attendances', 'currentMonthDisplay', 'prevMonth', 'nextMonth'));
    }

    // 勤怠詳細表示
    public function detail($id)
    {
        $attendance = Attendance::with(['breaks', 'user'])->findOrFail($id);
        
        // ログインユーザー本人のものかチェック
        if (Auth::user()->role !== 'admin' && $attendance->user_id !== Auth::id()) {
            abort(403);
        }

        // 承認待ちの申請があるかチェック
        $isPending = $attendance->correctionRequests()->where('status', 'pending')->exists();

        return view('attendance.detail', compact('attendance', 'isPending'));
    }

    // 修正申請の提出
    public function requestCorrection(CorrectionRequest $request, $id)
    {
        $attendance = Attendance::findOrFail($id);
        $isAdmin = Auth::user()->role === 'admin';
        
        $validated = $request->validated();

        if ($isAdmin) {
            DB::transaction(function () use ($attendance, $request) {
                // 勤怠本体の直接更新
                $attendance->update([
                    'clock_in' => $request->clock_in,
                    'clock_out' => $request->clock_out,
                    'note' => $request->note,
                    'status' => '退勤済',
                ]);

                // 休憩時間の直接更新（一度削除して再作成）
                BreakTime::where('attendance_id', $attendance->id)->delete();
                if ($request->has('breaks')) {
                    foreach ($request->breaks as $breakData) {
                        if ($breakData['start'] && $breakData['end']) {
                            BreakTime::create([
                                'attendance_id' => $attendance->id,
                                'break_start' => $breakData['start'],
                                'break_end' => $breakData['end'],
                            ]);
                        }
                    }
                }
            });

            return redirect('/admin/attendance/list')->with('success', '勤怠情報を修正しました。');
        } else {
            // 一般ユーザー：修正申請レコードの作成
            $correction = AttendanceCorrectionRequest::create([
                'attendance_id' => $attendance->id,
                'user_id' => Auth::id(),
                'status' => 'pending',
                'clock_in' => $request->clock_in,
                'clock_out' => $request->clock_out,
                'note' => $request->note,
            ]);

            // 休憩時間の修正データ保存
            if ($request->has('breaks')) {
                foreach ($request->breaks as $breakData) {
                    if ($breakData['start'] && $breakData['end']) {
                        CorrectionBreak::create([
                            'correction_id' => $correction->id,
                            'break_start' => $breakData['start'],
                            'break_end' => $breakData['end'],
                        ]);
                    }
                }
            }

            return redirect('/attendance/list')->with('success', '修正申請を提出しました。');
        }
    }
}
