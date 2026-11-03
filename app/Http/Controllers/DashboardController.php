<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\FieldVisit;
use App\Models\GeographicalArea;
use App\Models\InspectionReport;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $totalEmployees = User::where('role', 'field_officer')->count();
        $activeEmployees = User::where('role', 'field_officer')->where('status', 'active')->count();
        $totalAreas = GeographicalArea::where('is_active', true)->count();
        $totalLocations = Location::where('is_active', true)->count();

        $todayVisitsCount = FieldVisit::whereDate('scheduled_at', Carbon::today())->count();
        $completedVisitsCount = FieldVisit::whereDate('scheduled_at', Carbon::today())->where('status', 'completed')->count();
        $inProgressVisitsCount = FieldVisit::where('status', 'in_progress')->count();

        $pendingReportsCount = InspectionReport::whereIn('status', ['submitted', 'under_review'])->count();
        $averageComplianceScore = InspectionReport::where('status', 'approved')->avg('compliance_score') ?? 90.0;

        $recentVisits = FieldVisit::with(['user', 'location', 'geographicalArea'])
            ->latest()
            ->take(6)
            ->get();

        $recentReports = InspectionReport::with(['inspector', 'location'])
            ->latest()
            ->take(5)
            ->get();

        $recentActivities = ActivityLog::with('user')
            ->latest()
            ->take(8)
            ->get();

        $areas = GeographicalArea::select('id', 'name', 'color_code')
            ->where('is_active', true)
            ->get();

        return view('dashboard.index', compact(
            'totalEmployees',
            'activeEmployees',
            'totalAreas',
            'totalLocations',
            'todayVisitsCount',
            'completedVisitsCount',
            'inProgressVisitsCount',
            'pendingReportsCount',
            'averageComplianceScore',
            'recentVisits',
            'recentReports',
            'recentActivities',
            'areas'
        ));
    }
}
