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
        $now = Carbon::now();

        // 本日の最新の勤怠レコードを取得
        $attendance = Attendance::where('user_id', $user->id)
            ->where('date', $now->toDateString())
            ->latest()
            ->first();

        $status = $attendance ? $attendance->status : '勤務外';

        // 表示用の日付と時刻をコントローラーで準備
        $currentDate = $now->isoFormat('YYYY年M月D日(ddd)');
        $currentTime = $now->format('H:i');

        return view('attendance.index', compact('attendance', 'status', 'currentDate', 'currentTime'));
    }

    // 出勤処理
    public function clockIn(Request $request)
    {
        $user = Auth::user();
        $now = Carbon::now();

        // 1日に1回だけ押下できる
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

        $prevMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');
        $currentMonthDisplay = $date->format('Y/m');

        return view('attendance.list', compact('attendances', 'currentMonthDisplay', 'prevMonth', 'nextMonth'));
    }

    // 勤怠詳細表示
    public function detail($id)
    {
        $attendance = Attendance::with(['breaks', 'user', 'correctionRequests.breaks'])->findOrFail($id);

        // 権限チェック
        if (Auth::user()->role !== 'admin' && $attendance->user_id !== Auth::id()) {
            abort(403);
        }

        // 承認待ちの申請を取得
        $pendingRequest = $attendance->correctionRequests->where('status', 'pending')->first();
        $isPending = (bool) $pendingRequest;
        $canEdit = Auth::user()->role === 'admin' || !$isPending;

        // ベースとなるデータの判定
        $baseClockIn = $isPending ? $pendingRequest->clock_in : $attendance->clock_in;
        $baseClockOut = $isPending ? $pendingRequest->clock_out : $attendance->clock_out;
        $baseNote = $isPending ? $pendingRequest->note : $attendance->note;

        // 1. 出退勤と備考のデータを準備 (すでにH:iフォーマット済み)
        $displayData = [
            'clock_in' => $this->formatTime(old('clock_in', $baseClockIn)),
            'clock_out' => $this->formatTime(old('clock_out', $baseClockOut)),
            'note' => old('note', $baseNote),
            'breaks' => []
        ];

        // 2. 休憩データの準備（バリデーションエラー時の old を優先）
        $oldBreaks = old('breaks');
        if ($oldBreaks !== null) {
            // エラーで戻ってきた場合は、入力された値をそのまま使う
            foreach ($oldBreaks as $break) {
                $displayData['breaks'][] = [
                    'start' => $break['start'] ?? '',
                    'end' => $break['end'] ?? '',
                ];
            }
        } else {
            // 通常時はDBの値をフォーマットして使う
            $sourceBreaks = $isPending ? $pendingRequest->breaks : $attendance->breaks;
            foreach ($sourceBreaks as $break) {
                $displayData['breaks'][] = [
                    'start' => $this->formatTime($break->break_start),
                    'end' => $this->formatTime($break->break_end),
                ];
            }
        }

        // 3. 休憩行の表示数を調整
        if ($canEdit) {
            // 編集モードなら、新しく追加するための「空行」を末尾に1つ足す
            $displayData['breaks'][] = ['start' => '', 'end' => ''];
        } elseif (count($displayData['breaks']) === 0) {
            // 閲覧モードで休憩が0回の場合でも、表示崩れを防ぐために空行を1つ用意
            $displayData['breaks'][] = ['start' => '', 'end' => ''];
        }

        return view('attendance.detail', compact('attendance', 'isPending', 'displayData', 'canEdit', 'pendingRequest'));
    }

    // 修正申請の提出
    public function requestCorrection(CorrectionRequest $request, $id)
    {
        $attendance = Attendance::findOrFail($id);
        $isAdmin = Auth::user()->role === 'admin';

        $validated = $request->validated();

        if ($isAdmin) {
            DB::transaction(function () use ($attendance, $validated) {
                // 勤怠本体の直接更新
                $attendance->update([
                    'clock_in' => $validated['clock_in'],
                    'clock_out' => $validated['clock_out'],
                    'note' => $validated['note'],
                    'status' => '退勤済',
                ]);

                // 休憩時間の直接更新（一度削除して再作成）
                BreakTime::where('attendance_id', $attendance->id)->delete();
                if (isset($validated['breaks'])) {
                    foreach ($validated['breaks'] as $breakData) {
                        // 開始・終了どちらも入力されている場合のみ保存
                        if (!empty($breakData['start']) && !empty($breakData['end'])) {
                            BreakTime::create([
                                'attendance_id' => $attendance->id,
                                'break_start' => $breakData['start'],
                                'break_end' => $breakData['end'],
                            ]);
                        }
                    }
                }
            });

            return back()->with('success', '勤怠情報を修正しました。');
        } else {
            // 一般ユーザー：修正申請レコードの作成
            $correction = AttendanceCorrectionRequest::create([
                'attendance_id' => $attendance->id,
                'user_id' => Auth::id(),
                'status' => 'pending',
                'clock_in' => $validated['clock_in'],
                'clock_out' => $validated['clock_out'],
                'note' => $validated['note'],
            ]);

            // 休憩時間の修正データ保存
            if (isset($validated['breaks'])) {
                foreach ($validated['breaks'] as $breakData) {
                    if (!empty($breakData['start']) && !empty($breakData['end'])) {
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

    // 時刻フォーマット用のプライベートメソッド
    private function formatTime($time)
    {
        if (!$time)
            return '';
        try {
            return \Carbon\Carbon::parse($time)->format('H:i');
        } catch (\Exception $e) {
            return $time; // 既にH:i形式になっている等の場合はそのまま返す
        }
    }
}