<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StampCorrectionController;
use App\Http\Controllers\AdminStampCorrectionController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Controllers\AdminAttendanceController;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| 勤怠管理システムのルーティング設定です。
|
*/

/**
 * システム共通 / トップページ
 */
Route::get('/', function () {
    return redirect('/attendance');
})->name('top');


/**
 * 一般ユーザー機能 (認証・メール認証必須)
 */
Route::middleware(['auth', 'verified'])->group(function () {
    
    // --- 勤怠打刻・ステータス管理 ---
    Route::get('/attendance', [AttendanceController::class, 'index'])->name('attendance.index');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendance.clock-in');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendance.clock-out');
    Route::post('/attendance/break-start', [AttendanceController::class, 'breakStart'])->name('attendance.break-start');
    Route::post('/attendance/break-end', [AttendanceController::class, 'breakEnd'])->name('attendance.break-end');
    
    // --- 勤怠履歴・詳細・修正申請 ---
    Route::get('/attendance/list', [AttendanceController::class, 'list'])->name('attendance.list');
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'detail'])->name('attendance.detail');
    Route::post('/attendance/detail/{id}', [AttendanceController::class, 'requestCorrection'])->name('attendance.update');

    // --- 修正申請一覧 ---
    Route::get('/stamp_correction_request/list', [StampCorrectionController::class, 'list'])->name('correction.list');
});


/**
 * 管理者機能 (認証・管理者権限必須)
 */
// 管理者ログイン (独自ビューを使用)
Route::get('/admin/login', function () {
    return view('admin.auth.login');
})->name('admin.login')->middleware('guest');

Route::post('/admin/login', [AuthenticatedSessionController::class, 'store'])->middleware('guest');

Route::middleware(['auth', 'admin'])->group(function () {
    
    // --- 勤怠・スタッフ管理 ---
    Route::get('/admin/attendance/list', [AdminAttendanceController::class, 'list'])->name('admin.attendance.list');
    Route::get('/admin/staff/list', [AdminStaffController::class, 'list'])->name('admin.staff.list');
    Route::get('/admin/attendance/staff/{id}', [AdminStaffController::class, 'attendance'])->name('admin.attendance.staff');
    Route::get('/admin/attendance/staff/{id}/export', [AdminStaffController::class, 'exportCsv'])->name('admin.attendance.export');

    // --- 修正申請の承認処理 ---
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminStampCorrectionController::class, 'showApprove'])->name('admin.correction.show');
    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [AdminStampCorrectionController::class, 'approve'])->name('admin.correction.approve');
});
