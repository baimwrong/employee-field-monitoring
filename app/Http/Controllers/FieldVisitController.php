<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\FieldVisit;
use App\Models\GeographicalArea;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FieldVisitController extends Controller
{
    /**
     * Display a listing of field visits with filters.
     */
    public function index(Request $request): View
    {
        $statusFilter = $request->input('status');
        $employeeFilter = $request->input('user_id');
        $areaFilter = $request->input('area_id');
        $dateFilter = $request->input('date');

        $query = FieldVisit::with(['user', 'location.geographicalArea', 'inspectionReports']);

        $currentUser = auth()->user();
        if ($currentUser && $currentUser->isFieldOfficer()) {
            $query->where('user_id', $currentUser->id);
            $employeeFilter = (string) $currentUser->id;
        } elseif ($employeeFilter) {
            $query->where('user_id', $employeeFilter);
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        if ($areaFilter) {
            $query->where('geographical_area_id', $areaFilter);
        }

        if ($dateFilter) {
            $query->whereDate('scheduled_at', $dateFilter);
        }

        $visits = $query->latest('scheduled_at')->paginate(12)->withQueryString();

        $employees = ($currentUser && $currentUser->isFieldOfficer())
            ? User::where('id', $currentUser->id)->get()
            : User::where('role', 'field_officer')->get();
        $areas = GeographicalArea::where('is_active', true)->get();
        $locations = Location::where('is_active', true)->get();

        return view('visits.index', compact('visits', 'employees', 'areas', 'locations', 'statusFilter', 'employeeFilter', 'areaFilter', 'dateFilter'));
    }

    /**
     * Store and schedule a new field visit.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'location_id' => 'required|exists:locations,id',
            'scheduled_at' => 'required|date',
            'purpose' => 'required|string|in:routine_inspection,audit,maintenance,survey,emergency,client_meeting',
            'visit_summary' => 'nullable|string',
        ]);

        $location = Location::findOrFail($validated['location_id']);

        $visit = DB::transaction(function () use ($validated, $location) {
            do {
                $code = 'VST-'.Carbon::now()->format('Ymd').'-'.strtoupper(Str::random(6));
            } while (FieldVisit::where('visit_code', $code)->exists());

            return FieldVisit::create([
                'visit_code' => $code,
                'user_id' => $validated['user_id'],
                'location_id' => $validated['location_id'],
                'geographical_area_id' => $location->geographical_area_id,
                'scheduled_at' => $validated['scheduled_at'],
                'purpose' => $validated['purpose'],
                'status' => 'scheduled',
                'visit_summary' => $validated['visit_summary'] ?? null,
            ]);
        });

        $officer = User::find($validated['user_id']);

        ActivityLog::log(
            'visit_scheduled',
            "Scheduled Visit {$visit->visit_code} for {$officer->name}",
            "Destination: {$location->name} ({$location->client_name}) on {$visit->scheduled_at->format('M d, Y H:i')}",
            $visit,
            $officer
        );

        return redirect()->route('visits.index')->with('success', "Field Visit {$visit->visit_code} recorded & scheduled successfully.");
    }

    /**
     * Record GPS Check-in with geofence verification and photo proof.
     */
    public function checkIn(Request $request, FieldVisit $visit): RedirectResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'check_in_photo' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:8192',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Authorization check: Only assigned officer, supervisor, or admin can check in
        $currentUser = auth()->user();
        if ($currentUser && $currentUser->id !== $visit->user_id && ! $currentUser->hasAnyRole(['admin', 'supervisor'])) {
            abort(403, 'Unauthorized. You are not assigned to this field visit.');
        }

        $photoPath = null;
        if ($request->hasFile('check_in_photo')) {
            $photoPath = $request->file('check_in_photo')->store('visits', 'public');
        }

        $result = DB::transaction(function () use ($visit, $validated, $photoPath) {
            $lockedVisit = FieldVisit::where('id', $visit->id)->lockForUpdate()->firstOrFail();

            if ($lockedVisit->status !== 'scheduled') {
                return [
                    'success' => false,
                    'message' => "Visit {$lockedVisit->visit_code} is already {$lockedVisit->status} and cannot be checked in.",
                ];
            }

            // Concurrency guard: Ensure assigned officer does not have another active in-progress visit
            $activeOtherVisit = FieldVisit::with('location')
                ->where('user_id', $lockedVisit->user_id)
                ->where('id', '!=', $lockedVisit->id)
                ->where('status', 'in_progress')
                ->first();

            if ($activeOtherVisit) {
                $siteName = $activeOtherVisit->location->name ?? 'site';

                return [
                    'success' => false,
                    'message' => "Officer already has an active visit in progress ({$activeOtherVisit->visit_code} at {$siteName}). Please check out of that visit before checking in to this site.",
                ];
            }

            $location = $lockedVisit->location;
            $distance = $this->calculateDistanceMeters(
                (float) $validated['latitude'],
                (float) $validated['longitude'],
                (float) $location->latitude,
                (float) $location->longitude
            );

            $isWithin = $distance <= $location->geofence_radius_meters;

            $lockedVisit->update([
                'status' => 'in_progress',
                'check_in_at' => now(),
                'check_in_lat' => $validated['latitude'],
                'check_in_lng' => $validated['longitude'],
                'check_in_distance_meters' => $distance,
                'is_within_geofence' => $isWithin,
                'check_in_photo' => $photoPath ?? $lockedVisit->check_in_photo,
                'visit_summary' => ! empty($validated['notes'])
                    ? ($lockedVisit->visit_summary."\nCheck-in note: ".$validated['notes'])
                    : $lockedVisit->visit_summary,
            ]);

            // Update assigned employee live coordinates
            $lockedVisit->user->update([
                'current_latitude' => $validated['latitude'],
                'current_longitude' => $validated['longitude'],
                'last_active_at' => now(),
            ]);

            return [
                'success' => true,
                'distance' => $distance,
                'isWithin' => $isWithin,
                'visit' => $lockedVisit,
                'location' => $location,
            ];
        });

        if (! $result['success']) {
            return redirect()->route('visits.index')->with('warning', $result['message']);
        }

        $distance = $result['distance'];
        $isWithin = $result['isWithin'];
        $geofenceMsg = $isWithin
            ? "Verified within {$distance}m (Geofence OK)"
            : "Notice: {$distance}m from site (Designated geofence: {$result['location']->geofence_radius_meters}m)";

        ActivityLog::log(
            'visit_checkin',
            "Check-In recorded for {$visit->visit_code} at {$result['location']->name}",
            "Coordinates: [{$validated['latitude']}, {$validated['longitude']}]. {$geofenceMsg}",
            $visit,
            $visit->user,
            (float) $validated['latitude'],
            (float) $validated['longitude']
        );

        return redirect()->route('visits.index')->with(
            $isWithin ? 'success' : 'warning',
            "GPS Check-in recorded! Distance: {$distance}m. ".($isWithin ? 'Location verified within geofence.' : 'Notice: Outside designated geofence radius.')
        );
    }

    /**
     * Record GPS Check-Out and calculate accurate on-site duration.
     */
    public function checkOut(Request $request, FieldVisit $visit): RedirectResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'visit_summary' => 'required|string|max:3000',
            'client_representative_name' => 'nullable|string|max:255',
        ]);

        $currentUser = auth()->user();
        if ($currentUser && $currentUser->id !== $visit->user_id && ! $currentUser->hasAnyRole(['admin', 'supervisor'])) {
            abort(403, 'Unauthorized. You are not assigned to this field visit.');
        }

        $result = DB::transaction(function () use ($visit, $validated) {
            $lockedVisit = FieldVisit::where('id', $visit->id)->lockForUpdate()->firstOrFail();

            if ($lockedVisit->status !== 'in_progress') {
                return [
                    'success' => false,
                    'message' => "Visit {$lockedVisit->visit_code} must be in-progress to check out (Current: {$lockedVisit->status}).",
                ];
            }

            $checkInTime = $lockedVisit->check_in_at ?? now();
            $durationMinutes = max(1, (int) round($checkInTime->diffInMinutes(now())));

            $lockedVisit->update([
                'status' => 'completed',
                'check_out_at' => now(),
                'check_out_lat' => $validated['latitude'],
                'check_out_lng' => $validated['longitude'],
                'duration_minutes' => $durationMinutes,
                'visit_summary' => $validated['visit_summary'],
                'client_representative_name' => $validated['client_representative_name'] ?? null,
            ]);

            $lockedVisit->user->update([
                'current_latitude' => $validated['latitude'],
                'current_longitude' => $validated['longitude'],
                'last_active_at' => now(),
            ]);

            return [
                'success' => true,
                'duration' => $durationMinutes,
                'visit' => $lockedVisit,
            ];
        });

        if (! $result['success']) {
            return redirect()->route('visits.index')->with('warning', $result['message']);
        }

        ActivityLog::log(
            'visit_checkout',
            "Completed field visit {$visit->visit_code} at {$visit->location->name}",
            "Total duration: {$result['duration']} mins. Sign-off: ".($validated['client_representative_name'] ?? 'Self'),
            $visit,
            $visit->user,
            (float) $validated['latitude'],
            (float) $validated['longitude']
        );

        return redirect()->route('visits.index')->with('success', "Visit {$visit->visit_code} completed successfully. Duration: {$result['duration']} minutes.");
    }

    /**
     * Supervisor override to force check-out an abandoned/stale in-progress visit.
     */
    public function forceCheckOut(Request $request, FieldVisit $visit): RedirectResponse
    {
        $validated = $request->validate([
            'visit_summary' => 'required|string|max:1000',
        ]);

        if ($visit->status !== 'in_progress') {
            return redirect()->route('visits.index')->with('warning', "Visit {$visit->visit_code} must be in-progress to force check-out (Current: {$visit->status}).");
        }

        $checkInTime = $visit->check_in_at ?? now();
        $durationMinutes = max(1, (int) round($checkInTime->diffInMinutes(now())));
        $supervisor = auth()->user();

        $visit->update([
            'status' => 'completed',
            'check_out_at' => now(),
            'duration_minutes' => $durationMinutes,
            'visit_summary' => $visit->visit_summary."\n[Force Checked-Out by Supervisor {$supervisor->name}]: ".$validated['visit_summary'],
            'client_representative_name' => "Supervisor Override ({$supervisor->name})",
        ]);

        ActivityLog::log(
            'visit_force_checkout',
            "Supervisor {$supervisor->name} force-closed visit {$visit->visit_code}",
            "Reason: {$validated['visit_summary']}",
            $visit,
            $supervisor
        );

        return redirect()->route('visits.index')->with('success', "Visit {$visit->visit_code} was force-checked out successfully.");
    }

    /**
     * Export filtered field visits to CSV format.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $statusFilter = $request->input('status');
        $employeeFilter = $request->input('user_id');
        $areaFilter = $request->input('area_id');
        $dateFilter = $request->input('date');

        $query = FieldVisit::with(['user', 'location.geographicalArea']);

        $currentUser = auth()->user();
        if ($currentUser && $currentUser->isFieldOfficer()) {
            $query->where('user_id', $currentUser->id);
        } elseif ($employeeFilter) {
            $query->where('user_id', $employeeFilter);
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }
        if ($areaFilter) {
            $query->where('geographical_area_id', $areaFilter);
        }
        if ($dateFilter) {
            $query->whereDate('scheduled_at', $dateFilter);
        }

        $visits = $query->latest('scheduled_at')->get();
        $filename = 'field_visits_'.date('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($visits) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Visit Code',
                'Officer Name',
                'Employee Code',
                'Location Name',
                'Zone / Area',
                'Scheduled Date',
                'Status',
                'Check-In Time',
                'Check-In Distance (m)',
                'Within Geofence',
                'Check-Out Time',
                'Duration (Minutes)',
                'Client Sign-Off',
                'Summary / Notes',
            ]);

            foreach ($visits as $v) {
                fputcsv($handle, [
                    $v->visit_code,
                    $v->user->name ?? 'N/A',
                    $v->user->employee_code ?? 'N/A',
                    $v->location->name ?? 'N/A',
                    $v->location->geographicalArea->name ?? 'N/A',
                    $v->scheduled_at ? $v->scheduled_at->format('Y-m-d H:i') : '',
                    ucfirst($v->status),
                    $v->check_in_at ? $v->check_in_at->format('Y-m-d H:i:s') : '',
                    $v->check_in_distance_meters ?? '',
                    $v->is_within_geofence ? 'Yes' : 'No',
                    $v->check_out_at ? $v->check_out_at->format('Y-m-d H:i:s') : '',
                    $v->duration_minutes ?? '',
                    $v->client_representative_name ?? '',
                    $v->visit_summary ?? '',
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    /**
     * Delete/cancel a field visit.
     */
    public function destroy(FieldVisit $visit): RedirectResponse
    {
        $code = $visit->visit_code;
        $visit->delete();

        ActivityLog::log('visit_cancelled', "Cancelled/Deleted visit {$code}");

        return redirect()->route('visits.index')->with('success', "Visit {$code} deleted.");
    }

    /**
     * Calculate Haversine great-circle distance between two GPS points in meters.
     */
    private function calculateDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): int
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return (int) round($earthRadius * $c);
    }
}
