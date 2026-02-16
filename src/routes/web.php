<?php


use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\StampCorrectionController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Http\Controllers\AuthenticatedSessionController;
use App\Http\Controllers\AdminAttendanceController;
use App\Http\Controllers\RequestApproveController;
use App\Http\Controllers\AdminStaffController;
use App\Http\Requests\RegisterRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Laravel\Fortify\Contracts\RegisterResponse;

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

Route::prefix('admin')->name('admin.')->group(function () {

    Route::middleware(['guest:admin', 'fortify.admin'])->group(function () {
        Route::get('/login', fn() => view('admin.auth.login'))->name('login');
        Route::post('/login', [AuthenticatedSessionController::class, 'store'])->name('login.store');
    });

    Route::middleware(['auth:admin', 'fortify.admin'])->group(function () {
        Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

        Route::get('/attendance/list', [AdminAttendanceController::class, 'index'])->name('attendances.index');
        Route::get('/attendance/{id}', [AdminAttendanceController::class, 'show'])->whereNumber('id')->name('attendances.show');
        Route::patch('/attendance/{id}', [AdminAttendanceController::class, 'update'])->whereNumber('id')->name('attendances.update');
    });
});

Route::get('/stamp_correction_request/list', [StampCorrectionController::class, 'index'])
    ->middleware(['auth.any'])
    ->name('stamp_correction_requests.index');

Route::prefix('admin')->name('admin.')->group(function () {

    Route::middleware(['auth:admin', 'fortify.admin'])->group(function () {

        Route::get('/staff/list', [AdminStaffController::class, 'index'])
            ->name('staff.index');

        Route::get('/attendance/staff/{user}', [AdminStaffController::class, 'attendanceIndex'])
            ->whereNumber('user')
            ->name('staff.attendances.index');

        Route::get('/attendance/staff/{user}/csv', [AdminStaffController::class, 'attendanceCsv'])
            ->whereNumber('user')
            ->name('staff.attendances.csv');
    });
});

Route::middleware(['auth:admin', 'fortify.admin'])->group(function () {
    Route::get('/stamp_correction_request/approve/{id}', [RequestApproveController::class, 'show'])
        ->whereNumber('id')
        ->name('admin.requests.show');

    Route::patch('/stamp_correction_request/approve/{id}', [RequestApproveController::class, 'approve'])
        ->whereNumber('id')
        ->name('admin.requests.approve');
});

Route::middleware('guest')->group(function () {
    Route::get('/register', fn() => view('auth.register'))->name('register');

    Route::post('/register', function (
        RegisterRequest $request,
        CreatesNewUsers $creator
    ) {
        $user = $creator->create($request->validated());

        event(new Registered($user));

        Auth::login($user);

        return app(RegisterResponse::class);
    })->name('register');
});
