<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\GeographicalArea;
use App\Models\User;
use App\Services\CsvImportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $roleFilter = $request->input('role');
        $statusFilter = $request->input('status');
        $search = $request->input('search');

        $query = User::with('geographicalAreas')
            ->withCount(['geographicalAreas', 'fieldVisits', 'inspectionReports']);

        if ($roleFilter) {
            $query->where('role', $roleFilter);
        }

        if ($statusFilter) {
            $query->where('status', $statusFilter);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('employee_code', 'ilike', "%{$search}%")
                    ->orWhere('phone', 'ilike', "%{$search}%");
            });
        }

        $users = $query->orderByRaw("CASE WHEN role = 'admin' THEN 1 WHEN role = 'supervisor' THEN 2 ELSE 3 END")
            ->orderBy('name')
            ->paginate(12)
            ->withQueryString();

        $totalUsers = User::count();
        $adminCount = User::where('role', 'admin')->count();
        $supervisorCount = User::where('role', 'supervisor')->count();
        $officerCount = User::where('role', 'field_officer')->count();
        $activeCount = User::where('status', 'active')->count();

        $allAreas = GeographicalArea::where('is_active', true)->get();

        return view('users.index', compact(
            'users',
            'totalUsers',
            'adminCount',
            'supervisorCount',
            'officerCount',
            'activeCount',
            'allAreas',
            'roleFilter',
            'statusFilter',
            'search'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'employee_code' => 'required|string|max:50|unique:users,employee_code',
            'phone' => 'nullable|string|max:50',
            'role' => 'required|string|in:admin,supervisor,field_officer',
            'status' => 'required|string|in:active,inactive,on_leave',
            'password' => 'required|string|min:6',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        // Set default coordinates near central hub if not given
        $validated['current_latitude'] = -6.2088;
        $validated['current_longitude'] = 106.8456;
        $validated['last_active_at'] = now();

        $user = User::create($validated);

        ActivityLog::log(
            'user_created',
            "Created Staff Member: {$user->name} ({$user->employee_code})",
            'Assigned Role: '.strtoupper($user->role).', Status: '.ucfirst($user->status),
            $user
        );

        return redirect()->route('users.index')->with('success', "Staff member '{$user->name}' created successfully with role: ".strtoupper($user->role));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'employee_code' => ['required', 'string', 'max:50', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:50',
            'role' => 'required|string|in:admin,supervisor,field_officer',
            'status' => 'required|string|in:active,inactive,on_leave',
            'password' => 'nullable|string|min:6',
        ]);

        if (! empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $oldRole = $user->role;
        $user->update($validated);

        $roleChangedMsg = ($oldRole !== $user->role) ? " (Role changed from {$oldRole} to {$user->role})" : '';

        ActivityLog::log(
            'user_updated',
            "Updated Staff Member: {$user->name}",
            "Updated details and permissions{$roleChangedMsg}.",
            $user
        );

        return redirect()->route('users.index')->with('success', "Staff member '{$user->name}' updated successfully.");
    }

    public function destroy(User $user)
    {
        if (auth()->id() === $user->id) {
            return back()->with('warning', 'You cannot delete your own active account.');
        }

        if ($user->role === 'admin' && User::where('role', 'admin')->count() <= 1) {
            return back()->with('warning', 'Cannot delete the system\'s only remaining administrator account.');
        }

        // Data retention guard: prevent cascading erasure of field history
        if ($user->fieldVisits()->exists() || $user->inspectionReports()->exists()) {
            return back()->with('warning', "Cannot delete staff member '{$user->name}' because they have associated field visits or inspection reports. Please update their operational status to 'Inactive' instead to preserve audit history.");
        }

        $name = $user->name;
        $user->delete();

        ActivityLog::log('user_deleted', "Deleted Staff Account: {$name}");

        return redirect()->route('users.index')->with('success', "Staff member '{$name}' deleted successfully.");
    }

    /**
     * Self-service password change for authenticated users.
     */
    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = $request->user();
        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        ActivityLog::log(
            'password_changed',
            'Changed account password',
            'Self-service password update.',
            $user,
            $user
        );

        return back()->with('success', 'Your account password has been updated successfully.');
    }

    /**
     * Download a sample CSV template for bulk staff import.
     */
    public function downloadImportTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="staff_import_template.csv"',
        ];

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'name',
                'email',
                'employee_code',
                'phone',
                'role',
                'status',
                'password',
                'assigned_area',
            ]);
            fputcsv($handle, [
                'Budi Santoso',
                'budi.santoso@fieldtrack.com',
                'EMP-2001',
                '081234567801',
                'field_officer',
                'active',
                'FieldTrack123!',
                'Central Jakarta Business District',
            ]);
            fputcsv($handle, [
                'Siti Rahmawati',
                'siti.rahmawati@fieldtrack.com',
                'EMP-2002',
                '081234567802',
                'field_officer',
                'active',
                'FieldTrack123!',
                'South Jakarta Commercial Zone',
            ]);
            fputcsv($handle, [
                'Dewi Lestari',
                'dewi.lestari@fieldtrack.com',
                'EMP-2003',
                '081234567803',
                'supervisor',
                'active',
                'FieldTrack123!',
                '',
            ]);
            fclose($handle);
        }, 'staff_import_template.csv', $headers);
    }

    /**
     * Bulk import staff members from an uploaded CSV file.
     */
    public function importCsv(Request $request, CsvImportService $importer): RedirectResponse
    {
        $request->validate([
            'csv_file' => 'required|file|mimes:csv,txt|max:5120',
        ]);

        $result = $importer->importUsers($request->file('csv_file')->getRealPath());

        if ($result['skipped'] > 0) {
            $errorSummary = implode('; ', array_slice($result['errors'], 0, 5));
            if (count($result['errors']) > 5) {
                $errorSummary .= '... and '.(count($result['errors']) - 5).' more';
            }

            return redirect()->route('users.index')->with(
                'warning',
                "Bulk import completed: {$result['imported']} staff members created. {$result['skipped']} row(s) skipped: {$errorSummary}"
            );
        }

        return redirect()->route('users.index')->with(
            'success',
            "Bulk import successful: {$result['imported']} staff members created."
        );
    }
}
