<?php

namespace App\Http\Controllers;

use App\Models\FieldVisit;
use App\Models\GeographicalArea;
use App\Models\Location;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MapApiController extends Controller
{
    public function getMapData(Request $request): JsonResponse
    {
        $currentUser = auth()->user();
        $isFieldOfficer = $currentUser && $currentUser->isFieldOfficer();

        $areaId = $request->input('area_id');
        $employeeId = $request->input('user_id');

        // Geographical Areas
        $areasQuery = GeographicalArea::with('employees:id,name,employee_code,phone')
            ->where('is_active', true);

        if ($areaId) {
            $areasQuery->where('id', $areaId);
        }

        $areas = $areasQuery->get()->map(function ($area) use ($isFieldOfficer, $currentUser) {
            $assignedStaff = $isFieldOfficer
                ? $area->employees->where('id', $currentUser->id)
                : $area->employees;

            return [
                'id' => $area->id,
                'name' => $area->name,
                'code' => $area->code,
                'city' => $area->city,
                'province' => $area->province_state,
                'center' => [$area->center_lat, $area->center_lng],
                'radius' => $area->radius_meters,
                'color' => $area->color_code,
                'description' => $area->description,
                'employees' => $assignedStaff->map(function ($emp) {
                    return [
                        'id' => $emp->id,
                        'name' => $emp->name,
                        'code' => $emp->employee_code,
                        'role_in_area' => $emp->pivot->role_in_area,
                    ];
                })->values(),
            ];
        });

        // Locations
        $locationsQuery = Location::with('geographicalArea')
            ->where('is_active', true);

        if ($areaId) {
            $locationsQuery->where('geographical_area_id', $areaId);
        }

        $locations = $locationsQuery->get()->map(function ($loc) {
            return [
                'id' => $loc->id,
                'name' => $loc->name,
                'code' => $loc->code,
                'client_name' => $loc->client_name,
                'contact_person' => $loc->contact_person,
                'contact_phone' => $loc->contact_phone,
                'address' => $loc->address,
                'coords' => [$loc->latitude, $loc->longitude],
                'geofence_radius' => $loc->geofence_radius_meters,
                'location_type' => $loc->location_type,
                'priority' => $loc->priority,
                'area_name' => $loc->geographicalArea->name ?? 'Unassigned',
                'area_color' => $loc->geographicalArea->color_code ?? '#2563eb',
            ];
        });

        // Field Officers (Privacy-scoped: Field officers only see their own location)
        if ($isFieldOfficer) {
            $employeesQuery = User::where('id', $currentUser->id)
                ->whereNotNull('current_latitude')
                ->whereNotNull('current_longitude');
        } else {
            $employeesQuery = User::where('role', 'field_officer')
                ->whereNotNull('current_latitude')
                ->whereNotNull('current_longitude');

            if ($employeeId) {
                $employeesQuery->where('id', $employeeId);
            }
        }

        $employees = $employeesQuery->get()->map(function ($emp) {
            return [
                'id' => $emp->id,
                'name' => $emp->name,
                'code' => $emp->employee_code,
                'phone' => $emp->phone,
                'coords' => [$emp->current_latitude, $emp->current_longitude],
                'status' => $emp->status,
                'last_active' => $emp->last_active_at ? $emp->last_active_at->diffForHumans() : 'Unknown',
            ];
        });

        // Today's Visits (Privacy-scoped: Field officers only see their assigned visits)
        $todayVisitsQuery = FieldVisit::with(['user:id,name', 'location:id,name,latitude,longitude'])
            ->whereDate('scheduled_at', today());

        if ($isFieldOfficer) {
            $todayVisitsQuery->where('user_id', $currentUser->id);
        }

        $todayVisits = $todayVisitsQuery->get()
            ->map(function ($v) {
                return [
                    'id' => $v->id,
                    'code' => $v->visit_code,
                    'user_name' => $v->user->name ?? 'N/A',
                    'location_name' => $v->location->name ?? 'N/A',
                    'status' => $v->status,
                    'check_in_at' => $v->check_in_at ? $v->check_in_at->format('H:i') : null,
                    'check_in_coords' => ($v->check_in_lat && $v->check_in_lng) ? [$v->check_in_lat, $v->check_in_lng] : null,
                ];
            });

        return response()->json([
            'status' => 'success',
            'data' => [
                'areas' => $areas,
                'locations' => $locations,
                'employees' => $employees,
                'today_visits' => $todayVisits,
            ],
        ]);
    }

    /**
     * Real-time GPS heartbeat beacon from mobile field officer device.
     */
    public function pingLocation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
        ]);

        $user = auth()->user();
        if ($user) {
            $user->update([
                'current_latitude' => $validated['latitude'],
                'current_longitude' => $validated['longitude'],
                'last_active_at' => now(),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
