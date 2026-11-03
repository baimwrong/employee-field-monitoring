<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\FieldVisit;
use App\Models\InspectionItem;
use App\Models\InspectionPhoto;
use App\Models\InspectionReport;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class InspectionReportController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = $request->input('status');
        $gradeFilter = $request->input('grade');
        $categoryFilter = $request->input('category');
        $search = $request->input('search');

        $query = InspectionReport::with(['inspector', 'location.geographicalArea', 'items', 'reviewer']);

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        if ($gradeFilter) {
            $query->where('result_grade', $gradeFilter);
        }

        if ($categoryFilter) {
            $query->where('category', $categoryFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('report_number', 'ilike', "%{$search}%")
                  ->orWhere('title', 'ilike', "%{$search}%")
                  ->orWhereHas('location', function ($lq) use ($search) {
                      $lq->where('name', 'ilike', "%{$search}%");
                  });
            });
        }

        $reports = $query->latest('inspection_date')->paginate(10)->withQueryString();

        $totalReports = InspectionReport::count();
        $passedCount = InspectionReport::where('result_grade', 'Passed')->count();
        $warningCount = InspectionReport::where('result_grade', 'like', '%Warning%')->count();
        $failedCount = InspectionReport::where('result_grade', 'Failed')->count();
        $avgScore = round(InspectionReport::avg('compliance_score') ?? 0, 1);

        $inspectors = User::where('role', 'field_officer')->get();
        $locations = Location::where('is_active', true)->get();

        return view('inspections.index', compact(
            'reports',
            'totalReports',
            'passedCount',
            'warningCount',
            'failedCount',
            'avgScore',
            'inspectors',
            'locations',
            'statusFilter',
            'gradeFilter',
            'categoryFilter',
            'search'
        ));
    }

    public function show(InspectionReport $inspection)
    {
        $inspection->load(['inspector', 'location.geographicalArea', 'fieldVisit', 'items', 'photos', 'reviewer']);
        return view('inspections.show', compact('inspection'));
    }

    public function create(Request $request)
    {
        $visitId = $request->input('visit_id');
        $selectedVisit = $visitId ? FieldVisit::with(['location', 'user'])->find($visitId) : null;

        $locations = Location::where('is_active', true)->get();
        $inspectors = User::where('role', 'field_officer')->get();
        $availableVisits = FieldVisit::with(['location', 'user'])
            ->whereIn('status', ['in_progress', 'completed', 'scheduled'])
            ->latest()
            ->take(20)
            ->get();

        return view('inspections.create', compact('selectedVisit', 'locations', 'inspectors', 'availableVisits'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'location_id' => 'required|exists:locations,id',
            'user_id' => 'required|exists:users,id',
            'field_visit_id' => 'nullable|exists:field_visits,id',
            'category' => 'required|string|max:100',
            'inspection_date' => 'required|date',
            'compliance_score' => 'required|numeric|between:0,100',
            'result_grade' => 'required|string|in:Passed,Warning / Needs Improvement,Failed',
            'executive_summary' => 'required|string',
            'major_findings' => 'nullable|string',
            'corrective_recommendations' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.item_name' => 'required_with:items|string|max:255',
            'items.*.category' => 'nullable|string|max:100',
            'items.*.status' => 'required_with:items|in:pass,fail,na',
            'items.*.severity' => 'required_with:items|in:low,medium,high,critical',
            'items.*.notes' => 'nullable|string',
        ]);

        $reportNumber = 'RPT-' . Carbon::now()->format('Ym') . '-' . strtoupper(substr(uniqid(), -4));

        $report = InspectionReport::create([
            'report_number' => $reportNumber,
            'field_visit_id' => $validated['field_visit_id'] ?? null,
            'location_id' => $validated['location_id'],
            'user_id' => $validated['user_id'],
            'title' => $validated['title'],
            'category' => $validated['category'],
            'inspection_date' => $validated['inspection_date'],
            'compliance_score' => $validated['compliance_score'],
            'result_grade' => $validated['result_grade'],
            'status' => 'submitted',
            'executive_summary' => $validated['executive_summary'],
            'major_findings' => $validated['major_findings'] ?? null,
            'corrective_recommendations' => $validated['corrective_recommendations'] ?? null,
        ]);

        // Save checklist items if provided
        if (!empty($validated['items'])) {
            foreach ($validated['items'] as $item) {
                InspectionItem::create([
                    'inspection_report_id' => $report->id,
                    'item_name' => $item['item_name'],
                    'category' => $item['category'] ?? 'Standard Checklist',
                    'status' => $item['status'],
                    'severity' => $item['severity'] ?? 'low',
                    'notes' => $item['notes'] ?? null,
                ]);
            }
        }

        $inspector = User::find($validated['user_id']);
        $location = Location::find($validated['location_id']);

        ActivityLog::log(
            'inspection_created',
            "Submitted Inspection {$report->report_number}: {$report->title}",
            "Location: {$location->name}. Score: {$report->compliance_score}% ({$report->result_grade})",
            $report,
            $inspector
        );

        return redirect()->route('inspections.show', $report)->with('success', "Inspection report {$report->report_number} submitted successfully.");
    }

    public function review(Request $request, InspectionReport $inspection)
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,under_review',
            'reviewer_remarks' => 'required|string',
        ]);

        $supervisor = User::where('role', 'supervisor')->orWhere('role', 'admin')->first() ?? auth()->user();

        $inspection->update([
            'status' => $validated['status'],
            'reviewed_by' => $supervisor?->id ?? 1,
            'reviewed_at' => now(),
            'reviewer_remarks' => $validated['reviewer_remarks'],
        ]);

        $actionType = $validated['status'] === 'approved' ? 'report_approved' : 'report_rejected';

        ActivityLog::log(
            $actionType,
            "Inspection Report {$inspection->report_number} was {$validated['status']}",
            "Remarks: {$validated['reviewer_remarks']}",
            $inspection,
            $supervisor
        );

        return redirect()->route('inspections.show', $inspection)->with('success', "Report status updated to " . ucfirst(str_replace('_', ' ', $validated['status'])) . ".");
    }
}
