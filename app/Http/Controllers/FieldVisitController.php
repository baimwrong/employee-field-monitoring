<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\FieldVisit;
use App\Models\GeographicalArea;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;

class FieldVisitController extends Controller
{
    public function index(Request $request)
    {
        $statusFilter = $request->input('status');
        $employeeFilter = $request->input('user_id');
        $areaFilter = $request->input('area_id');
        $dateFilter = $request->input('date');

        $query = FieldVisit::with(['user', 'location.geographicalArea', 'inspectionReports']);

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        if ($employeeFilter) {
            $query->where('user_id', $employeeFilter);
        }

        if ($areaFilter) {
            $query->where('geographical_area_id', $areaFilter);
        }

        if ($dateFilter) {
            $query->whereDate('scheduled_at', $dateFilter);
        }

        $visits = $query->latest('scheduled_at')->paginate(12)->withQueryString();

        $employees = User::where('role', 'field_officer')->get();
        $areas = GeographicalArea::where('is_active', true)->get();
        $locations = Location::where('is_active', true)->get();

        return view('visits.index', compact('visits', 'employees', 'areas', 'locations', 'statusFilter', 'employeeFilter', 'areaFilter', 'dateFilter'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'location_id' => 'required|exists:locations,id',
            'scheduled_at' => 'required|date',
            'purpose' => 'required|string|in:routine_inspection,audit,maintenance,survey,emergency,client_meeting',
            'visit_summary' => 'nullable|string',
        ]);

        $location = Location::findOrFail($validated['location_id']);

        $visit = FieldVisit::create([
            'visit_code' => 'VST-' . Carbon::now()->format('Ymd') . '-' . strtoupper(substr(uniqid(), -4)),
            'user_id' => $validated['user_id'],
            'location_id' => $validated['location_id'],
            'geographical_area_id' => $location->geographical_area_id,
            'scheduled_at' => $validated['scheduled_at'],
            'purpose' => $validated['purpose'],
            'status' => 'scheduled',
            'visit_summary' => $validated['visit_summary'] ?? null,
        ]);

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

    // Record GPS Check-in for a field visit
    public function checkIn(Request $request, FieldVisit $visit)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'check_in_photo' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $location = $visit->location;
        $distance = $this->calculateDistanceMeters(
            $validated['latitude'],
            $validated['longitude'],
            $location->latitude,
            $location->longitude
        );

        $isWithin = $distance <= $location->geofence_radius_meters;

        $visit->update([
            'status' => 'in_progress',
            'check_in_at' => now(),
            'check_in_lat' => $validated['latitude'],
            'check_in_lng' => $validated['longitude'],
            'check_in_distance_meters' => $distance,
            'is_within_geofence' => $isWithin,
            'visit_summary' => $validated['notes'] ? ($visit->visit_summary . "\nCheck-in note: " . $validated['notes']) : $visit->visit_summary,
        ]);

        // Update employee's current live coordinates
        $visit->user->update([
            'current_latitude' => $validated['latitude'],
            'current_longitude' => $validated['longitude'],
            'last_active_at' => now(),
        ]);

        $geofenceMsg = $isWithin
            ? "Verified within {$distance}m (Geofence OK)"
            : "Warning: {$distance}m from site (Exceeds {$location->geofence_radius_meters}m geofence)";

        ActivityLog::log(
            'visit_checkin',
            "Check-In recorded for {$visit->visit_code} at {$location->name}",
            "Location: [{$validated['latitude']}, {$validated['longitude']}]. {$geofenceMsg}",
            $visit,
            $visit->user,
            $validated['latitude'],
            $validated['longitude']
        );

        return redirect()->route('visits.index')->with(
            $isWithin ? 'success' : 'warning',
            "GPS Check-in recorded! Distance: {$distance}m. " . ($isWithin ? 'Location verified within geofence.' : 'Notice: Outside designated geofence radius.')
        );
    }

    // Record GPS Check-Out for a field visit
    public function checkOut(Request $request, FieldVisit $visit)
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'visit_summary' => 'required|string',
            'client_representative_name' => 'nullable|string|max:255',
        ]);

        $checkInTime = $visit->check_in_at ?? now();
        $durationMinutes = max(1, (int) round($checkInTime->diffInMinutes(now())));

        $visit->update([
            'status' => 'completed',
            'check_out_at' => now(),
            'check_out_lat' => $validated['latitude'],
            'check_out_lng' => $validated['longitude'],
            'duration_minutes' => $durationMinutes,
            'visit_summary' => $validated['visit_summary'],
            'client_representative_name' => $validated['client_representative_name'] ?? null,
        ]);

        // Update employee location
        $visit->user->update([
            'current_latitude' => $validated['latitude'],
            'current_longitude' => $validated['longitude'],
            'last_active_at' => now(),
        ]);

        ActivityLog::log(
            'visit_checkout',
            "Completed field visit {$visit->visit_code} at {$visit->location->name}",
            "Total on-site duration: {$durationMinutes} mins. Sign-off: " . ($validated['client_representative_name'] ?? 'Self'),
            $visit,
            $visit->user,
            $validated['latitude'],
            $validated['longitude']
        );

        return redirect()->route('visits.index')->with('success', "Visit {$visit->visit_code} completed successfully. Duration: {$durationMinutes} minutes.");
    }

    public function destroy(FieldVisit $visit)
    {
        $code = $visit->visit_code;
        $visit->delete();

        ActivityLog::log('visit_cancelled', "Cancelled/Deleted visit {$code}");

        return redirect()->route('visits.index')->with('success', "Visit {$code} deleted.");
    }

    // Helper: Haversine distance formula in meters
    private function calculateDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): int
    {
        $earthRadius = 6371000; // in meters
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLon / 2) * sin($dLon / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return (int) round($earthRadius * $c);
    }
}
