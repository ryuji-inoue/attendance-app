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

        $prevDate = $date->copy()->subDay()->toDateString();
        $nextDate = $date->copy()->addDay()->toDateString();
        $currentDateDisplay = $date->isoFormat('YYYY/MM/DD');

        return view('admin.attendance.list', compact('attendances', 'currentDateDisplay', 'prevDate', 'nextDate'));
    }
}
