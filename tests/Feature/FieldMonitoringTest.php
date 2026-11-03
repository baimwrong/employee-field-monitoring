<?php

namespace Tests\Feature;

use App\Models\FieldVisit;
use App\Models\GeographicalArea;
use App\Models\InspectionReport;
use App\Models\Location;
use App\Models\User;
use Tests\TestCase;

class FieldMonitoringTest extends TestCase
{
    protected User $admin;

    protected User $supervisor;

    protected User $fieldOfficer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::where('role', 'admin')->first()
            ?? User::factory()->create(['role' => 'admin']);
        $this->supervisor = User::where('role', 'supervisor')->first()
            ?? User::factory()->create(['role' => 'supervisor']);
        $this->fieldOfficer = User::where('role', 'field_officer')->first()
            ?? User::factory()->create(['role' => 'field_officer']);
    }

    public function test_guest_is_redirected_to_login_for_protected_routes(): void
    {
        $response = $this->get('/');
        $response->assertRedirect('/login');

        $apiResponse = $this->getJson('/api/map-data');
        $apiResponse->assertStatus(401);
    }

    public function test_dashboard_page_loads_successfully_for_authenticated_user(): void
    {
        $response = $this->actingAs($this->admin)->get('/');
        $response->assertStatus(200);
        $response->assertSee('Field Operations Command Center');
    }

    public function test_geographical_areas_page_loads_and_displays_assigned_officers(): void
    {
        $response = $this->actingAs($this->admin)->get('/areas');
        $response->assertStatus(200);
        $response->assertSee('Geographical Areas & Territory Assignments');
    }

    public function test_can_assign_employee_to_geographical_area(): void
    {
        $area = GeographicalArea::first();

        $response = $this->actingAs($this->supervisor)->post("/areas/{$area->id}/assign", [
            'user_id' => $this->fieldOfficer->id,
            'role_in_area' => 'Field Safety Officer',
            'assigned_date' => now()->format('Y-m-d'),
            'is_primary' => true,
            'assignment_notes' => 'Test assignment',
        ]);

        $response->assertRedirect('/areas');
        $this->assertDatabaseHas('area_employee', [
            'geographical_area_id' => $area->id,
            'user_id' => $this->fieldOfficer->id,
            'role_in_area' => 'Field Safety Officer',
        ]);
    }

    public function test_assigned_locations_page_loads_successfully(): void
    {
        $response = $this->actingAs($this->admin)->get('/locations');
        $response->assertStatus(200);
        $response->assertSee('Assigned Client Locations', false);
    }

    public function test_can_record_and_dispatch_field_visit(): void
    {
        $location = Location::first();

        $response = $this->actingAs($this->supervisor)->post('/visits', [
            'user_id' => $this->fieldOfficer->id,
            'location_id' => $location->id,
            'scheduled_at' => now()->addDay()->format('Y-m-d\TH:i'),
            'purpose' => 'routine_inspection',
            'visit_summary' => 'Automated test visit dispatch',
        ]);

        $response->assertRedirect('/visits');
        $this->assertDatabaseHas('field_visits', [
            'user_id' => $this->fieldOfficer->id,
            'location_id' => $location->id,
            'status' => 'scheduled',
        ]);
    }

    public function test_can_record_gps_checkin_for_field_visit(): void
    {
        $location = Location::first();
        $visit = FieldVisit::create([
            'visit_code' => 'TEST-VST-'.rand(1000, 9999),
            'user_id' => $this->fieldOfficer->id,
            'location_id' => $location->id,
            'geographical_area_id' => $location->geographical_area_id,
            'scheduled_at' => now(),
            'purpose' => 'routine_inspection',
            'status' => 'scheduled',
        ]);

        $response = $this->actingAs($this->fieldOfficer)->post("/visits/{$visit->id}/check-in", [
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
        $response = $this->actingAs($this->admin)->get('/inspections');
        $response->assertStatus(200);
        $response->assertSee('Track Inspection Reports');

        $report = InspectionReport::first();
        if ($report) {
            $showResponse = $this->actingAs($this->admin)->get("/inspections/{$report->id}");
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
        $response = $this->actingAs($this->admin)->get('/users');
        $response->assertStatus(200);
        $response->assertSee('Staff & Role Management');
    }

    public function test_can_create_new_staff_member(): void
    {
        $code = 'FLD-'.rand(1000, 9999);
        $response = $this->actingAs($this->admin)->post('/users', [
            'name' => 'Budi Santoso',
            'email' => 'budi.'.rand(1000, 9999).'@fieldmonitor.com',
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

    public function test_non_admin_cannot_create_staff_member(): void
    {
        $response = $this->actingAs($this->fieldOfficer)->post('/users', [
            'name' => 'Unauthorized Member',
            'email' => 'unauth@fieldmonitor.com',
            'employee_code' => 'FLD-9999',
            'role' => 'admin',
            'status' => 'active',
            'password' => 'password123',
        ]);

        $response->assertStatus(403);
    }

    public function test_activity_history_page_loads(): void
    {
        $response = $this->actingAs($this->admin)->get('/activities');
        $response->assertStatus(200);
        $response->assertSee('Operational Activity History');
    }

    public function test_cannot_check_in_already_in_progress_or_completed_visit(): void
    {
        $location = Location::first();
        $visit = FieldVisit::create([
            'visit_code' => 'TEST-VST-'.rand(1000, 9999),
            'user_id' => $this->fieldOfficer->id,
            'location_id' => $location->id,
            'geographical_area_id' => $location->geographical_area_id,
            'scheduled_at' => now(),
            'purpose' => 'routine_inspection',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->fieldOfficer)->post("/visits/{$visit->id}/check-in", [
            'latitude' => $visit->location->latitude,
            'longitude' => $visit->location->longitude,
            'notes' => 'Attempting duplicate checkin',
        ]);

        $response->assertRedirect('/visits');
        $response->assertSessionHas('warning');
        $visit->refresh();
        $this->assertEquals('completed', $visit->status);
    }

    public function test_inspection_report_auto_calculates_compliance_score_and_grade(): void
    {
        $location = Location::first();

        $response = $this->actingAs($this->fieldOfficer)->post('/inspections', [
            'title' => 'Electrical Safety Audit',
            'location_id' => $location->id,
            'user_id' => $this->fieldOfficer->id,
            'category' => 'Safety & Quality',
            'inspection_date' => now()->format('Y-m-d'),
            'executive_summary' => 'Comprehensive walkthrough testing checklist auto-score.',
            'items' => [
                [
                    'item_name' => 'Fire Extinguishers Inspected',
                    'category' => 'Safety',
                    'status' => 'pass',
                    'severity' => 'medium',
                ],
                [
                    'item_name' => 'Emergency Exits Clear',
                    'category' => 'Safety',
                    'status' => 'pass',
                    'severity' => 'high',
                ],
                [
                    'item_name' => 'Hazard Signage Posted',
                    'category' => 'Signage',
                    'status' => 'fail',
                    'severity' => 'low',
                ],
                [
                    'item_name' => 'Solar Backup Active',
                    'category' => 'Power',
                    'status' => 'na',
                    'severity' => 'low',
                ],
            ],
        ]);

        $report = InspectionReport::where('title', 'Electrical Safety Audit')->latest()->first();
        $this->assertNotNull($report);
        // 2 passed out of 3 evaluable (1 is N/A) = 66.7%
        $this->assertEquals(66.7, (float) $report->compliance_score);
        $this->assertEquals('Warning / Needs Improvement', $report->result_grade);
    }

    public function test_supervisor_review_attributes_to_authenticated_user(): void
    {
        $location = Location::first();
        $report = InspectionReport::create([
            'report_number' => 'TEST-RPT-'.rand(1000, 9999),
            'location_id' => $location->id,
            'user_id' => $this->fieldOfficer->id,
            'title' => 'Test Review Attribution Report',
            'category' => 'Safety & Quality',
            'inspection_date' => now(),
            'compliance_score' => 90.0,
            'result_grade' => 'Passed',
            'status' => 'submitted',
            'executive_summary' => 'Walkthrough completed.',
        ]);

        $response = $this->actingAs($this->supervisor)->post("/inspections/{$report->id}/review", [
            'status' => 'approved',
            'reviewer_remarks' => 'Reviewed and officially approved by Sarah.',
        ]);

        $response->assertRedirect("/inspections/{$report->id}");
        $report->refresh();
        $this->assertEquals('approved', $report->status);
        $this->assertEquals($this->supervisor->id, $report->reviewed_by);
    }

    public function test_map_api_returns_valid_geojson_data(): void
    {
        $response = $this->actingAs($this->admin)->getJson('/api/map-data');
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

    public function test_field_officer_cannot_access_staff_directory(): void
    {
        $response = $this->actingAs($this->fieldOfficer)->get('/users');
        $response->assertStatus(403);

        $supervisorResponse = $this->actingAs($this->supervisor)->get('/users');
        $supervisorResponse->assertStatus(200);
    }

    public function test_field_officer_cannot_access_activity_audit_logs(): void
    {
        $response = $this->actingAs($this->fieldOfficer)->get('/activities');
        $response->assertStatus(403);

        $supervisorResponse = $this->actingAs($this->supervisor)->get('/activities');
        $supervisorResponse->assertStatus(200);
    }

    public function test_map_api_scopes_employee_visibility_for_field_officer(): void
    {
        $response = $this->actingAs($this->fieldOfficer)->getJson('/api/map-data');
        $response->assertStatus(200);

        $employees = $response->json('data.employees');
        foreach ($employees as $employee) {
            $this->assertEquals($this->fieldOfficer->id, $employee['id']);
        }
    }

    public function test_field_officer_cannot_spoof_inspector_id_on_report_submission(): void
    {
        $otherOfficer = User::where('role', 'field_officer')->where('id', '!=', $this->fieldOfficer->id)->first();
        if (! $otherOfficer) {
            $otherOfficer = User::factory()->create(['role' => 'field_officer']);
        }

        $location = Location::first();

        $uniqueTitle = 'Spoof Test Inspection '.uniqid();

        $response = $this->actingAs($this->fieldOfficer)->post('/inspections', [
            'title' => $uniqueTitle,
            'location_id' => $location->id,
            'user_id' => $otherOfficer->id, // Attempting to file under colleague's identity
            'category' => 'Safety & Quality',
            'inspection_date' => now()->format('Y-m-d'),
            'executive_summary' => 'Tested spoofing guard.',
        ]);

        $report = InspectionReport::where('title', $uniqueTitle)->first();
        $this->assertNotNull($report);
        // Assert that inspector identity was automatically overridden to the authenticated officer
        $this->assertEquals($this->fieldOfficer->id, $report->user_id);
    }

    public function test_cannot_delete_user_with_historical_records(): void
    {
        $userWithVisits = User::whereHas('fieldVisits')->first();
        $this->assertNotNull($userWithVisits);

        $response = $this->actingAs($this->admin)->delete("/users/{$userWithVisits->id}");
        $response->assertSessionHas('warning');
        $this->assertDatabaseHas('users', ['id' => $userWithVisits->id]);
    }

    public function test_cannot_delete_location_with_historical_records(): void
    {
        $locationWithVisits = Location::whereHas('fieldVisits')->first();
        $this->assertNotNull($locationWithVisits);

        $response = $this->actingAs($this->admin)->delete("/locations/{$locationWithVisits->id}");
        $response->assertSessionHas('warning');
        $this->assertDatabaseHas('locations', ['id' => $locationWithVisits->id]);
    }
}
