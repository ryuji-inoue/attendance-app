<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Attendance;
use App\Models\BreakTime;
use Carbon\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'user@example.com')->first();
        if (!$user) return;

        // 過去7日分のデータを作成
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            
            // 出勤データ
            $attendance = Attendance::create([
                'user_id' => $user->id,
                'date' => $date->toDateString(),
                'clock_in' => '09:00:00',
                'clock_out' => '18:00:00',
                'status' => '退勤済',
            ]);

            // 休憩データ (12:00 - 13:00)
            BreakTime::create([
                'attendance_id' => $attendance->id,
                'break_start' => '12:00:00',
                'break_end' => '13:00:00',
            ]);
        }
    }
}
