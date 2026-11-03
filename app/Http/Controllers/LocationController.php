<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\GeographicalArea;
use App\Models\Location;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function index(Request $request)
    {
        $areaFilter = $request->input('area_id');
        $typeFilter = $request->input('type');
        $search = $request->input('search');

        $query = Location::with('geographicalArea')
            ->withCount('fieldVisits', 'inspectionReports');

        if ($areaFilter) {
            $query->where('geographical_area_id', $areaFilter);
        }

        if ($typeFilter) {
            $query->where('location_type', $typeFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%")
                    ->orWhere('client_name', 'ilike', "%{$search}%")
                    ->orWhere('address', 'ilike', "%{$search}%");
            });
        }

        $locations = $query->latest()->paginate(12)->withQueryString();
        $areas = GeographicalArea::where('is_active', true)->get();

        // All locations for map view
        $allMapLocations = Location::with('geographicalArea')->where('is_active', true)->get();

        return view('locations.index', compact('locations', 'areas', 'allMapLocations', 'areaFilter', 'typeFilter', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'geographical_area_id' => 'required|exists:geographical_areas,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:locations,code',
            'client_name' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'geofence_radius_meters' => 'required|integer|min:20|max:2000',
            'location_type' => 'required|string|max:50',
            'priority' => 'required|string|in:low,normal,high,urgent',
        ]);

        if (empty($validated['code'])) {
            $validated['code'] = 'LOC-'.strtoupper(uniqid());
        }

        $location = Location::create($validated);

        ActivityLog::log(
            'location_created',
            "Added Assigned Location: {$location->name}",
            "Type: {$location->location_type}, Geofence: {$location->geofence_radius_meters}m",
            $location,
            null,
            $location->latitude,
            $location->longitude
        );

        return redirect()->route('locations.index')->with('success', "Location '{$location->name}' added successfully.");
    }

    public function update(Request $request, Location $location)
    {
        $validated = $request->validate([
            'geographical_area_id' => 'required|exists:geographical_areas,id',
            'name' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:locations,code,'.$location->id,
            'client_name' => 'nullable|string|max:255',
            'contact_person' => 'nullable|string|max:100',
            'contact_phone' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'geofence_radius_meters' => 'required|integer|min:20|max:2000',
            'location_type' => 'required|string|max:50',
            'priority' => 'required|string|in:low,normal,high,urgent',
            'is_active' => 'boolean',
        ]);

        $location->update($validated);

        ActivityLog::log(
            'location_updated',
            "Updated Assigned Location: {$location->name}",
            'Coordinates or geofence updated.',
            $location,
            null,
            $location->latitude,
            $location->longitude
        );

        return redirect()->route('locations.index')->with('success', "Location '{$location->name}' updated successfully.");
    }

    public function destroy(Location $location)
    {
        // Data retention guard: prevent cascading erasure of site audit records
        if ($location->fieldVisits()->exists() || $location->inspectionReports()->exists()) {
            return back()->with('warning', "Cannot delete location '{$location->name}' because it has linked historical visits or inspection dossiers. Please mark it as inactive instead to preserve audit records.");
        }

        $name = $location->name;
        $location->delete();

        ActivityLog::log('location_deleted', "Deleted Location: {$name}");

        return redirect()->route('locations.index')->with('success', "Location '{$name}' deleted.");
    }
}
