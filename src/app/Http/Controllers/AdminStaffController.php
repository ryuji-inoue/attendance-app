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


        $prevMonth = $date->copy()->subMonth()->format('Y-m');
        $nextMonth = $date->copy()->addMonth()->format('Y-m');
        $currentMonthDisplay = $date->format('Y/m');
        $exportMonth = $month; // BladeのCSV出力リンク用

        return view('admin.staff.attendance', compact('user', 'attendances', 'currentMonthDisplay', 'prevMonth', 'nextMonth', 'exportMonth'));
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
                fputcsv($file, [
                    Carbon::parse($attendance->date)->isoFormat('YYYY/MM/DD(ddd)'), // CSV用の年入り日付
                    $attendance->formatted_clock_in,
                    $attendance->formatted_clock_out,
                    $attendance->total_break,
                    $attendance->total_working
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