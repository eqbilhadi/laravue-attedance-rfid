<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\AccessSettings\NavManagementController;
use App\Http\Controllers\AccessSettings\RoleManagementController;
use App\Http\Controllers\AccessSettings\UserManagementController;
use App\Http\Controllers\AccessSettings\PermissionManagementController;
use App\Http\Controllers\Attendance\AttendanceController;
use App\Http\Controllers\Attendance\AttendanceCorrectionController;
use App\Http\Controllers\Leave\LeaveApprovalController;
use App\Http\Controllers\Leave\LeaveRequestController;
use App\Http\Controllers\MasterData\HolidayController;
use App\Http\Controllers\MasterData\LeaveTypeController;
use App\Http\Controllers\MasterData\ScheduleAssignmentController;
use App\Http\Controllers\MasterData\WorkScheduleController;
use App\Http\Controllers\MasterData\WorkTimeController;
use App\Http\Controllers\Reports\LateReportController;
use App\Http\Controllers\Reports\MonthlyReportController;
use App\Http\Controllers\RfidManagement\CardListController;
use App\Http\Controllers\RfidManagement\DevicesController;
use App\Http\Controllers\RfidManagement\LogScanController;
use App\Http\Controllers\RfidManagement\RegisterNewCard;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Di sini Anda dapat mendaftarkan rute web untuk aplikasi Anda. Rute-rute
| ini dimuat oleh RouteServiceProvider dan semuanya akan
| ditugaskan ke grup middleware "web".
|
*/

// Rute Publik
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Rute yang Memerlukan Autentikasi
Route::middleware(['auth', 'verified'])->group(function () {

    // Dashboard
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        // Endpoint untuk mengambil data via fetch
        Route::get('/summary-cards', [DashboardController::class, 'getSummaryCards'])->name('summary-cards');
        Route::get('/live-attendance', [DashboardController::class, 'getLiveAttendanceSummary'])->name('live-attendance');
        Route::get('/charts', [DashboardController::class, 'getChartData'])->name('charts');
        Route::get('/quick-stats', [DashboardController::class, 'getQuickStats'])->name('quick-stats');
    });

    // Grup untuk Role-Based Access Control (RBAC)
    Route::prefix('rbac')->name('rbac.')->group(function () {

        // Navigation Management
        Route::controller(NavManagementController::class)->group(function () {
            Route::get('navigation-management/sort', 'sort')->name('nav.sort');
            Route::post('navigation-management/sort', 'sortUpdate')->name('nav.sort-update');
            Route::resource('navigation-management', NavManagementController::class)
                ->except('show')
                ->names('nav')
                ->parameters(['navigation-management' => 'sysMenu'])
                ->whereNumber('sysMenu');
        });

        // Permission Management
        Route::resource('permission-management', PermissionManagementController::class)
            ->except(['show', 'create', 'edit'])
            ->names('permission')
            ->parameters(['permission-management' => 'sysPermission'])
            ->whereNumber('sysPermission');

        // Role Management
        Route::resource('role-management', RoleManagementController::class)
            ->except('show')
            ->names('role')
            ->parameters(['role-management' => 'sysRole'])
            ->whereNumber('sysRole');

        // User Management
        Route::controller(UserManagementController::class)->group(function () {
            Route::get('/user-search', 'search')->name('user.search');
            Route::resource('user-management', UserManagementController::class)
                ->except('show')
                ->names('user')
                ->parameters(['user-management' => 'sysUser'])
                ->whereUuid('sysUser');
        });
    });

    // Grup untuk RFID Management
    Route::prefix('rfid-management')->name('rfid-management.')->group(function () {

        // Register New Card
        Route::resource('register-new-card', RegisterNewCard::class)
            ->only('index', 'store')
            ->names('register-new-card');

        // Card List
        Route::resource('card', CardListController::class)
            ->only('index', 'update', 'destroy')
            ->names('card');

        // Devices Management
        Route::controller(DevicesController::class)->group(function () {
            Route::get('/devices/check-exists', 'checkExists')->name('devices.check');
            Route::resource('devices', DevicesController::class)
                ->only('index', 'destroy', 'store', 'update');
        });

        // Log Scan
        Route::get('/log-scan', [LogScanController::class, 'index'])->name('log-scan.index');
    });

    // Grup untuk Master Data
    Route::prefix('master-data')->name('master-data.')->group(function () {
        // Work Time
        Route::resource('work-time', WorkTimeController::class)
            ->except('show')
            ->parameters(['work-time' => 'workTime'])
            ->whereNumber('workTime')
            ->names('work-time');
        // Work Schedule
        Route::resource('work-schedule', WorkScheduleController::class)
            ->except('show')
            ->parameters(['work-schedule' => 'workSchedule'])
            ->whereNumber('workSchedule')
            ->names('work-schedule');
        // Schedule Assignment
        Route::resource('schedule-assignment', ScheduleAssignmentController::class)
            ->except('show')
            ->parameters(['schedule-assignment' => 'scheduleAssignment'])
            ->whereNumber('scheduleAssignment')
            ->names('schedule-assignment');
        // Holidays
        Route::resource('holiday', HolidayController::class)
            ->only('index', 'store', 'update', 'destroy')
            ->parameters(['holiday' => 'holiday'])
            ->whereNumber('holiday')
            ->names('holiday');
        // Leave Type
        Route::resource('leave-type', LeaveTypeController::class)
            ->only('index', 'store', 'update', 'destroy')
            ->parameters(['leave-type' => 'leaveType'])
            ->whereNumber('leave-type')
            ->names('leave-type');
    });

    Route::prefix('attendance')->name('attendance.')->group(function () {
        Route::get('data', [AttendanceController::class, 'index'])->name('data.index');
        Route::post('process', [AttendanceController::class, 'processAttendance'])->name('data.process');
        Route::get('correction/fetch', [AttendanceCorrectionController::class, 'fetch'])->name('correction.fetch');
        Route::resource('correction', AttendanceCorrectionController::class)
            ->except('show')
            ->parameters(['correction' => 'correction'])
            ->whereNumber('correction')
            ->names('correction');
    });

    Route::prefix('leave')->name('leave.')->group(function () {
        Route::resource('request', LeaveRequestController::class)
            ->except(['show'])
            ->parameters(['request' => 'leaveRequest'])
            ->whereNumber('leaveRequest')
            ->names('request');
        Route::get('approval', [LeaveApprovalController::class, 'index'])->name('approval.index');
        Route::put('approval/{leaveRequest}', [LeaveApprovalController::class, 'update'])->name('approval.update');
    });

    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('monthly', [MonthlyReportController::class, 'index'])->name('monthly.index');
        Route::post('monthly/export', [MonthlyReportController::class, 'export'])->name('monthly.export');
        Route::get('monthly/export/download/{filename}', [MonthlyReportController::class, 'downloadExport'])->name('monthly.export.download');

        Route::get('/late', [LateReportController::class, 'index'])->name('late.index');
        Route::post('/late/export', [LateReportController::class, 'export'])->name('late.export');
        Route::get('/late/export/download/{filename}', [LateReportController::class, 'downloadExport'])->name('late.export.download');
    });

    // Rute untuk streaming file dari storage
    Route::get('/stream/{path}', function ($path) {
        $fullPath = storage_path('app/public/' . $path);

        if (!file_exists($fullPath)) {
            abort(404);
        }

        return response()->file($fullPath);
    })->where('path', '.*')->name('stream.file');
});

// Memuat file rute tambahan
require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
