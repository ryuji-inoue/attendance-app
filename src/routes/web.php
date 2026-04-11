<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect('/attendance');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/attendance', [App\Http\Controllers\AttendanceController::class, 'index']);
    Route::post('/attendance/clock-in', [App\Http\Controllers\AttendanceController::class, 'clockIn']);
    Route::post('/attendance/clock-out', [App\Http\Controllers\AttendanceController::class, 'clockOut']);
    Route::post('/attendance/break-start', [App\Http\Controllers\AttendanceController::class, 'breakStart']);
    Route::post('/attendance/break-end', [App\Http\Controllers\AttendanceController::class, 'breakEnd']);
    
    Route::get('/attendance/list', [App\Http\Controllers\AttendanceController::class, 'list']);
    Route::get('/attendance/detail/{id}', [App\Http\Controllers\AttendanceController::class, 'detail']);
    Route::post('/attendance/detail/{id}', [App\Http\Controllers\AttendanceController::class, 'requestCorrection']);

    // 共通申請一覧 (PG06/PG12)
    Route::get('/stamp_correction_request/list', [App\Http\Controllers\StampCorrectionController::class, 'list']);
});

Route::get('/admin/login', function () {
    return view('admin.auth.login');
})->name('admin.login')->middleware('guest');
Route::post('/admin/login', [\Laravel\Fortify\Http\Controllers\AuthenticatedSessionController::class, 'store'])->middleware('guest');

// 管理者機能
Route::middleware(['auth', 'admin'])->group(function () {
    // 承認画面はパス変更なし (PG13)
    Route::get('/stamp_correction_request/approve/{attendance_correct_request_id}', [App\Http\Controllers\AdminStampCorrectionController::class, 'showApprove']);
    Route::post('/stamp_correction_request/approve/{attendance_correct_request_id}', [App\Http\Controllers\AdminStampCorrectionController::class, 'approve']);

    Route::get('/admin/staff/list', [App\Http\Controllers\AdminStaffController::class, 'list']);
    Route::get('/admin/attendance/staff/{id}', [App\Http\Controllers\AdminStaffController::class, 'attendance']);
    Route::get('/admin/attendance/staff/{id}/export', [App\Http\Controllers\AdminStaffController::class, 'exportCsv']);
    Route::get('/admin/attendance/list', [App\Http\Controllers\AdminAttendanceController::class, 'list']);
});
