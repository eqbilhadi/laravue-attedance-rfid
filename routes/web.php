<?php

use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AccessSettings\NavManagementController;
use App\Http\Controllers\AccessSettings\RoleManagementController;
use App\Http\Controllers\AccessSettings\UserManagementController;
use App\Http\Controllers\AccessSettings\PermissionManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RfidManagement\DevicesController;
use App\Http\Controllers\RfidManagement\LogScanController;
use App\Http\Controllers\RfidManagement\RegisterNewCard;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('dashboard', [DashboardController::class, 'index'])->middleware(['auth', 'verified'])->name('dashboard');

Route::group(['middleware' => ['auth'], 'prefix' => 'rbac', 'as' => 'rbac.'], function () {
    Route::resource('navigation-management', NavManagementController::class)
        ->except('show')
        ->names('nav')
        ->parameters([
            'navigation-management' => 'sysMenu'
        ])
        ->whereNumber('sysMenu');
    
    Route::get('navigation-management/sort', [NavManagementController::class, 'sort'])
        ->name('nav.sort');
    
    Route::post('navigation-management/sort', [NavManagementController::class, 'sortUpdate'])
        ->name('nav.sort-update');

    Route::resource('permission-management', PermissionManagementController::class)
        ->except(['show', 'create', 'edit'])
        ->names('permission')
        ->parameters([
            'permission-management' => 'sysPermission'
        ])
        ->whereNumber('sysPermission');

    Route::resource('role-management', RoleManagementController::class)
        ->except('show')
        ->names('role')
        ->parameters([
            'role-management' => 'sysRole'
        ])
        ->whereNumber('sysRole');
    
    Route::resource('user-management', UserManagementController::class)
        ->except('show')
        ->names('user')
        ->parameters([
            'user-management' => 'sysUser'
        ])
        ->whereUuid('sysUser');
});

Route::group(['middleware' => ['auth'], 'prefix' => 'rfid-management', 'as' => 'rfid-management.'], function () {
    Route::resource('register-new-card', RegisterNewCard::class)
        ->only('index', 'store')
        ->names('register-new-card');

    Route::get('/devices/check-exists', [DevicesController::class, 'checkExists'])->name('devices.check');
    Route::resource('devices', DevicesController::class)
        ->names('devices')
        ->only('index', 'destroy', 'store', 'update');
    
    Route::get('/log-scan', [LogScanController::class, 'index'])->name('log-scan.index');
});

Route::get('/stream/{path}', function ($path) {
    $fullPath = storage_path('app/public/' . $path);

    if (!file_exists($fullPath)) {
        abort(404);
    }

    return response()->file($fullPath);
})->where('path', '.*')->name('stream.file');

Route::get('/user-search', [UserManagementController::class, 'search']);

require __DIR__ . '/settings.php';
require __DIR__ . '/auth.php';
