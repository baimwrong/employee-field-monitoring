<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\GeographicalArea;
use App\Models\User;
use Illuminate\Http\Request;

class GeographicalAreaController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        
        $areasQuery = GeographicalArea::with(['employees', 'locations'])
            ->withCount(['employees', 'locations', 'fieldVisits']);

        if ($search) {
            $areasQuery->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('code', 'ilike', "%{$search}%")
                  ->orWhere('city', 'ilike', "%{$search}%");
            });
        }

        $areas = $areasQuery->latest()->paginate(10)->withQueryString();
        $allEmployees = User::where('role', 'field_officer')->where('status', 'active')->get();

        return view('areas.index', compact('areas', 'allEmployees', 'search'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:geographical_areas,code',
            'city' => 'nullable|string|max:100',
            'province_state' => 'nullable|string|max:100',
            'center_lat' => 'required|numeric|between:-90,90',
            'center_lng' => 'required|numeric|between:-180,180',
            'radius_meters' => 'required|integer|min:100|max:50000',
            'color_code' => 'required|string|max:20',
            'description' => 'nullable|string',
        ]);

        $area = GeographicalArea::create($validated);

        ActivityLog::log(
            'area_created',
            "Created Geographical Area: {$area->name} ({$area->code})",
            "Radius: {$area->radius_meters}m in {$area->city}",
            $area
        );

        return redirect()->route('areas.index')->with('success', "Geographical Area '{$area->name}' created successfully.");
    }

    public function update(Request $request, GeographicalArea $area)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:geographical_areas,code,' . $area->id,
            'city' => 'nullable|string|max:100',
            'province_state' => 'nullable|string|max:100',
            'center_lat' => 'required|numeric|between:-90,90',
            'center_lng' => 'required|numeric|between:-180,180',
            'radius_meters' => 'required|integer|min:100|max:50000',
            'color_code' => 'required|string|max:20',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $area->update($validated);

        ActivityLog::log(
            'area_updated',
            "Updated Geographical Area: {$area->name}",
            "Updated boundaries and coordinates.",
            $area
        );

        return redirect()->route('areas.index')->with('success', "Area '{$area->name}' updated successfully.");
    }

    public function destroy(GeographicalArea $area)
    {
        $name = $area->name;
        $area->delete();

        ActivityLog::log('area_deleted', "Deleted Geographical Area: {$name}");

        return redirect()->route('areas.index')->with('success', "Area '{$name}' deleted successfully.");
    }

    // Assign Employee to Geographical Area
    public function assignEmployee(Request $request, GeographicalArea $area)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_in_area' => 'required|string|max:100',
            'assigned_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after_or_equal:assigned_date',
            'is_primary' => 'boolean',
            'assignment_notes' => 'nullable|string',
        ]);

        $user = User::findOrFail($validated['user_id']);

        $area->employees()->syncWithoutDetaching([
            $user->id => [
                'role_in_area' => $validated['role_in_area'],
                'assigned_date' => $validated['assigned_date'] ?? now(),
                'expiry_date' => $validated['expiry_date'] ?? null,
                'is_primary' => $request->boolean('is_primary', true),
                'assignment_notes' => $validated['assignment_notes'] ?? null,
            ]
        ]);

        ActivityLog::log(
            'area_assignment',
            "Assigned {$user->name} to {$area->name}",
            "Role: {$validated['role_in_area']}. Notes: " . ($validated['assignment_notes'] ?? 'None'),
            $area,
            $user
        );

        return redirect()->route('areas.index')->with('success', "Assigned employee {$user->name} to area '{$area->name}'.");
    }

    // Detach Employee from Geographical Area
    public function removeEmployee(GeographicalArea $area, User $user)
    {
        $area->employees()->detach($user->id);

        ActivityLog::log(
            'area_unassignment',
            "Removed {$user->name} from {$area->name}",
            "Employee assignment terminated.",
            $area,
            $user
        );

        return redirect()->route('areas.index')->with('success', "Removed {$user->name} from '{$area->name}'.");
    }
}
