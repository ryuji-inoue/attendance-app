<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminStaffController extends Controller
{
    // 管理者：スタッフ一覧
    public function list()
    {
        $staffs = User::where('role', 'user')->paginate(10);
        return view('admin.staff.list', compact('staffs'));
    }

    // 管理者：スタッフ別勤怠一覧
    public function attendance(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $month = $request->query('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month . '-01');

        $attendances = Attendance::where('user_id', $user->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->with('breaks')
            ->orderBy('date', 'asc')
            ->get();

        // 勤怠計算ロジック（AttendanceController@listと同様）
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

        return view('admin.staff.attendance', compact('user', 'attendances', 'currentMonthDisplay', 'prevMonth', 'nextMonth'));
    }

    // 管理者：スタッフ別勤怠CSV出力
    public function exportCsv(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $month = $request->query('month', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($month . '-01');

        $attendances = Attendance::where('user_id', $user->id)
            ->whereYear('date', $date->year)
            ->whereMonth('date', $date->month)
            ->with('breaks')
            ->orderBy('date', 'asc')
            ->get();

        $callback = function () use ($attendances) {
            $file = fopen('php://output', 'w');
            // BOM追加 (Excel対策)
            fputs($file, "\xEF\xBB\xBF");

            // ヘッダー
            fputcsv($file, ['日付', '出勤', '退勤', '休憩時間', '合計勤務時間']);

            foreach ($attendances as $attendance) {
                // 休憩時間計算
                $totalBreakMinutes = 0;
                foreach ($attendance->breaks as $break) {
                    if ($break->break_start && $break->break_end) {
                        $totalBreakMinutes += Carbon::parse($break->break_end)->diffInMinutes(Carbon::parse($break->break_start));
                    }
                }
                $totalBreak = sprintf('%d:%02d', intdiv($totalBreakMinutes, 60), $totalBreakMinutes % 60);

                // 勤務時間計算
                $totalWorking = '';
                if ($attendance->clock_in && $attendance->clock_out) {
                    $workingMinutes = Carbon::parse($attendance->clock_out)->diffInMinutes(Carbon::parse($attendance->clock_in)) - $totalBreakMinutes;
                    $totalWorking = sprintf('%d:%02d', intdiv($workingMinutes, 60), $workingMinutes % 60);
                }

                fputcsv($file, [
                    Carbon::parse($attendance->date)->isoFormat('YYYY/MM/DD(ddd)'),
                    $attendance->clock_in ? Carbon::parse($attendance->clock_in)->format('H:i') : '',
                    $attendance->clock_out ? Carbon::parse($attendance->clock_out)->format('H:i') : '',
                    $totalBreak,
                    $totalWorking
                ]);
            }
            fclose($file);
        };

        $fileName = sprintf('attendance_%s_%s.csv', $user->name, $month);
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$fileName\"",
        ];

        return response()->stream($callback, 200, $headers);
    }
}
