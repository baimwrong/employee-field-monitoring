<?php

namespace Tests\Feature;

use App\Models\GeographicalArea;
use App\Models\Location;
use App\Models\User;
use App\Models\FieldVisit;
use App\Models\InspectionReport;
use Tests\TestCase;

class FieldMonitoringTest extends TestCase
{
    public function test_dashboard_page_loads_successfully(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('Field Operations Command Center');
    }

    public function test_geographical_areas_page_loads_and_displays_assigned_officers(): void
    {
        $response = $this->get('/areas');
        $response->assertStatus(200);
        $response->assertSee('Geographical Areas & Territory Assignments');
    }

    public function test_can_assign_employee_to_geographical_area(): void
    {
        $area = GeographicalArea::first();
        $officer = User::where('role', 'field_officer')->first();

        $response = $this->post("/areas/{$area->id}/assign", [
            'user_id' => $officer->id,
            'role_in_area' => 'Field Safety Officer',
            'assigned_date' => now()->format('Y-m-d'),
            'is_primary' => true,
            'assignment_notes' => 'Test assignment',
        ]);

        $response->assertRedirect('/areas');
        $this->assertDatabaseHas('area_employee', [
            'geographical_area_id' => $area->id,
            'user_id' => $officer->id,
            'role_in_area' => 'Field Safety Officer',
        ]);
    }

    public function test_assigned_locations_page_loads_successfully(): void
    {
        $response = $this->get('/locations');
        $response->assertStatus(200);
        $response->assertSee('Assigned Client Locations', false);
    }

    public function test_can_record_and_dispatch_field_visit(): void
    {
        $officer = User::where('role', 'field_officer')->first();
        $location = Location::first();

        $response = $this->post('/visits', [
            'user_id' => $officer->id,
            'location_id' => $location->id,
            'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'purpose' => 'routine_inspection',
            'visit_summary' => 'Automated test visit dispatch',
        ]);

        $response->assertRedirect('/visits');
        $this->assertDatabaseHas('field_visits', [
            'user_id' => $officer->id,
            'location_id' => $location->id,
            'status' => 'scheduled',
        ]);
    }

    public function test_can_record_gps_checkin_for_field_visit(): void
    {
        $visit = FieldVisit::where('status', 'scheduled')->first();

        $response = $this->post("/visits/{$visit->id}/check-in", [
            'latitude' => $visit->location->latitude,
            'longitude' => $visit->location->longitude,
            'notes' => 'GPS checkin verified on site',
        ]);

        $response->assertRedirect('/visits');
        $visit->refresh();
        $this->assertEquals('in_progress', $visit->status);
        $this->assertTrue($visit->is_within_geofence);
    }

    public function test_inspection_reports_page_loads_and_dossier_accessible(): void
    {
        $response = $this->get('/inspections');
        $response->assertStatus(200);
        $response->assertSee('Track Inspection Reports');

        $report = InspectionReport::first();
        if ($report) {
            $showResponse = $this->get("/inspections/{$report->id}");
            $showResponse->assertStatus(200);
            $showResponse->assertSee($report->report_number);
        }
    }

    public function test_login_page_loads(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Account Sign In');
    }

    public function test_staff_management_page_loads_and_displays_users(): void
    {
        $response = $this->get('/users');
        $response->assertStatus(200);
        $response->assertSee('Staff & Role Management');
    }

    public function test_can_create_new_staff_member(): void
    {
        $code = 'FLD-' . rand(1000, 9999);
        $response = $this->post('/users', [
            'name' => 'Budi Santoso',
            'email' => 'budi.' . rand(1000, 9999) . '@fieldmonitor.com',
            'employee_code' => $code,
            'phone' => '+62 811-2233-4455',
            'role' => 'field_officer',
            'status' => 'active',
            'password' => 'password123',
        ]);

        $response->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'employee_code' => $code,
            'role' => 'field_officer',
        ]);
    }

    public function test_activity_history_page_loads(): void
    {
        $response = $this->get('/activities');
        $response->assertStatus(200);
        $response->assertSee('Operational Activity History');
    }

    public function test_map_api_returns_valid_geojson_data(): void
    {
        $response = $this->get('/api/map-data');
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'data' => [
                'areas',
                'locations',
                'employees',
                'today_visits',
            ],
        ]);
    }
}
