@extends('layouts.app')

@section('title', 'Staff & Role Management')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Staff & Role Management</h3>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">
            Manage employee profiles, assign administrative or field roles, and monitor active operational status.
        </p>
    </div>
    @if(auth()->user()->isAdmin())
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createStaffModal">
            <i class="fa-solid fa-user-plus"></i>
            <span>Add New Staff Member</span>
        </button>
    </div>
    @endif
</div>

<!-- Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card-custom p-3 d-flex align-items-center gap-3">
            <div class="bg-primary-subtle text-primary rounded-3 p-3">
                <i class="fa-solid fa-users fs-4"></i>
            </div>
            <div>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Total Employees</small>
                <div class="fs-4 fw-bold text-dark">{{ $totalUsers }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-custom p-3 d-flex align-items-center gap-3">
            <div class="bg-info-subtle text-info rounded-3 p-3">
                <i class="fa-solid fa-crown fs-4"></i>
            </div>
            <div>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Administrators</small>
                <div class="fs-4 fw-bold text-dark">{{ $adminCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-custom p-3 d-flex align-items-center gap-3">
            <div class="bg-warning-subtle text-warning rounded-3 p-3">
                <i class="fa-solid fa-user-shield fs-4"></i>
            </div>
            <div>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Supervisors</small>
                <div class="fs-4 fw-bold text-dark">{{ $supervisorCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-custom p-3 d-flex align-items-center gap-3">
            <div class="bg-success-subtle text-success rounded-3 p-3">
                <i class="fa-solid fa-person-walking fs-4"></i>
            </div>
            <div>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Field Officers</small>
                <div class="fs-4 fw-bold text-success">{{ $officerCount }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters Bar -->
<div class="card-custom p-3 mb-4">
    <form action="{{ route('users.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-12 col-md-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search staff name, email, code, phone..." value="{{ $search }}">
            </div>
        </div>
        <div class="col-6 col-md-3">
            <select name="role" class="form-select form-select-sm">
                <option value="">All Roles</option>
                <option value="admin" {{ $roleFilter === 'admin' ? 'selected' : '' }}>Administrator</option>
                <option value="supervisor" {{ $roleFilter === 'supervisor' ? 'selected' : '' }}>Field Supervisor</option>
                <option value="field_officer" {{ $roleFilter === 'field_officer' ? 'selected' : '' }}>Field Officer</option>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <select name="status" class="form-select form-select-sm">
                <option value="">All Statuses</option>
                <option value="active" {{ $statusFilter === 'active' ? 'selected' : '' }}>Active</option>
                <option value="inactive" {{ $statusFilter === 'inactive' ? 'selected' : '' }}>Inactive</option>
                <option value="on_leave" {{ $statusFilter === 'on_leave' ? 'selected' : '' }}>On Leave</option>
            </select>
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary w-100">Filter Staff</button>
            @if($search || $roleFilter || $statusFilter)
                <a href="{{ route('users.index') }}" class="btn btn-sm btn-light border"><i class="fa-solid fa-rotate-left"></i></a>
            @endif
        </div>
    </form>
</div>

<!-- Staff Table -->
<div class="card-custom">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
            <thead class="table-light">
                <tr>
                    <th>Staff Member</th>
                    <th>Contact Info</th>
                    <th>System Role</th>
                    <th>Operational Status</th>
                    <th>Assigned Territories</th>
                    <th>Last Active / GPS</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $user)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 36px; height: 36px; font-size: 0.85rem;">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="fw-bold text-dark">{{ $user->name }}</div>
                                    <small class="badge bg-light text-muted border">{{ $user->employee_code ?? 'EMP' }}</small>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div><i class="fa-regular fa-envelope me-1 text-muted"></i>{{ $user->email }}</div>
                            <small class="text-muted"><i class="fa-solid fa-phone me-1"></i>{{ $user->phone ?? 'Not provided' }}</small>
                        </td>
                        <td>
                            @if($user->role === 'admin')
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1">
                                    <i class="fa-solid fa-crown me-1"></i>Administrator
                                </span>
                            @elseif($user->role === 'supervisor')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">
                                    <i class="fa-solid fa-user-shield me-1"></i>Field Supervisor
                                </span>
                            @else
                                <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">
                                    <i class="fa-solid fa-person-walking me-1"></i>Field Officer
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($user->status === 'active')
                                <span class="status-badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="fa-solid fa-circle text-success" style="font-size: 0.45rem;"></i> Active
                                </span>
                            @elseif($user->status === 'on_leave')
                                <span class="status-badge bg-warning-subtle text-warning border border-warning-subtle">
                                    <i class="fa-solid fa-circle text-warning" style="font-size: 0.45rem;"></i> On Leave
                                </span>
                            @else
                                <span class="status-badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                    <i class="fa-solid fa-circle text-secondary" style="font-size: 0.45rem;"></i> Inactive
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($user->geographicalAreas->count() > 0)
                                <div class="d-flex flex-wrap gap-1">
                                    @foreach($user->geographicalAreas as $area)
                                        <span class="badge bg-light text-dark border" style="font-size: 0.7rem;">
                                            <span class="d-inline-block rounded-circle me-1" style="width: 8px; height: 8px; background-color: {{ $area->color_code }};"></span>
                                            {{ $area->name }}
                                        </span>
                                    @endforeach
                                </div>
                            @else
                                <span class="text-muted fst-italic" style="font-size: 0.75rem;">None assigned</span>
                            @endif
                        </td>
                        <td>
                            @if($user->last_active_at)
                                <div class="text-dark fw-semibold" style="font-size: 0.8rem;">{{ $user->last_active_at->diffForHumans() }}</div>
                                @if($user->current_latitude && $user->current_longitude)
                                    <small class="text-muted">
                                        <i class="fa-solid fa-location-dot text-danger me-1"></i>[{{ number_format($user->current_latitude, 3) }}, {{ number_format($user->current_longitude, 3) }}]
                                    </small>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                @if(auth()->user()->isAdmin())
                                <button class="btn btn-sm btn-light border py-1 px-2 text-primary" data-bs-toggle="modal" data-bs-target="#editStaffModal{{ $user->id }}" title="Edit Staff & Role">
                                    <i class="fa-regular fa-pen-to-square"></i>
                                </button>
                                @if(auth()->id() !== $user->id)
                                    <form action="{{ route('users.destroy', $user) }}" method="POST" onsubmit="return confirm('Delete staff account for {{ $user->name }}?');" class="d-inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light border py-1 px-2 text-danger" title="Delete Staff">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </form>
                                @endif
                                @else
                                <span class="text-muted small">View Only</span>
                                @endif
                            </div>
                        </td>
                    </tr>

                    <!-- Edit Staff Modal -->
                    <div class="modal fade" id="editStaffModal{{ $user->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <form action="{{ route('users.update', $user) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header bg-light">
                                        <h5 class="modal-title fw-bold">Edit Staff: {{ $user->name }}</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                                            <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label fw-semibold">Email Address <span class="text-danger">*</span></label>
                                                <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label fw-semibold">Employee ID / Code <span class="text-danger">*</span></label>
                                                <input type="text" name="employee_code" class="form-control" value="{{ $user->employee_code }}" required>
                                            </div>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label fw-semibold">System Role <span class="text-danger">*</span></label>
                                                <select name="role" class="form-select" required>
                                                    <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Administrator (Master Control)</option>
                                                    <option value="supervisor" {{ $user->role === 'supervisor' ? 'selected' : '' }}>Field Supervisor (Dispatch & Review)</option>
                                                    <option value="field_officer" {{ $user->role === 'field_officer' ? 'selected' : '' }}>Field Officer (Check-In & Inspect)</option>
                                                </select>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label fw-semibold">Status <span class="text-danger">*</span></label>
                                                <select name="status" class="form-select" required>
                                                    <option value="active" {{ $user->status === 'active' ? 'selected' : '' }}>Active</option>
                                                    <option value="inactive" {{ $user->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                                    <option value="on_leave" {{ $user->status === 'on_leave' ? 'selected' : '' }}>On Leave</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label fw-semibold">Phone Number</label>
                                                <input type="text" name="phone" class="form-control" value="{{ $user->phone }}">
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label fw-semibold">New Password (Leave blank to keep)</label>
                                                <input type="password" name="password" class="form-control" placeholder="••••••••">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary btn-sm">Update Staff Member</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-5">
                            <i class="fa-solid fa-users fs-2 mb-2 d-block text-secondary"></i>
                            No staff members match the selected filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $users->links('pagination::bootstrap-5') }}
</div>

<!-- Create Staff Modal -->
<div class="modal fade" id="createStaffModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('users.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">
                        <i class="fa-solid fa-user-plus text-primary me-2"></i>Add New Staff Member
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. David Setiawan" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Corporate Email <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="name@fieldmonitor.com" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Employee ID / Code <span class="text-danger">*</span></label>
                            <input type="text" name="employee_code" class="form-control" placeholder="FLD-205" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">System Role <span class="text-danger">*</span></label>
                            <select name="role" class="form-select" required>
                                <option value="field_officer" selected>Field Officer</option>
                                <option value="supervisor">Field Operations Supervisor</option>
                                <option value="admin">Administrator</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Operational Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="on_leave">On Leave</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control" placeholder="+62 812-...">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Initial Password <span class="text-danger">*</span></label>
                            <input type="password" name="password" class="form-control" value="password" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus me-1"></i>Create Staff Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
