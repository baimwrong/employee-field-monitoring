<?php

namespace Database\Seeders;

use App\Models\ActivityLog;
use App\Models\FieldVisit;
use App\Models\GeographicalArea;
use App\Models\InspectionItem;
use App\Models\InspectionReport;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Users
        $admin = User::create([
            'name' => 'Alexander Vance',
            'email' => 'admin@fieldmonitor.com',
            'employee_code' => 'DIR-001',
            'phone' => '+62 811-9988-7711',
            'role' => 'admin',
            'status' => 'active',
            'current_latitude' => -6.2088,
            'current_longitude' => 106.8456,
            'last_active_at' => now(),
            'password' => Hash::make('password'),
        ]);

        $supervisor = User::create([
            'name' => 'Sarah Jenkins',
            'email' => 'supervisor@fieldmonitor.com',
            'employee_code' => 'SUP-101',
            'phone' => '+62 812-4455-6677',
            'role' => 'supervisor',
            'status' => 'active',
            'current_latitude' => -6.2150,
            'current_longitude' => 106.8250,
            'last_active_at' => now()->subMinutes(15),
            'password' => Hash::make('password'),
        ]);

        $officer1 = User::create([
            'name' => 'Ahmad Rifai',
            'email' => 'ahmad.rifai@fieldmonitor.com',
            'employee_code' => 'FLD-201',
            'phone' => '+62 813-1122-3344',
            'role' => 'field_officer',
            'status' => 'active',
            'current_latitude' => -6.2185,
            'current_longitude' => 106.8210,
            'last_active_at' => now()->subMinutes(5),
            'password' => Hash::make('password'),
        ]);

        $officer2 = User::create([
            'name' => 'Dewi Lestari',
            'email' => 'dewi.lestari@fieldmonitor.com',
            'employee_code' => 'FLD-202',
            'phone' => '+62 813-5566-7788',
            'role' => 'field_officer',
            'status' => 'active',
            'current_latitude' => -6.2890,
            'current_longitude' => 106.8120,
            'last_active_at' => now()->subMinutes(12),
            'password' => Hash::make('password'),
        ]);

        $officer3 = User::create([
            'name' => 'Rian Pratama',
            'email' => 'rian.pratama@fieldmonitor.com',
            'employee_code' => 'FLD-203',
            'phone' => '+62 812-9900-1122',
            'role' => 'field_officer',
            'status' => 'active',
            'current_latitude' => -6.1820,
            'current_longitude' => 106.7750,
            'last_active_at' => now()->subMinutes(30),
            'password' => Hash::make('password'),
        ]);

        $officer4 = User::create([
            'name' => 'Maya Indah',
            'email' => 'maya.indah@fieldmonitor.com',
            'employee_code' => 'FLD-204',
            'phone' => '+62 817-2233-4455',
            'role' => 'field_officer',
            'status' => 'active',
            'current_latitude' => -6.1260,
            'current_longitude' => 106.8790,
            'last_active_at' => now()->subHours(1),
            'password' => Hash::make('password'),
        ]);

        // 2. Create Geographical Areas
        $areaCBD = GeographicalArea::create([
            'name' => 'Central Business District (CBD)',
            'code' => 'ZONE-CBD-01',
            'city' => 'Jakarta Pusat',
            'province_state' => 'DKI Jakarta',
            'center_lat' => -6.2146000,
            'center_lng' => 106.8228000,
            'radius_meters' => 3200,
            'color_code' => '#2563eb', // Royal Blue
            'description' => 'High-density commercial corridor along Sudirman, Thamrin, and Mega Kuningan corporate towers.',
            'is_active' => true,
        ]);

        $areaSouth = GeographicalArea::create([
            'name' => 'South Jakarta Corridor',
            'code' => 'ZONE-SJKT-02',
            'city' => 'Jakarta Selatan',
            'province_state' => 'DKI Jakarta',
            'center_lat' => -6.2842000,
            'center_lng' => 106.8188000,
            'radius_meters' => 3800,
            'color_code' => '#10b981', // Emerald Green
            'description' => 'TB Simatupang office parks, data centers, and major retail hubs in Cilandak & Kemang.',
            'is_active' => true,
        ]);

        $areaWest = GeographicalArea::create([
            'name' => 'West Logistics & Commercial Hub',
            'code' => 'ZONE-WJKT-03',
            'city' => 'Jakarta Barat',
            'province_state' => 'DKI Jakarta',
            'center_lat' => -6.1754000,
            'center_lng' => 106.7720000,
            'radius_meters' => 3500,
            'color_code' => '#f59e0b', // Amber / Orange
            'description' => 'Puri Indah, Kebon Jeruk commercial hubs and logistics dispatch centers.',
            'is_active' => true,
        ]);

        $areaNorth = GeographicalArea::create([
            'name' => 'North Coast Port & Industrial Zone',
            'code' => 'ZONE-NJKT-04',
            'city' => 'Jakarta Utara',
            'province_state' => 'DKI Jakarta',
            'center_lat' => -6.1214000,
            'center_lng' => 106.8821000,
            'radius_meters' => 4500,
            'color_code' => '#8b5cf6', // Indigo / Purple
            'description' => 'Maritime logistics, shipping terminals, cold storage warehouses, and industrial plants.',
            'is_active' => true,
        ]);

        // 3. Assign Employees to Geographical Areas
        $areaCBD->employees()->attach($officer1->id, [
            'role_in_area' => 'Lead Inspector',
            'assigned_date' => Carbon::now()->subMonths(3),
            'expiry_date' => Carbon::now()->addMonths(9),
            'is_primary' => true,
            'assignment_notes' => 'Primary officer responsible for commercial high-rises and bank branches.',
        ]);

        $areaSouth->employees()->attach($officer2->id, [
            'role_in_area' => 'Field Safety Officer',
            'assigned_date' => Carbon::now()->subMonths(2),
            'expiry_date' => Carbon::now()->addMonths(10),
            'is_primary' => true,
            'assignment_notes' => 'Responsible for data centers and healthcare complexes in South Jakarta.',
        ]);

        $areaWest->employees()->attach($officer3->id, [
            'role_in_area' => 'Logistics Quality Inspector',
            'assigned_date' => Carbon::now()->subMonths(1),
            'expiry_date' => Carbon::now()->addMonths(11),
            'is_primary' => true,
            'assignment_notes' => 'Focused on warehouse security, compliance, and distribution points.',
        ]);

        $areaNorth->employees()->attach($officer4->id, [
            'role_in_area' => 'Port Operations Auditor',
            'assigned_date' => Carbon::now()->subMonths(4),
            'expiry_date' => Carbon::now()->addMonths(8),
            'is_primary' => true,
            'assignment_notes' => 'Specializes in maritime health & safety, heavy machinery, and cold chain audits.',
        ]);

        // Secondary cross-assignment (Dewi also covers CBD backup)
        $areaCBD->employees()->attach($officer2->id, [
            'role_in_area' => 'Backup Auditor',
            'assigned_date' => Carbon::now()->subDays(20),
            'expiry_date' => Carbon::now()->addMonths(6),
            'is_primary' => false,
            'assignment_notes' => 'Secondary coverage for peak inspection weeks.',
        ]);

        // 4. Create Locations (Assigned Sites within areas)
        // CBD Locations
        $loc1 = Location::create([
            'geographical_area_id' => $areaCBD->id,
            'name' => 'Wisma Nusantara Tower 1',
            'code' => 'LOC-CBD-001',
            'client_name' => 'Nusantara Pacific Holdings',
            'contact_person' => 'Bambang Kusuma',
            'contact_phone' => '+62 21-5088-1122',
            'address' => 'Jl. M.H. Thamrin No. 59, Gondangdia, Menteng, Jakarta Pusat',
            'latitude' => -6.1926000,
            'longitude' => 106.8239000,
            'geofence_radius_meters' => 120,
            'location_type' => 'Office',
            'priority' => 'high',
            'is_active' => true,
        ]);

        $loc2 = Location::create([
            'geographical_area_id' => $areaCBD->id,
            'name' => 'Pacific Place Retail & Hotel Wing',
            'code' => 'LOC-CBD-002',
            'client_name' => 'Dua Mutiara Retail Group',
            'contact_person' => 'Jessica Tan',
            'contact_phone' => '+62 21-5152-3344',
            'address' => 'SCBD Lot 3-5, Jl. Jend. Sudirman Kav 52-53, Jakarta Selatan',
            'latitude' => -6.2248000,
            'longitude' => 106.8097000,
            'geofence_radius_meters' => 150,
            'location_type' => 'Retail',
            'priority' => 'urgent',
            'is_active' => true,
        ]);

        $loc3 = Location::create([
            'geographical_area_id' => $areaCBD->id,
            'name' => 'Mega Kuningan Cyber Tower II',
            'code' => 'LOC-CBD-003',
            'client_name' => 'Telco Cloud Indonesia',
            'contact_person' => 'Hendro Setiawan',
            'contact_phone' => '+62 21-5760-8899',
            'address' => 'Kawasan Mega Kuningan Barat Kav E4 No. 7, Jakarta Selatan',
            'latitude' => -6.2301000,
            'longitude' => 106.8285000,
            'geofence_radius_meters' => 100,
            'location_type' => 'Infrastructure',
            'priority' => 'high',
            'is_active' => true,
        ]);

        // South Jakarta Locations
        $loc4 = Location::create([
            'geographical_area_id' => $areaSouth->id,
            'name' => 'Simatupang Tier-3 Data Center',
            'code' => 'LOC-SJKT-004',
            'client_name' => 'Apex Hyperscale Data Ltd',
            'contact_person' => 'David Suryadi',
            'contact_phone' => '+62 21-7890-4455',
            'address' => 'Jl. TB Simatupang No. 88, Cilandak Barat, Jakarta Selatan',
            'latitude' => -6.2915000,
            'longitude' => 106.7972000,
            'geofence_radius_meters' => 100,
            'location_type' => 'Infrastructure',
            'priority' => 'urgent',
            'is_active' => true,
        ]);

        $loc5 = Location::create([
            'geographical_area_id' => $areaSouth->id,
            'name' => 'Kemang Village Lifestyle Center',
            'code' => 'LOC-SJKT-005',
            'client_name' => 'Lippo Malls Group',
            'contact_person' => 'Clarissa Gunawan',
            'contact_phone' => '+62 21-2993-7788',
            'address' => 'Jl. Pangeran Antasari No. 36, Kemang, Jakarta Selatan',
            'latitude' => -6.2608000,
            'longitude' => 106.8152000,
            'geofence_radius_meters' => 180,
            'location_type' => 'Retail',
            'priority' => 'normal',
            'is_active' => true,
        ]);

        // West Jakarta Locations
        $loc6 = Location::create([
            'geographical_area_id' => $areaWest->id,
            'name' => 'Puri Logistics Mega Depot',
            'code' => 'LOC-WJKT-006',
            'client_name' => 'FastTrack Express Logistics',
            'contact_person' => 'Rahmat Hidayat',
            'contact_phone' => '+62 21-5830-1177',
            'address' => 'Jl. Puri Lingkar Luar No. 12, Kembangan, Jakarta Barat',
            'latitude' => -6.1852000,
            'longitude' => 106.7364000,
            'geofence_radius_meters' => 200,
            'location_type' => 'Warehouse',
            'priority' => 'high',
            'is_active' => true,
        ]);

        $loc7 = Location::create([
            'geographical_area_id' => $areaWest->id,
            'name' => 'Kebon Jeruk Media Broadcasting Center',
            'code' => 'LOC-WJKT-007',
            'client_name' => 'Nusantara Broadcasting Corp',
            'contact_person' => 'Agus Priyono',
            'contact_phone' => '+62 21-5303-9900',
            'address' => 'Jl. Raya Perjuangan No. 1, Kebon Jeruk, Jakarta Barat',
            'latitude' => -6.1912000,
            'longitude' => 106.7681000,
            'geofence_radius_meters' => 150,
            'location_type' => 'Office',
            'priority' => 'normal',
            'is_active' => true,
        ]);

        // North Coast Port Locations
        $loc8 = Location::create([
            'geographical_area_id' => $areaNorth->id,
            'name' => 'Tanjung Priok Container Terminal 3',
            'code' => 'LOC-NJKT-008',
            'client_name' => 'Pelindo Terminal Petikemas',
            'contact_person' => 'Capt. Firman Siregar',
            'contact_phone' => '+62 21-4301-5544',
            'address' => 'Jl. Pelabuhan Raya No. 1, Tanjung Priok, Jakarta Utara',
            'latitude' => -6.1042000,
            'longitude' => 106.8856000,
            'geofence_radius_meters' => 250,
            'location_type' => 'Site',
            'priority' => 'urgent',
            'is_active' => true,
        ]);

        $loc9 = Location::create([
            'geographical_area_id' => $areaNorth->id,
            'name' => 'Sunter Cold Storage & Freezing Facility',
            'code' => 'LOC-NJKT-009',
            'client_name' => 'Polaris Cold Logistics PT',
            'contact_person' => 'Eko Prasetyo',
            'contact_phone' => '+62 21-6512-3322',
            'address' => 'Kawasan Industri Sunter Agung Blok D2, Jakarta Utara',
            'latitude' => -6.1398000,
            'longitude' => 106.8643000,
            'geofence_radius_meters' => 140,
            'location_type' => 'Warehouse',
            'priority' => 'high',
            'is_active' => true,
        ]);

        // 5. Create Field Visits (Record field visits)
        // Visit 1: Completed by Ahmad Rifai at Wisma Nusantara
        $visit1 = FieldVisit::create([
            'visit_code' => 'VST-20261103-001',
            'user_id' => $officer1->id,
            'location_id' => $loc1->id,
            'geographical_area_id' => $areaCBD->id,
            'scheduled_at' => Carbon::today()->setTime(9, 0),
            'status' => 'completed',
            'purpose' => 'routine_inspection',
            'check_in_at' => Carbon::today()->setTime(9, 4),
            'check_in_lat' => -6.1925800,
            'check_in_lng' => 106.8239200,
            'check_in_distance_meters' => 24,
            'is_within_geofence' => true,
            'check_out_at' => Carbon::today()->setTime(10, 45),
            'check_out_lat' => -6.1926200,
            'check_out_lng' => 106.8238800,
            'duration_minutes' => 101,
            'visit_summary' => 'Comprehensive annual fire safety, emergency exit clearance, and HVAC filtration inspection completed without major blockage.',
            'client_representative_name' => 'Bambang Kusuma (Property Manager)',
        ]);

        // Visit 2: In-Progress by Ahmad Rifai at Pacific Place
        $visit2 = FieldVisit::create([
            'visit_code' => 'VST-20261103-002',
            'user_id' => $officer1->id,
            'location_id' => $loc2->id,
            'geographical_area_id' => $areaCBD->id,
            'scheduled_at' => Carbon::today()->setTime(13, 30),
            'status' => 'in_progress',
            'purpose' => 'audit',
            'check_in_at' => Carbon::today()->setTime(13, 35),
            'check_in_lat' => -6.2247600,
            'check_in_lng' => 106.8097500,
            'check_in_distance_meters' => 18,
            'is_within_geofence' => true,
            'visit_summary' => 'Auditing electrical room thermal readings and tenant grease trap maintenance protocols.',
            'client_representative_name' => 'Jessica Tan',
        ]);

        // Visit 3: Completed by Dewi Lestari at Simatupang Data Center
        $visit3 = FieldVisit::create([
            'visit_code' => 'VST-20261103-003',
            'user_id' => $officer2->id,
            'location_id' => $loc4->id,
            'geographical_area_id' => $areaSouth->id,
            'scheduled_at' => Carbon::today()->setTime(10, 0),
            'status' => 'completed',
            'purpose' => 'maintenance',
            'check_in_at' => Carbon::today()->setTime(10, 2),
            'check_in_lat' => -6.2915200,
            'check_in_lng' => 106.7971800,
            'check_in_distance_meters' => 12,
            'is_within_geofence' => true,
            'check_out_at' => Carbon::today()->setTime(12, 15),
            'check_out_lat' => -6.2914800,
            'check_out_lng' => 106.7972200,
            'duration_minutes' => 133,
            'visit_summary' => 'UPS battery bank room ventilation checked. Generator fuel backup levels at 94%. Redundant cooling loop operational.',
            'client_representative_name' => 'David Suryadi (Lead Engineer)',
        ]);

        // Visit 4: Scheduled for Dewi at Kemang Village
        $visit4 = FieldVisit::create([
            'visit_code' => 'VST-20261103-004',
            'user_id' => $officer2->id,
            'location_id' => $loc5->id,
            'geographical_area_id' => $areaSouth->id,
            'scheduled_at' => Carbon::today()->setTime(15, 30),
            'status' => 'scheduled',
            'purpose' => 'routine_inspection',
            'visit_summary' => 'Scheduled inspection for public escalator emergency brake tests and fire hydrant accessibility.',
        ]);

        // Visit 5: Completed by Maya Indah at Tanjung Priok Port
        $visit5 = FieldVisit::create([
            'visit_code' => 'VST-20261102-005',
            'user_id' => $officer4->id,
            'location_id' => $loc8->id,
            'geographical_area_id' => $areaNorth->id,
            'scheduled_at' => Carbon::yesterday()->setTime(8, 30),
            'status' => 'completed',
            'purpose' => 'emergency',
            'check_in_at' => Carbon::yesterday()->setTime(8, 28),
            'check_in_lat' => -6.1042500,
            'check_in_lng' => 106.8855500,
            'check_in_distance_meters' => 32,
            'is_within_geofence' => true,
            'check_out_at' => Carbon::yesterday()->setTime(11, 50),
            'check_out_lat' => -6.1041900,
            'check_out_lng' => 106.8856200,
            'duration_minutes' => 202,
            'visit_summary' => 'Post-rainfall berth stability check and container crane hydraulic leak inspection. Remediation plan requested.',
            'client_representative_name' => 'Capt. Firman Siregar',
        ]);

        // 6. Create Inspection Reports (Track inspection reports)
        // Report 1: Wisma Nusantara (Passed)
        $report1 = InspectionReport::create([
            'report_number' => 'RPT-202611-001',
            'field_visit_id' => $visit1->id,
            'location_id' => $loc1->id,
            'user_id' => $officer1->id,
            'title' => 'Wisma Nusantara Q4 Fire Safety & Emergency Infrastructure Audit',
            'category' => 'Safety & Quality',
            'inspection_date' => Carbon::today(),
            'compliance_score' => 96.50,
            'result_grade' => 'Passed',
            'status' => 'approved',
            'executive_summary' => 'High standard of building safety compliance maintained. Emergency lighting and pressurized stairwells tested operational.',
            'major_findings' => 'All fire doors cleared. Floor 14 east wing fire extinguisher tag nearing renewal in 10 days.',
            'corrective_recommendations' => 'Schedule extinguisher recharge batch for Floors 12-18 before November 15.',
            'reviewed_by' => $supervisor->id,
            'reviewed_at' => Carbon::today()->subHours(1),
            'reviewer_remarks' => 'Thorough audit. Recharging work order verified and approved.',
        ]);

        InspectionItem::create([
            'inspection_report_id' => $report1->id,
            'item_name' => 'Emergency Exit Doors Free of Obstruction',
            'category' => 'Egress & Evacuation',
            'status' => 'pass',
            'severity' => 'low',
            'notes' => 'All 8 staircases verified unblocked and properly illuminated.',
        ]);

        InspectionItem::create([
            'inspection_report_id' => $report1->id,
            'item_name' => 'Fire Extinguisher Pressure & Certification',
            'category' => 'Suppression Equipment',
            'status' => 'pass',
            'severity' => 'low',
            'notes' => 'Pressure gauges in green zone. Floor 14 tag renewal due soon.',
        ]);

        InspectionItem::create([
            'inspection_report_id' => $report1->id,
            'item_name' => 'Smoke Detector & Sprinkler Line Hydrostatic Check',
            'category' => 'Fire Detection',
            'status' => 'pass',
            'severity' => 'low',
            'notes' => 'Panel annunciator shows zero active supervisory faults.',
        ]);

        // Report 2: Simatupang Data Center (Passed)
        $report2 = InspectionReport::create([
            'report_number' => 'RPT-202611-002',
            'field_visit_id' => $visit3->id,
            'location_id' => $loc4->id,
            'user_id' => $officer2->id,
            'title' => 'Simatupang Data Center Critical Environmental & Power Audit',
            'category' => 'Equipment & Machinery',
            'inspection_date' => Carbon::today(),
            'compliance_score' => 92.00,
            'result_grade' => 'Passed',
            'status' => 'submitted',
            'executive_summary' => 'Mission-critical electrical distribution and chillers operating within optimal thermal tolerances.',
            'major_findings' => 'CRAC Unit #3 delta-T running slightly warm (+1.8C above setpoint); clean filter replacement recommended.',
            'corrective_recommendations' => 'Clean secondary air intake mesh on CRAC Unit #3 within 48 hours.',
        ]);

        InspectionItem::create([
            'inspection_report_id' => $report2->id,
            'item_name' => 'Battery Bank Voltage Uniformity',
            'category' => 'Power Backup',
            'status' => 'pass',
            'severity' => 'low',
            'notes' => 'All 120 VRLA cell voltages recorded within 13.5V to 13.7V float spec.',
        ]);

        InspectionItem::create([
            'inspection_report_id' => $report2->id,
            'item_name' => 'CRAC Air Filtration & Cooling Loop',
            'category' => 'HVAC & Cooling',
            'status' => 'pass',
            'severity' => 'medium',
            'notes' => 'Unit #3 intake filter exhibits moderate dust accumulation.',
        ]);

        // Report 3: Tanjung Priok Port (Warning / Needs Improvement)
        $report3 = InspectionReport::create([
            'report_number' => 'RPT-202611-003',
            'field_visit_id' => $visit5->id,
            'location_id' => $loc8->id,
            'user_id' => $officer4->id,
            'title' => 'Berth 3 Crane Heavy Machinery & Slip Hazard Assessment',
            'category' => 'Facility & Grounds',
            'inspection_date' => Carbon::yesterday(),
            'compliance_score' => 68.50,
            'result_grade' => 'Warning / Needs Improvement',
            'status' => 'under_review',
            'executive_summary' => 'Berth 3 quayside pavement shows localized oil sheen near Crane #2 rail trolley.',
            'major_findings' => 'Hydraulic line pinhole weep discovered on Crane #2 boom actuator. Sand absorption pads deployed.',
            'corrective_recommendations' => 'Ground Crane #2 for seal kit replacement and power wash quayside oil deposits immediately.',
        ]);

        InspectionItem::create([
            'inspection_report_id' => $report3->id,
            'item_name' => 'Quayside Surface Traction & Spill Control',
            'category' => 'Grounds & Pavement',
            'status' => 'fail',
            'severity' => 'high',
            'notes' => 'Oil sheen present over 15 meter radius near rail track.',
        ]);

        InspectionItem::create([
            'inspection_report_id' => $report3->id,
            'item_name' => 'Crane Hydraulic Line Integrity',
            'category' => 'Machinery',
            'status' => 'fail',
            'severity' => 'critical',
            'notes' => 'Minor fluid weep on high pressure line under 25-ton load test.',
        ]);

        // 7. Seed Activity Logs (View activity history)
        ActivityLog::create([
            'user_id' => $officer1->id,
            'action_type' => 'visit_checkout',
            'title' => 'Completed Field Visit at Wisma Nusantara Tower 1',
            'details' => 'Recorded 101 minutes on-site inspection. Check-out GPS verified within geofence (24m).',
            'subject_type' => FieldVisit::class,
            'subject_id' => $visit1->id,
            'latitude' => -6.1926200,
            'longitude' => 106.8238800,
            'ip_address' => '127.0.0.1',
            'created_at' => Carbon::today()->setTime(10, 46),
        ]);

        ActivityLog::create([
            'user_id' => $officer1->id,
            'action_type' => 'inspection_created',
            'title' => 'Submitted Inspection Report RPT-202611-001',
            'details' => 'Fire safety audit compliance score 96.50% (Passed).',
            'subject_type' => InspectionReport::class,
            'subject_id' => $report1->id,
            'latitude' => -6.1926000,
            'longitude' => 106.8239000,
            'ip_address' => '127.0.0.1',
            'created_at' => Carbon::today()->setTime(11, 00),
        ]);

        ActivityLog::create([
            'user_id' => $supervisor->id,
            'action_type' => 'report_approved',
            'title' => 'Approved Report RPT-202611-001',
            'details' => 'Supervisor Sarah Jenkins reviewed and approved the report with remark on recharging order.',
            'subject_type' => InspectionReport::class,
            'subject_id' => $report1->id,
            'ip_address' => '127.0.0.1',
            'created_at' => Carbon::today()->setTime(11, 45),
        ]);

        ActivityLog::create([
            'user_id' => $officer1->id,
            'action_type' => 'visit_recorded',
            'title' => 'Checked in at Pacific Place Retail Wing',
            'details' => 'Check-in GPS coordinates (-6.22476, 106.80975) verified 18m from site pin.',
            'subject_type' => FieldVisit::class,
            'subject_id' => $visit2->id,
            'latitude' => -6.2247600,
            'longitude' => 106.8097500,
            'ip_address' => '127.0.0.1',
            'created_at' => Carbon::today()->setTime(13, 35),
        ]);

        ActivityLog::create([
            'user_id' => $admin->id,
            'action_type' => 'area_assignment',
            'title' => 'Assigned Ahmad Rifai to Central Business District (CBD)',
            'details' => 'Designated as Lead Inspector with primary coverage over high-rise commercial premises.',
            'subject_type' => GeographicalArea::class,
            'subject_id' => $areaCBD->id,
            'ip_address' => '127.0.0.1',
            'created_at' => Carbon::now()->subMonths(3),
        ]);
    }
}
