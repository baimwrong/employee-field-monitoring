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

// Public Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1')->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Quick Demo Login (Guarded inside controller for local development only)
Route::get('/login/quick/{role}', [AuthController::class, 'quickLogin'])->name('login.quick');

// Protected Operational Routes (Require Authenticated Session)
Route::middleware('auth')->group(function () {
    // Dashboard & Live Monitoring
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/map-data', [MapApiController::class, 'getMapData'])
        ->middleware('throttle:60,1')
        ->name('api.mapData');
    Route::post('/api/officer/ping-location', [MapApiController::class, 'pingLocation'])
        ->middleware('throttle:60,1')
        ->name('api.pingLocation');
    Route::get('/api/keep-alive', fn () => response()->json([
        'status' => 'alive',
        'timestamp' => now()->toIso8601String(),
    ]))->name('api.keepAlive');

    // Self-Service Authenticated Profile Actions
    Route::put('/profile/password', [UserController::class, 'updatePassword'])->name('profile.password');

    // Staff & Role Management (Restricted to Administrators & Supervisors)
    Route::prefix('users')->name('users.')->middleware('role:admin,supervisor')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');

        // Mutating actions restricted to Administrators
        Route::middleware('role:admin')->group(function () {
            Route::post('/', [UserController::class, 'store'])->name('store');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
            Route::get('/import/template', [UserController::class, 'downloadImportTemplate'])->name('importTemplate');
            Route::post('/import', [UserController::class, 'importCsv'])->name('import');
        });
    });

    // Geographical Areas & Employee Assignments
    Route::prefix('areas')->name('areas.')->group(function () {
        Route::get('/', [GeographicalAreaController::class, 'index'])->name('index');

        // Zone configuration restricted to Administrators
        Route::middleware('role:admin')->group(function () {
            Route::post('/', [GeographicalAreaController::class, 'store'])->name('store');
            Route::put('/{area}', [GeographicalAreaController::class, 'update'])->name('update');
            Route::delete('/{area}', [GeographicalAreaController::class, 'destroy'])->name('destroy');
        });

        // Territory employee assignment allowed for Admin and Supervisor
        Route::middleware('role:admin,supervisor')->group(function () {
            Route::post('/{area}/assign', [GeographicalAreaController::class, 'assignEmployee'])->name('assign');
            Route::delete('/{area}/employees/{user}', [GeographicalAreaController::class, 'removeEmployee'])->name('removeEmployee');
        });
    });

    // Assigned Locations
    Route::prefix('locations')->name('locations.')->group(function () {
        Route::get('/', [LocationController::class, 'index'])->name('index');

        Route::middleware('role:admin,supervisor')->group(function () {
            Route::post('/', [LocationController::class, 'store'])->name('store');
            Route::put('/{location}', [LocationController::class, 'update'])->name('update');
            Route::get('/import/template', [LocationController::class, 'downloadImportTemplate'])->name('importTemplate');
            Route::post('/import', [LocationController::class, 'importCsv'])->name('import');
        });

        Route::delete('/{location}', [LocationController::class, 'destroy'])
            ->middleware('role:admin')
            ->name('destroy');
    });

    // Field Visits & GPS Geofencing
    Route::prefix('visits')->name('visits.')->group(function () {
        Route::get('/', [FieldVisitController::class, 'index'])->name('index');
        Route::get('/export/csv', [FieldVisitController::class, 'exportCsv'])->name('exportCsv');

        // Dispatch visits (Supervisor / Admin)
        Route::middleware('role:admin,supervisor')->group(function () {
            Route::post('/', [FieldVisitController::class, 'store'])->name('store');
            Route::get('/import/template', [FieldVisitController::class, 'downloadImportTemplate'])->name('importTemplate');
            Route::post('/import', [FieldVisitController::class, 'importCsv'])->name('import');
        });

        // Check-in & Check-out actions (Field Officer, Supervisor, or Admin)
        Route::post('/{visit}/check-in', [FieldVisitController::class, 'checkIn'])->name('checkIn');
        Route::post('/{visit}/check-out', [FieldVisitController::class, 'checkOut'])->name('checkOut');

        // Supervisor force check-out override
        Route::post('/{visit}/force-checkout', [FieldVisitController::class, 'forceCheckOut'])
            ->middleware('role:admin,supervisor')
            ->name('forceCheckOut');

        Route::delete('/{visit}', [FieldVisitController::class, 'destroy'])
            ->middleware('role:admin,supervisor')
            ->name('destroy');
    });

    // Inspection Reports
    Route::prefix('inspections')->name('inspections.')->group(function () {
        Route::get('/', [InspectionReportController::class, 'index'])->name('index');
        Route::get('/export/csv', [InspectionReportController::class, 'exportCsv'])->name('exportCsv');
        Route::get('/create', [InspectionReportController::class, 'create'])->name('create');
        Route::post('/', [InspectionReportController::class, 'store'])->name('store');
        Route::get('/{inspection}', [InspectionReportController::class, 'show'])->name('show');

        // Review & sign-off restricted to Supervisors & Administrators
        Route::post('/{inspection}/review', [InspectionReportController::class, 'review'])
            ->middleware('role:admin,supervisor')
            ->name('review');
    });

    // Operational Activity Audit Trail (Restricted to Administrators & Supervisors)
    Route::get('/activities', [ActivityLogController::class, 'index'])
        ->middleware('role:admin,supervisor')
        ->name('activities.index');
});
