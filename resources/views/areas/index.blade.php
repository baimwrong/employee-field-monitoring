@extends('layouts.app')

@section('title', 'Geographical Areas & Territory Assignments')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Geographical Areas & Territory Assignments</h3>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">
            Define operational territories, configure geofence coverage radii, and assign field officers to manage designated areas.
        </p>
    </div>
    @if(auth()->user()->isAdmin())
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createAreaModal">
            <i class="fa-solid fa-plus"></i>
            <span>Create Geographical Area</span>
        </button>
    </div>
    @endif
</div>

<!-- Search & Statistics Bar -->
<div class="card-custom p-3 mb-4">
    <div class="row g-3 align-items-center justify-content-between">
        <div class="col-12 col-md-5">
            <form action="{{ route('areas.index') }}" method="GET" class="d-flex gap-2">
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                    <input type="text" name="search" class="form-control border-start-0" placeholder="Search zone name, code, city..." value="{{ $search }}">
                    <button class="btn btn-outline-secondary" type="submit">Filter</button>
                    @if($search)
                        <a href="{{ route('areas.index') }}" class="btn btn-light border"><i class="fa-solid fa-xmark"></i></a>
                    @endif
                </div>
            </form>
        </div>
        <div class="col-12 col-md-6 text-md-end">
            <span class="text-muted small me-3"><i class="fa-solid fa-map me-1 text-primary"></i>Total Areas: <b>{{ $areas->total() }}</b></span>
            <span class="text-muted small"><i class="fa-solid fa-users me-1 text-success"></i>Available Officers: <b>{{ $allEmployees->count() }}</b></span>
        </div>
    </div>
</div>

<!-- Geographical Areas Grid / Cards -->
<div class="row g-4">
    @forelse($areas as $area)
        <div class="col-12 col-lg-6">
            <div class="card-custom h-100 position-relative overflow-hidden">
                <!-- Color bar indicator -->
                <div style="height: 5px; background-color: {{ $area->color_code }};"></div>

                <div class="p-4">
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                        <div>
                            <div class="d-flex align-items-center gap-2">
                                <span class="d-inline-block rounded-circle" style="width: 12px; height: 12px; background-color: {{ $area->color_code }};"></span>
                                <h5 class="fw-bold text-dark mb-0">{{ $area->name }}</h5>
                            </div>
                            <div class="d-flex gap-2 align-items-center mt-1">
                                <span class="badge bg-light text-dark border">{{ $area->code }}</span>
                                <span class="text-muted small"><i class="fa-solid fa-city me-1"></i>{{ $area->city ?? 'Jakarta' }} &bull; {{ $area->province_state ?? 'DKI Jakarta' }}</span>
                            </div>
                        </div>

                        @if(auth()->user()->hasAnyRole(['admin', 'supervisor']))
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown">
                                <i class="fa-solid fa-ellipsis-vertical text-muted"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                @if(auth()->user()->isAdmin())
                                <li>
                                    <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editAreaModal{{ $area->id }}">
                                        <i class="fa-regular fa-pen-to-square me-2 text-primary"></i>Edit Area
                                    </button>
                                </li>
                                @endif
                                <li>
                                    <button class="dropdown-item text-primary" data-bs-toggle="modal" data-bs-target="#assignModal{{ $area->id }}">
                                        <i class="fa-solid fa-user-plus me-2"></i>Assign Employee
                                    </button>
                                </li>
                                @if(auth()->user()->isAdmin())
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('areas.destroy', $area) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this geographical area?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="dropdown-item text-danger" type="submit">
                                            <i class="fa-solid fa-trash me-2"></i>Delete Area
                                        </button>
                                    </form>
                                </li>
                                @endif
                            </ul>
                        </div>
                        @endif
                    </div>

                    <p class="text-muted small mb-3">
                        {{ $area->description ?? 'Operational geofence zone.' }}
                    </p>

                    <!-- Territory Geofence Metrics -->
                    <div class="row g-2 mb-3">
                        <div class="col-4">
                            <div class="p-2 bg-light rounded text-center border">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">Geofence Radius</small>
                                <span class="fw-bold text-primary">{{ number_format($area->radius_meters) }}m</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 bg-light rounded text-center border">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">Assigned Sites</small>
                                <span class="fw-bold text-dark">{{ $area->locations_count }} Locations</span>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="p-2 bg-light rounded text-center border">
                                <small class="text-muted d-block" style="font-size: 0.7rem;">Field Visits</small>
                                <span class="fw-bold text-success">{{ $area->field_visits_count }} Logged</span>
                            </div>
                        </div>
                    </div>

                    <!-- Assigned Employees Section (Core Requirement) -->
                    <div class="border-top pt-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h6 class="fw-bold text-dark small mb-0">
                                <i class="fa-solid fa-users text-primary me-1"></i>Assigned Field Officers ({{ $area->employees->count() }})
                            </h6>
                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.75rem;" data-bs-toggle="modal" data-bs-target="#assignModal{{ $area->id }}">
                                <i class="fa-solid fa-plus me-1"></i>Assign
                            </button>
                        </div>

                        @if($area->employees->count() > 0)
                            <div class="d-flex flex-column gap-2">
                                @foreach($area->employees as $emp)
                                    <div class="p-2 bg-light bg-opacity-75 rounded-2 d-flex align-items-center justify-content-between border" style="font-size: 0.8rem;">
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                                {{ substr($emp->name, 0, 2) }}
                                            </div>
                                            <div>
                                                <div class="fw-bold text-dark">{{ $emp->name }}</div>
                                                <div class="text-muted" style="font-size: 0.7rem;">
                                                    <span class="text-primary fw-semibold">{{ $emp->pivot->role_in_area ?? 'Field Officer' }}</span> &bull;
                                                    {{ $emp->phone ?? 'No phone' }}
                                                    @if($emp->pivot->is_primary)
                                                        <span class="badge bg-success-subtle text-success py-0 px-1 ms-1">Primary</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Detach Action -->
                                        @if(auth()->user()->hasAnyRole(['admin', 'supervisor']))
                                        <form action="{{ route('areas.removeEmployee', [$area, $emp]) }}" method="POST" onsubmit="return confirm('Remove {{ $emp->name }} from {{ $area->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-link text-danger p-0 border-0" title="Remove Assignment">
                                                <i class="fa-solid fa-user-minus"></i>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-muted small p-2 bg-light rounded text-center">
                                <i class="fa-solid fa-triangle-exclamation text-warning me-1"></i>No field officers assigned to this area yet.
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Footer coordinates -->
                <div class="card-footer bg-light border-top px-4 py-2 d-flex align-items-center justify-content-between" style="font-size: 0.75rem; color: #64748b;">
                    <span><i class="fa-solid fa-crosshairs me-1"></i>Center: [{{ number_format($area->center_lat, 4) }}, {{ number_format($area->center_lng, 4) }}]</span>
                    <a href="{{ route('locations.index', ['area_id' => $area->id]) }}" class="text-primary text-decoration-none">View Area Locations &rarr;</a>
                </div>
            </div>
        </div>

        <!-- Assign Employee Modal -->
        <div class="modal fade" id="assignModal{{ $area->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form action="{{ route('areas.assign', $area) }}" method="POST">
                        @csrf
                        <div class="modal-header bg-light">
                            <h5 class="modal-title fw-bold">
                                <i class="fa-solid fa-user-plus text-primary me-2"></i>Assign Employee to {{ $area->name }}
                            </h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Select Field Officer <span class="text-danger">*</span></label>
                                <select name="user_id" class="form-select" required>
                                    <option value="">-- Choose Field Officer --</option>
                                    @foreach($allEmployees as $emp)
                                        <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->employee_code ?? 'EMP' }}) - {{ $emp->phone ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold">Assignment Role in Area <span class="text-danger">*</span></label>
                                <select name="role_in_area" class="form-select" required>
                                    <option value="Lead Inspector">Lead Inspector</option>
                                    <option value="Field Safety Officer" selected>Field Safety Officer</option>
                                    <option value="Logistics Quality Auditor">Logistics Quality Auditor</option>
                                    <option value="Specialist Technician">Specialist Technician</option>
                                    <option value="Backup Auditor">Backup Auditor</option>
                                </select>
                            </div>

                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-semibold small">Assignment Date</label>
                                    <input type="date" name="assigned_date" class="form-control" value="{{ date('Y-m-d') }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold small">Expiry Date (Optional)</label>
                                    <input type="date" name="expiry_date" class="form-control">
                                </div>
                            </div>

                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" name="is_primary" value="1" id="isPrimary{{ $area->id }}" checked>
                                <label class="form-check-label fw-semibold" for="isPrimary{{ $area->id }}">Primary Responsible Officer</label>
                            </div>

                            <div class="mb-2">
                                <label class="form-label fw-semibold small">Assignment Notes & Instructions</label>
                                <textarea name="assignment_notes" class="form-control" rows="2" placeholder="e.g. Responsible for daily retail audits and morning safety inspections..."></textarea>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm">
                                <i class="fa-solid fa-check me-1"></i>Confirm Assignment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Area Modal -->
        <div class="modal fade" id="editAreaModal{{ $area->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form action="{{ route('areas.update', $area) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header bg-light">
                            <h5 class="modal-title fw-bold">Edit Area: {{ $area->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Area Name</label>
                                <input type="text" name="name" class="form-control" value="{{ $area->name }}" required>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Area Code</label>
                                    <input type="text" name="code" class="form-control" value="{{ $area->code }}" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">City</label>
                                    <input type="text" name="city" class="form-control" value="{{ $area->city }}">
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Center Latitude</label>
                                    <input type="number" step="0.000001" name="center_lat" class="form-control" value="{{ $area->center_lat }}" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Center Longitude</label>
                                    <input type="number" step="0.000001" name="center_lng" class="form-control" value="{{ $area->center_lng }}" required>
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-8">
                                    <label class="form-label fw-semibold">Radius (Meters)</label>
                                    <input type="number" name="radius_meters" class="form-control" value="{{ $area->radius_meters }}" min="100" max="50000" required>
                                </div>
                                <div class="col-4">
                                    <label class="form-label fw-semibold">Color Tag</label>
                                    <input type="color" name="color_code" class="form-control form-control-color w-100" value="{{ $area->color_code }}">
                                </div>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Description</label>
                                <textarea name="description" class="form-control" rows="2">{{ $area->description }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card-custom p-5 text-center text-muted">
                <i class="fa-solid fa-map-location-dot fs-1 mb-3 text-secondary"></i>
                <h5>No Geographical Areas Found</h5>
                <p>Start by creating an operational territory and assigning field officers to it.</p>
                <button class="btn btn-primary btn-sm mt-2" data-bs-toggle="modal" data-bs-target="#createAreaModal">
                    <i class="fa-solid fa-plus me-1"></i>Create First Area
                </button>
            </div>
        </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $areas->links('pagination::bootstrap-5') }}
</div>

<!-- Create Area Modal -->
<div class="modal fade" id="createAreaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('areas.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">
                        <i class="fa-solid fa-map-location-dot text-primary me-2"></i>Create Geographical Area
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Area Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. East Jakarta Industrial Corridor" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Area Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" placeholder="ZONE-EJKT-05" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">City / Regency</label>
                            <input type="text" name="city" class="form-control" placeholder="Jakarta Timur" value="Jakarta">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Center Latitude <span class="text-danger">*</span></label>
                            <input type="number" step="0.000001" name="center_lat" class="form-control" value="-6.225000" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Center Longitude <span class="text-danger">*</span></label>
                            <input type="number" step="0.000001" name="center_lng" class="form-control" value="106.850000" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-8">
                            <label class="form-label fw-semibold">Coverage Radius (Meters) <span class="text-danger">*</span></label>
                            <input type="number" name="radius_meters" class="form-control" value="3000" min="100" max="50000" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label fw-semibold">Color Tag</label>
                            <input type="color" name="color_code" class="form-control form-control-color w-100" value="#2563eb">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Brief territory overview and coverage notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus me-1"></i>Create Area
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
