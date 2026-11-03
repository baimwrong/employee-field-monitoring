<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FieldVisitController;
use App\Http\Controllers\GeographicalAreaController;
use App\Http\Controllers\InspectionReportController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MapApiController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

// Authentication & Quick Role Switcher
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::get('/login/quick/{role}', [AuthController::class, 'quickLogin'])->name('login.quick');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Dashboard
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

// Staff & Role Management (Admin & Supervisor)
Route::prefix('users')->name('users.')->group(function () {
    Route::get('/', [UserController::class, 'index'])->name('index');
    Route::post('/', [UserController::class, 'store'])->name('store');
    Route::put('/{user}', [UserController::class, 'update'])->name('update');
    Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
});

// Geographical Areas & Employee Assignments
Route::prefix('areas')->name('areas.')->group(function () {
    Route::get('/', [GeographicalAreaController::class, 'index'])->name('index');
    Route::post('/', [GeographicalAreaController::class, 'store'])->name('store');
    Route::put('/{area}', [GeographicalAreaController::class, 'update'])->name('update');
    Route::delete('/{area}', [GeographicalAreaController::class, 'destroy'])->name('destroy');
    Route::post('/{area}/assign', [GeographicalAreaController::class, 'assignEmployee'])->name('assign');
    Route::delete('/{area}/employees/{user}', [GeographicalAreaController::class, 'removeEmployee'])->name('removeEmployee');
});

// Assigned Locations
Route::prefix('locations')->name('locations.')->group(function () {
    Route::get('/', [LocationController::class, 'index'])->name('index');
    Route::post('/', [LocationController::class, 'store'])->name('store');
    Route::put('/{location}', [LocationController::class, 'update'])->name('update');
    Route::delete('/{location}', [LocationController::class, 'destroy'])->name('destroy');
});

// Field Visits (Record & Geofencing)
Route::prefix('visits')->name('visits.')->group(function () {
    Route::get('/', [FieldVisitController::class, 'index'])->name('index');
    Route::post('/', [FieldVisitController::class, 'store'])->name('store');
    Route::post('/{visit}/check-in', [FieldVisitController::class, 'checkIn'])->name('checkIn');
    Route::post('/{visit}/check-out', [FieldVisitController::class, 'checkOut'])->name('checkOut');
    Route::delete('/{visit}', [FieldVisitController::class, 'destroy'])->name('destroy');
});

// Inspection Reports
Route::prefix('inspections')->name('inspections.')->group(function () {
    Route::get('/', [InspectionReportController::class, 'index'])->name('index');
    Route::get('/create', [InspectionReportController::class, 'create'])->name('create');
    Route::post('/', [InspectionReportController::class, 'store'])->name('store');
    Route::get('/{inspection}', [InspectionReportController::class, 'show'])->name('show');
    Route::post('/{inspection}/review', [InspectionReportController::class, 'review'])->name('review');
});

// Activity History
Route::get('/activities', [ActivityLogController::class, 'index'])->name('activities.index');

// Map API Data Endpoint
Route::get('/api/map-data', [MapApiController::class, 'getMapData'])->name('api.mapData');
