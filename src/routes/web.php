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
    Route::get('attendance', [AttendanceController::class, 'create'])->name('attendances.create');
    Route::post('attendance/clock-in', [AttendanceController::class, 'clockIn'])->name('attendances.clock_in');
    Route::post('attendance/clock-out', [AttendanceController::class, 'clockOut'])->name('attendances.clock_out');
    Route::post('attendance/break-in', [AttendanceController::class, 'breakIn'])->name('attendances.break_in');
    Route::post('attendance/break-out', [AttendanceController::class, 'breakOut'])->name('attendances.break_out');
});
