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
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class InspectionReportController extends Controller
{
    /**
     * Display a listing of inspection dossiers with status and score metrics.
     */
    public function index(Request $request): View
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

    /**
     * Display the detailed dossier for a specific inspection.
     */
    public function show(InspectionReport $inspection): View
    {
        $inspection->load(['inspector', 'location.geographicalArea', 'fieldVisit', 'items', 'photos', 'reviewer']);

        return view('inspections.show', compact('inspection'));
    }

    /**
     * Show the form for creating a new inspection dossier.
     */
    public function create(Request $request): View
    {
        $currentUser = auth()->user();
        $isFieldOfficer = $currentUser && $currentUser->isFieldOfficer();

        $visitId = $request->input('visit_id');
        $selectedVisit = $visitId ? FieldVisit::with(['location', 'user'])->find($visitId) : null;

        $locations = Location::where('is_active', true)->get();
        $inspectors = User::where('role', 'field_officer')->get();

        $visitsQuery = FieldVisit::with(['location', 'user'])
            ->whereIn('status', ['in_progress', 'completed', 'scheduled']);

        if ($isFieldOfficer) {
            $visitsQuery->where('user_id', $currentUser->id);
        }

        $availableVisits = $visitsQuery->latest()->take(20)->get();

        return view('inspections.create', compact('selectedVisit', 'locations', 'inspectors', 'availableVisits'));
    }

    /**
     * Store a newly created inspection dossier with checklist items and evidence photos.
     */
    public function store(Request $request): RedirectResponse
    {
        $currentUser = auth()->user();
        $canAssignInspector = $currentUser && $currentUser->hasAnyRole(['admin', 'supervisor']);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'location_id' => 'required|exists:locations,id',
            'user_id' => $canAssignInspector ? 'required|exists:users,id' : 'nullable',
            'field_visit_id' => 'nullable|exists:field_visits,id',
            'category' => 'required|string|max:100',
            'inspection_date' => 'required|date',
            'compliance_score' => 'nullable|numeric|between:0,100',
            'result_grade' => 'nullable|string|in:Passed,Warning / Needs Improvement,Failed',
            'executive_summary' => 'required|string',
            'major_findings' => 'nullable|string',
            'corrective_recommendations' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.item_name' => 'required_with:items|string|max:255',
            'items.*.category' => 'nullable|string|max:100',
            'items.*.status' => 'required_with:items|in:pass,fail,na',
            'items.*.severity' => 'required_with:items|in:low,medium,high,critical',
            'items.*.notes' => 'nullable|string',
            'photos' => 'nullable|array|max:10',
            'photos.*' => 'image|mimes:jpeg,png,jpg,webp|max:8192',
            'photo_captions' => 'nullable|array',
            'photo_captions.*' => 'nullable|string|max:255',
        ]);

        // Anti-spoofing: Field officers are forced to record reports under their own user account
        $inspectorId = $canAssignInspector ? (int) $validated['user_id'] : (int) $currentUser->id;

        // Auto-calculate compliance score and grade from checklist items if provided
        $computedScore = (float) ($validated['compliance_score'] ?? 100.0);
        $computedGrade = $validated['result_grade'] ?? 'Passed';

        if (! empty($validated['items'])) {
            $totalEvaluated = 0;
            $passCount = 0;
            $hasCriticalFailure = false;

            foreach ($validated['items'] as $item) {
                if ($item['status'] !== 'na') {
                    $totalEvaluated++;
                    if ($item['status'] === 'pass') {
                        $passCount++;
                    } elseif ($item['status'] === 'fail' && ($item['severity'] ?? '') === 'critical') {
                        $hasCriticalFailure = true;
                    }
                }
            }

            if ($totalEvaluated > 0) {
                $computedScore = round(($passCount / $totalEvaluated) * 100, 1);
            } else {
                $computedScore = 100.0;
            }

            if ($hasCriticalFailure || $computedScore < 65) {
                $computedGrade = 'Failed';
            } elseif ($computedScore < 85) {
                $computedGrade = 'Warning / Needs Improvement';
            } else {
                $computedGrade = 'Passed';
            }
        }

        $report = DB::transaction(function () use ($validated, $computedScore, $computedGrade, $inspectorId, $request) {
            do {
                $reportNumber = 'RPT-'.Carbon::now()->format('Ym').'-'.strtoupper(Str::random(6));
            } while (InspectionReport::where('report_number', $reportNumber)->exists());

            $createdReport = InspectionReport::create([
                'report_number' => $reportNumber,
                'field_visit_id' => $validated['field_visit_id'] ?? null,
                'location_id' => $validated['location_id'],
                'user_id' => $inspectorId,
                'title' => $validated['title'],
                'category' => $validated['category'],
                'inspection_date' => $validated['inspection_date'],
                'compliance_score' => $computedScore,
                'result_grade' => $computedGrade,
                'status' => 'submitted',
                'executive_summary' => $validated['executive_summary'],
                'major_findings' => $validated['major_findings'] ?? null,
                'corrective_recommendations' => $validated['corrective_recommendations'] ?? null,
            ]);

            // Save checklist items
            if (! empty($validated['items'])) {
                foreach ($validated['items'] as $item) {
                    InspectionItem::create([
                        'inspection_report_id' => $createdReport->id,
                        'item_name' => $item['item_name'],
                        'category' => $item['category'] ?? 'Standard Checklist',
                        'status' => $item['status'],
                        'severity' => $item['severity'] ?? 'low',
                        'notes' => $item['notes'] ?? null,
                    ]);
                }
            }

            // Save evidence photos if uploaded
            if ($request->hasFile('photos')) {
                $captions = $request->input('photo_captions', []);
                foreach ($request->file('photos') as $idx => $photoFile) {
                    $savedPath = $photoFile->store('inspections', 'public');
                    InspectionPhoto::create([
                        'inspection_report_id' => $createdReport->id,
                        'photo_path' => $savedPath,
                        'caption' => $captions[$idx] ?? ('Site Photo #'.($idx + 1)),
                        'captured_at' => now(),
                    ]);
                }
            }

            return $createdReport;
        });

        $inspector = User::find($inspectorId);
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

    /**
     * Record supervisor review and approval decision.
     */
    public function review(Request $request, InspectionReport $inspection): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:approved,rejected,under_review',
            'reviewer_remarks' => 'required|string|max:3000',
        ]);

        $reviewer = auth()->user();
        if (! $reviewer || ! $reviewer->hasAnyRole(['admin', 'supervisor'])) {
            abort(403, 'Unauthorized. Only supervisors and administrators may review inspection reports.');
        }

        DB::transaction(function () use ($inspection, $validated, $reviewer) {
            $inspection->update([
                'status' => $validated['status'],
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
                'reviewer_remarks' => $validated['reviewer_remarks'],
            ]);
        });

        $actionType = $validated['status'] === 'approved' ? 'report_approved' : 'report_rejected';

        ActivityLog::log(
            $actionType,
            "Inspection Report {$inspection->report_number} was {$validated['status']}",
            "Remarks: {$validated['reviewer_remarks']}",
            $inspection,
            $reviewer
        );

        return redirect()->route('inspections.show', $inspection)->with('success', 'Report status updated to '.ucfirst(str_replace('_', ' ', $validated['status'])).'.');
    }
}
