<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\GeographicalArea;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $roleFilter = $request->input('role');
        $statusFilter = $request->input('status');
        $search = $request->input('search');

        $query = User::with(['geographicalAreas', 'fieldVisits' => function ($q) {
            $q->latest()->take(3);
        }])->withCount(['geographicalAreas', 'fieldVisits', 'inspectionReports']);

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
            "Assigned Role: " . strtoupper($user->role) . ", Status: " . ucfirst($user->status),
            $user
        );

        return redirect()->route('users.index')->with('success', "Staff member '{$user->name}' created successfully with role: " . strtoupper($user->role));
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

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $oldRole = $user->role;
        $user->update($validated);

        $roleChangedMsg = ($oldRole !== $user->role) ? " (Role changed from {$oldRole} to {$user->role})" : "";

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

        $name = $user->name;
        $user->delete();

        ActivityLog::log('user_deleted', "Deleted Staff Account: {$name}");

        return redirect()->route('users.index')->with('success', "Staff member '{$name}' deleted successfully.");
    }
}
