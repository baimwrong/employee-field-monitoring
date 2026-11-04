<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\FieldVisit;
use App\Models\GeographicalArea;
use App\Models\Location;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use SplFileObject;

class CsvImportService
{
    /**
     * Parse raw CSV file into sanitized headers and key-value rows.
     *
     * @return array{headers: array<int, string>, rows: array<int, array<string, mixed>>}
     */
    public function parseCsv(string $filePath): array
    {
        $file = new SplFileObject($filePath, 'r');
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::DROP_NEW_LINE);

        $firstLine = $file->fgets();
        $file->rewind();
        $delimiter = (substr_count((string) $firstLine, ';') > substr_count((string) $firstLine, ',')) ? ';' : ',';
        $file->setCsvControl($delimiter);

        $headers = [];
        $rows = [];
        $lineNumber = 1;

        while (! $file->eof()) {
            $data = $file->fgetcsv();
            if ($data === false || $data === null || empty(array_filter($data, fn ($v) => $v !== null && trim((string) $v) !== ''))) {
                continue;
            }

            if (empty($headers)) {
                $data[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $data[0]);
                $headers = array_map(fn ($h) => strtolower(trim(preg_replace('/[^a-zA-Z0-9_]/', '_', trim((string) $h)))), $data);

                continue;
            }

            $lineNumber++;
            $rowAssoc = [];
            foreach ($headers as $index => $header) {
                $rowAssoc[$header] = isset($data[$index]) ? trim((string) $data[$index]) : '';
            }
            $rowAssoc['_line_number'] = $lineNumber;
            $rows[] = $rowAssoc;
        }

        return [
            'headers' => $headers,
            'rows' => $rows,
        ];
    }

    /**
     * Import Locations from CSV rows.
     *
     * @return array{imported: int, skipped: int, errors: array<int, string>}
     */
    public function importLocations(string $filePath): array
    {
        $parsed = $this->parseCsv($filePath);
        $imported = 0;
        $skipped = 0;
        $errors = [];

        $allAreas = GeographicalArea::all();
        $defaultArea = $allAreas->firstWhere('is_active', true) ?? $allAreas->first();

        foreach ($parsed['rows'] as $row) {
            $line = $row['_line_number'];
            $name = $row['name'] ?? $row['location_name'] ?? '';

            if (empty($name)) {
                $errors[] = "Line {$line}: Location name is required.";
                $skipped++;

                continue;
            }

            $lat = isset($row['latitude']) && $row['latitude'] !== '' ? (float) $row['latitude'] : null;
            $lng = isset($row['longitude']) && $row['longitude'] !== '' ? (float) $row['longitude'] : null;

            if ($lat === null || $lng === null || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
                $errors[] = "Line {$line} ('{$name}'): Invalid coordinates (Lat: {$row['latitude']}, Lng: {$row['longitude']}).";
                $skipped++;

                continue;
            }

            $area = null;
            $areaQuery = $row['geographical_area'] ?? $row['area'] ?? $row['area_name'] ?? $row['area_code'] ?? '';
            if (! empty($areaQuery)) {
                $area = $allAreas->first(function ($a) use ($areaQuery) {
                    return strcasecmp($a->name, $areaQuery) === 0
                        || strcasecmp($a->code, $areaQuery) === 0
                        || (string) $a->id === (string) $areaQuery;
                });
            }
            $area = $area ?? $defaultArea;

            if (! $area) {
                $errors[] = "Line {$line} ('{$name}'): No active geographical area exists in the database.";
                $skipped++;

                continue;
            }

            $radius = isset($row['geofence_radius_meters']) && (int) $row['geofence_radius_meters'] >= 20
                ? (int) $row['geofence_radius_meters']
                : 150;

            $code = ! empty($row['code']) ? $row['code'] : 'LOC-'.strtoupper(Str::random(6));
            if (Location::where('code', $code)->exists()) {
                $code = 'LOC-'.strtoupper(Str::random(6));
            }

            Location::create([
                'geographical_area_id' => $area->id,
                'name' => $name,
                'code' => $code,
                'client_name' => $row['client_name'] ?? null,
                'contact_person' => $row['contact_person'] ?? null,
                'contact_phone' => $row['contact_phone'] ?? null,
                'address' => $row['address'] ?? null,
                'latitude' => $lat,
                'longitude' => $lng,
                'geofence_radius_meters' => $radius,
                'location_type' => ! empty($row['location_type']) ? $row['location_type'] : 'Site',
                'priority' => in_array(strtolower($row['priority'] ?? ''), ['low', 'normal', 'high', 'urgent']) ? strtolower($row['priority']) : 'normal',
                'is_active' => true,
            ]);

            $imported++;
        }

        ActivityLog::log(
            'bulk_import',
            "Bulk Imported {$imported} Locations",
            "Successfully imported {$imported} locations. Skipped: {$skipped}."
        );

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Import Staff / Employees from CSV rows.
     *
     * @return array{imported: int, skipped: int, errors: array<int, string>}
     */
    public function importUsers(string $filePath): array
    {
        $parsed = $this->parseCsv($filePath);
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($parsed['rows'] as $row) {
            $line = $row['_line_number'];
            $name = $row['name'] ?? '';
            $email = strtolower($row['email'] ?? '');

            if (empty($name)) {
                $errors[] = "Line {$line}: Staff name is required.";
                $skipped++;

                continue;
            }

            if (empty($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Line {$line} ('{$name}'): Invalid email address '{$email}'.";
                $skipped++;

                continue;
            }

            if (User::where('email', $email)->exists()) {
                $errors[] = "Line {$line} ('{$name}'): Email '{$email}' already exists.";
                $skipped++;

                continue;
            }

            $empCode = $row['employee_code'] ?? $row['code'] ?? '';
            if (empty($empCode)) {
                do {
                    $empCode = 'EMP-'.rand(1000, 9999);
                } while (User::where('employee_code', $empCode)->exists());
            } elseif (User::where('employee_code', $empCode)->exists()) {
                $errors[] = "Line {$line} ('{$name}'): Employee code '{$empCode}' already in use.";
                $skipped++;

                continue;
            }

            $role = strtolower($row['role'] ?? 'field_officer');
            if (! in_array($role, ['admin', 'supervisor', 'field_officer'])) {
                $role = 'field_officer';
            }

            $status = strtolower($row['status'] ?? 'active');
            if (! in_array($status, ['active', 'inactive', 'on_leave'])) {
                $status = 'active';
            }

            $password = ! empty($row['password']) ? $row['password'] : 'FieldTrack123!';

            $user = User::create([
                'name' => $name,
                'email' => $email,
                'employee_code' => $empCode,
                'phone' => $row['phone'] ?? $row['phone_number'] ?? null,
                'role' => $role,
                'status' => $status,
                'password' => Hash::make($password),
                'current_latitude' => -6.2088,
                'current_longitude' => 106.8456,
                'last_active_at' => now(),
            ]);

            $areaQuery = $row['assigned_area'] ?? $row['area'] ?? '';
            if (! empty($areaQuery)) {
                $area = GeographicalArea::where('name', 'ilike', $areaQuery)
                    ->orWhere('code', 'ilike', $areaQuery)
                    ->first();
                if ($area) {
                    $user->geographicalAreas()->syncWithoutDetaching([$area->id]);
                }
            }

            $imported++;
        }

        ActivityLog::log(
            'bulk_import',
            "Bulk Imported {$imported} Staff Members",
            "Successfully imported {$imported} users. Skipped: {$skipped}."
        );

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }

    /**
     * Import Field Visit Dispatch Schedule from CSV rows.
     *
     * @return array{imported: int, skipped: int, errors: array<int, string>}
     */
    public function importVisits(string $filePath): array
    {
        $parsed = $this->parseCsv($filePath);
        $imported = 0;
        $skipped = 0;
        $errors = [];

        foreach ($parsed['rows'] as $row) {
            $line = $row['_line_number'];

            $officerQuery = $row['employee'] ?? $row['employee_code'] ?? $row['email'] ?? $row['officer'] ?? '';
            if (empty($officerQuery)) {
                $errors[] = "Line {$line}: Employee identifier (code or email) is required.";
                $skipped++;

                continue;
            }

            $officer = User::where('employee_code', $officerQuery)
                ->orWhere('email', strtolower($officerQuery))
                ->orWhere('name', 'ilike', $officerQuery)
                ->first();

            if (! $officer) {
                $errors[] = "Line {$line}: Officer '{$officerQuery}' not found in system.";
                $skipped++;

                continue;
            }

            $locQuery = $row['location'] ?? $row['location_code'] ?? $row['location_name'] ?? '';
            if (empty($locQuery)) {
                $errors[] = "Line {$line}: Location identifier is required.";
                $skipped++;

                continue;
            }

            $location = Location::where('code', $locQuery)
                ->orWhere('name', 'ilike', $locQuery)
                ->first();

            if (! $location) {
                $errors[] = "Line {$line}: Location '{$locQuery}' not found.";
                $skipped++;

                continue;
            }

            $timeStr = $row['scheduled_at'] ?? $row['date'] ?? '';
            try {
                $scheduledAt = ! empty($timeStr) ? Carbon::parse($timeStr) : now()->addHours(2);
            } catch (\Exception $e) {
                $errors[] = "Line {$line}: Invalid schedule date format '{$timeStr}'.";
                $skipped++;

                continue;
            }

            $purpose = strtolower($row['purpose'] ?? $row['visit_type'] ?? 'routine_inspection');
            $allowedPurposes = ['routine_inspection', 'audit', 'maintenance', 'survey', 'emergency', 'client_meeting'];
            if (! in_array($purpose, $allowedPurposes)) {
                $purpose = 'routine_inspection';
            }

            do {
                $code = 'VST-'.Carbon::now()->format('Ymd').'-'.strtoupper(Str::random(6));
            } while (FieldVisit::where('visit_code', $code)->exists());

            FieldVisit::create([
                'visit_code' => $code,
                'user_id' => $officer->id,
                'location_id' => $location->id,
                'geographical_area_id' => $location->geographical_area_id,
                'scheduled_at' => $scheduledAt,
                'purpose' => $purpose,
                'status' => 'scheduled',
                'visit_summary' => $row['instructions'] ?? $row['visit_summary'] ?? null,
            ]);

            $imported++;
        }

        ActivityLog::log(
            'bulk_import',
            "Bulk Dispatched {$imported} Field Visits",
            "Successfully scheduled {$imported} field visits from import. Skipped: {$skipped}."
        );

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'errors' => $errors,
        ];
    }
}
