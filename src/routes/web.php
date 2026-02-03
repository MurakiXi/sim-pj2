<?php

use App\Http\Controllers\AdminAuthController;
use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;

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




Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('/login',  [AdminAuthController::class, 'create'])
        ->middleware('guest:admin')
        ->name('login');

    Route::post('/login', [AdminAuthController::class, 'store'])
        ->middleware('guest:admin')
        ->name('login.store');

    Route::post('/logout', [AdminAuthController::class, 'destroy'])
        ->middleware('auth:admin')
        ->name('logout');
});

Route::middleware('auth', 'verified')->group(function () {
    Route::get('/attendance', [AttendanceController::class, 'create'])->name('attendances.create');
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendances.clock_in');
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendances.clock_out');
    Route::post('/attendance/break-in', [AttendanceController::class, 'breakIn'])->name('attendances.break_in');
    Route::post('/attendance/break-out', [AttendanceController::class, 'breakOut'])->name('attendances.break_out');

    Route::get('/attendance/list', [AttendanceController::class, 'index'])->name('attendances.index');
    Route::get('/attendance/detail/{id}', [AttendanceController::class, 'show'])->whereNumber('id')->name('attendances.show');
    Route::patch('/attendance/detail/{id}', [AttendanceController::class, 'update'])->whereNumber('id')->name('attendances.update');

    Route::get('/stamp_correction_request/list', [CorrectionController::class, 'list'])->name('request.list');
    Route::post('/stamp_correction_request/list', [CorrectionController::class, 'edit'])->name('request.list');
});

Route::middleware('admin', 'verified')->group(function () {
    Route::get('/stamp_correction_request/list', [CorrectionController::class, 'list'])->name('correction.list');
    Route::patch('/stamp_correction_request/list', [CorrectionController::class, 'store'])->name('correction.list');
});
