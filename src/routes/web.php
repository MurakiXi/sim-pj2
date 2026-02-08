<?php


use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StampCorrectionController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\RequestApproveController;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::middleware('auth', 'verified')->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'create'])->name('attendances.create');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendances.clock_in');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendances.clock_out');
    Route::post('/attendance/break-in', [AttendanceController::class, 'breakIn'])->name('attendances.break_in');
    Route::post('/attendance/break-out', [AttendanceController::class, 'breakOut'])->name('attendances.break_out');

    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendances.index');
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show'])->whereNumber('id')->name('attendances.show');
    Route::patch('/attendance/detail/{id}', [AttendanceController::class, 'update'])->whereNumber('id')->name('attendances.update');
});

Route::get('/stamp_correction_request/list', [StampCorrectionController::class, 'index'])
    ->middleware(['auth.any'])
    ->name('stamp_correction_request.list');

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['fortify.admin'])
    ->group(function () {

        Route::middleware('guest:admin')->group(function () {
            Route::get('/login',  [AuthenticatedSessionController::class, 'create'])->name('login');
            Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
        });

        Route::middleware('auth:admin')->group(function () {

            Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

            Route::get('/stamp_correction_request/list', [RequestApproveController::class, 'index'])
                ->name('requests.index');

            Route::get('/stamp_correction_request/{id}', [RequestApproveController::class, 'show'])
                ->whereNumber('id')
                ->name('requests.show');

            Route::patch('/stamp_correction_request/approve/{id}', [RequestApproveController::class, 'approve'])
                ->whereNumber('id')
                ->name('requests.approve');

            Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])
                ->name('attendances.index');

            Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])
                ->whereNumber('id')
                ->name('attendances.show');
        });
    });
