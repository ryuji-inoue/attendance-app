<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Attendance;
use Carbon\Carbon;

class AdminAttendanceController extends Controller
{
    /**
     * 管理者：全スタッフの勤怠一覧（日付別）
     */
    public function list(Request $request)
    {
        $dateString = $request->query('date', Carbon::today()->toDateString());
        $date = Carbon::parse($dateString);

        $attendances = Attendance::where('date', $dateString)
            ->with(['user', 'breaks'])
            ->orderBy('clock_in', 'asc')
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

        $prevDate = $date->copy()->subDay()->toDateString();
        $nextDate = $date->copy()->addDay()->toDateString();
        $currentDateDisplay = $date->isoFormat('YYYY/MM/DD');

        return view('admin.attendance.list', compact('attendances', 'currentDateDisplay', 'prevDate', 'nextDate'));
    }
}
