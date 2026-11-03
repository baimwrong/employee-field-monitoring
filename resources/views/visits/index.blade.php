@extends('layouts.app')

@section('title', 'Field Visits & GPS Attendance')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Field Visits & GPS Verification</h3>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">
            Record and dispatch field visits, monitor real-time GPS check-ins, verify geofence accuracy, and log on-site durations.
        </p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#scheduleVisitModal">
            <i class="fa-solid fa-plus"></i>
            <span>Record / Schedule Visit</span>
        </button>
    </div>
</div>

<!-- Filters Bar -->
<div class="card-custom p-3 mb-4">
    <form action="{{ route('visits.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-6 col-md-3">
            <select name="status" class="form-select form-select-sm">
                <option value="">All Visit Statuses</option>
                <option value="scheduled" {{ $statusFilter === 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                <option value="in_progress" {{ $statusFilter === 'in_progress' ? 'selected' : '' }}>In Progress (Checked-In)</option>
                <option value="completed" {{ $statusFilter === 'completed' ? 'selected' : '' }}>Completed</option>
                <option value="cancelled" {{ $statusFilter === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <select name="user_id" class="form-select form-select-sm">
                <option value="">All Field Officers</option>
                @foreach($employees as $emp)
                    <option value="{{ $emp->id }}" {{ $employeeFilter == $emp->id ? 'selected' : '' }}>{{ $emp->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <select name="area_id" class="form-select form-select-sm">
                <option value="">All Areas</option>
                @foreach($areas as $area)
                    <option value="{{ $area->id }}" {{ $areaFilter == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-2">
            <input type="date" name="date" class="form-control form-control-sm" value="{{ $dateFilter }}">
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
            @if($statusFilter || $employeeFilter || $areaFilter || $dateFilter)
                <a href="{{ route('visits.index') }}" class="btn btn-sm btn-light border"><i class="fa-solid fa-rotate-left"></i></a>
            @endif
        </div>
    </form>
</div>

<!-- Visits Table -->
<div class="card-custom">
    <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-route text-primary"></i>
            <h6 class="fw-bold mb-0">Recorded Field Visits Directory</h6>
            <span class="badge bg-light text-dark border">{{ $visits->total() }} Visits</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
            <thead class="table-light">
                <tr>
                    <th>Visit Reference</th>
                    <th>Field Officer</th>
                    <th>Assigned Location</th>
                    <th>Scheduled Time</th>
                    <th>Status</th>
                    <th>Check-In & Geofence</th>
                    <th>Duration & Check-Out</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse($visits as $visit)
                    <tr>
                        <td>
                            <div class="fw-bold text-dark">{{ $visit->visit_code }}</div>
                            <span class="badge bg-light text-muted border" style="font-size: 0.7rem;">
                                {{ ucfirst(str_replace('_', ' ', $visit->purpose)) }}
                            </span>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $visit->user->name ?? 'Unassigned' }}</div>
                            <small class="text-muted">{{ $visit->user->employee_code ?? '' }}</small>
                        </td>
                        <td>
                            <div class="fw-medium text-dark">{{ $visit->location->name ?? 'N/A' }}</div>
                            <small class="text-muted"><i class="fa-solid fa-location-dot me-1"></i>{{ $visit->location->geographicalArea->name ?? 'Zone' }}</small>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $visit->scheduled_at->format('M d, Y') }}</div>
                            <small class="text-muted">{{ $visit->scheduled_at->format('H:i') }}</small>
                        </td>
                        <td>
                            @if($visit->status === 'completed')
                                <span class="status-badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="fa-solid fa-circle-check"></i> Completed
                                </span>
                            @elseif($visit->status === 'in_progress')
                                <span class="status-badge bg-primary-subtle text-primary border border-primary-subtle">
                                    <i class="fa-solid fa-spinner fa-spin"></i> In Progress
                                </span>
                            @elseif($visit->status === 'scheduled')
                                <span class="status-badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                    <i class="fa-regular fa-clock"></i> Scheduled
                                </span>
                            @else
                                <span class="status-badge bg-danger-subtle text-danger border border-danger-subtle">
                                    {{ ucfirst($visit->status) }}
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($visit->check_in_at)
                                <div class="fw-semibold text-success">
                                    <i class="fa-solid fa-clock me-1"></i>{{ $visit->check_in_at->format('H:i') }}
                                </div>
                                <div class="text-muted" style="font-size: 0.75rem;">
                                    Distance: <b>{{ $visit->check_in_distance_meters }}m</b>
                                    @if($visit->is_within_geofence)
                                        <span class="badge bg-success-subtle text-success py-0 px-1 ms-1">Within Geofence</span>
                                    @else
                                        <span class="badge bg-warning-subtle text-warning py-0 px-1 ms-1">Outside ({{ $visit->location->geofence_radius_meters }}m)</span>
                                    @endif
                                </div>
                            @else
                                <span class="text-muted fst-italic">Pending Check-In</span>
                            @endif
                        </td>
                        <td>
                            @if($visit->check_out_at)
                                <div class="fw-semibold text-dark">
                                    <i class="fa-solid fa-flag-checkered text-primary me-1"></i>{{ $visit->check_out_at->format('H:i') }}
                                </div>
                                <small class="text-muted">{{ $visit->duration_minutes }} minutes on-site</small>
                            @elseif($visit->status === 'in_progress')
                                <span class="text-primary fw-semibold"><i class="fa-solid fa-stopwatch fa-spin me-1"></i>Active Now</span>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-1">
                                <!-- Check-in button if scheduled -->
                                @if($visit->status === 'scheduled')
                                    <button class="btn btn-sm btn-outline-success py-1 px-2" data-bs-toggle="modal" data-bs-target="#checkInModal{{ $visit->id }}" title="Record GPS Check-in">
                                        <i class="fa-solid fa-location-crosshairs me-1"></i>Check-In
                                    </button>
                                @endif

                                <!-- Check-out button if in_progress -->
                                @if($visit->status === 'in_progress')
                                    <button class="btn btn-sm btn-outline-primary py-1 px-2" data-bs-toggle="modal" data-bs-target="#checkOutModal{{ $visit->id }}" title="Record GPS Check-out">
                                        <i class="fa-solid fa-flag-checkered me-1"></i>Check-Out
                                    </button>
                                @endif

                                <!-- Create Inspection Report button -->
                                <a href="{{ route('inspections.create', ['visit_id' => $visit->id]) }}" class="btn btn-sm btn-light border py-1 px-2" title="Create Inspection Report">
                                    <i class="fa-solid fa-clipboard-check text-warning"></i>
                                </a>

                                <!-- Delete button -->
                                <form action="{{ route('visits.destroy', $visit) }}" method="POST" onsubmit="return confirm('Delete visit {{ $visit->visit_code }}?');" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light border py-1 px-2 text-danger" title="Cancel Visit">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>

                    <!-- Check-In Modal -->
                    <div class="modal fade" id="checkInModal{{ $visit->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <form action="{{ route('visits.checkIn', $visit) }}" method="POST">
                                    @csrf
                                    <div class="modal-header bg-light">
                                        <h5 class="modal-title fw-bold">
                                            <i class="fa-solid fa-location-crosshairs text-success me-2"></i>Record Check-In: {{ $visit->visit_code }}
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <div class="p-3 bg-light rounded-3 border mb-3">
                                            <div class="fw-bold text-dark">{{ $visit->location->name }}</div>
                                            <small class="text-muted d-block mb-1">{{ $visit->location->address }}</small>
                                            <div class="small text-primary">
                                                <b>Allowed Geofence:</b> {{ $visit->location->geofence_radius_meters }}m radius
                                            </div>
                                        </div>

                                        <p class="text-muted small">
                                            Click the button below to capture the field officer's current GPS device coordinates. The system will automatically calculate the distance to the target site and verify the geofence perimeter.
                                        </p>

                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label fw-semibold small">Check-In Latitude</label>
                                                <input type="number" step="0.000001" name="latitude" id="checkInLat{{ $visit->id }}" class="form-control" value="{{ $visit->location->latitude }}" required>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label fw-semibold small">Check-In Longitude</label>
                                                <input type="number" step="0.000001" name="longitude" id="checkInLng{{ $visit->id }}" class="form-control" value="{{ $visit->location->longitude }}" required>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-end mb-3">
                                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="captureCheckInGPS({{ $visit->id }})">
                                                <i class="fa-solid fa-crosshairs me-1"></i>Grab Live Device Coordinates
                                            </button>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label fw-semibold small">Arrival Observation / Notes</label>
                                            <textarea name="notes" class="form-control" rows="2" placeholder="e.g. Arrived on-site. Security clearance obtained."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success btn-sm">
                                            <i class="fa-solid fa-check me-1"></i>Confirm GPS Check-In
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Check-Out Modal -->
                    <div class="modal fade" id="checkOutModal{{ $visit->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <form action="{{ route('visits.checkOut', $visit) }}" method="POST">
                                    @csrf
                                    <div class="modal-header bg-light">
                                        <h5 class="modal-title fw-bold">
                                            <i class="fa-solid fa-flag-checkered text-primary me-2"></i>Record Check-Out: {{ $visit->visit_code }}
                                        </h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-4">
                                        <div class="p-2 bg-light rounded border mb-3 small">
                                            <div><b>Target Site:</b> {{ $visit->location->name }}</div>
                                            <div><b>Checked-In At:</b> {{ $visit->check_in_at ? $visit->check_in_at->format('H:i:s (M d)') : '-' }}</div>
                                        </div>

                                        <div class="row g-2 mb-3">
                                            <div class="col-6">
                                                <label class="form-label fw-semibold small">Check-Out Latitude</label>
                                                <input type="number" step="0.000001" name="latitude" class="form-control" value="{{ $visit->location->latitude }}" required>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label fw-semibold small">Check-Out Longitude</label>
                                                <input type="number" step="0.000001" name="longitude" class="form-control" value="{{ $visit->location->longitude }}" required>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small">Client Representative Sign-Off (Name & Title)</label>
                                            <input type="text" name="client_representative_name" class="form-control" placeholder="e.g. Ir. Bambang Kusuma (Site Manager)">
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label fw-semibold small">Visit Completion Summary <span class="text-danger">*</span></label>
                                            <textarea name="visit_summary" class="form-control" rows="3" placeholder="Summary of work completed, issues addressed, follow-up required..." required></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fa-solid fa-flag-checkered me-1"></i>Complete Field Visit
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="fa-solid fa-route fs-2 mb-2 d-block text-secondary"></i>
                            No field visits match the selected criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $visits->links('pagination::bootstrap-5') }}
</div>

<!-- Schedule / Record Visit Modal -->
<div class="modal fade" id="scheduleVisitModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('visits.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">
                        <i class="fa-solid fa-route text-primary me-2"></i>Record & Dispatch Field Visit
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Assign Field Officer <span class="text-danger">*</span></label>
                        <select name="user_id" class="form-select" required>
                            <option value="">-- Choose Field Officer --</option>
                            @foreach($employees as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->name }} ({{ $emp->employee_code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Destination Site / Location <span class="text-danger">*</span></label>
                        <select name="location_id" class="form-select" required>
                            <option value="">-- Choose Assigned Location --</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}">{{ $loc->name }} ({{ $loc->client_name ?? 'Client' }}) - {{ $loc->geographicalArea->name ?? 'Zone' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Scheduled Date & Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" name="scheduled_at" class="form-control" value="{{ date('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Visit Purpose <span class="text-danger">*</span></label>
                            <select name="purpose" class="form-select" required>
                                <option value="routine_inspection" selected>Routine Inspection</option>
                                <option value="audit">Safety & Compliance Audit</option>
                                <option value="maintenance">Preventive Maintenance</option>
                                <option value="survey">Site Survey & Mapping</option>
                                <option value="emergency">Emergency Response</option>
                                <option value="client_meeting">Client Meeting</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label fw-semibold">Visit Objective / Instructions</label>
                        <textarea name="visit_summary" class="form-control" rows="2" placeholder="Specific check items or instructions for the field officer..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus me-1"></i>Record Field Visit
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function captureCheckInGPS(visitId) {
    if (!navigator.geolocation) {
        alert('Geolocation not supported on this device/browser.');
        return;
    }
    navigator.geolocation.getCurrentPosition(
        pos => {
            document.getElementById('checkInLat' + visitId).value = pos.coords.latitude.toFixed(6);
            document.getElementById('checkInLng' + visitId).value = pos.coords.longitude.toFixed(6);
            alert('Live GPS Coordinates captured: [' + pos.coords.latitude.toFixed(5) + ', ' + pos.coords.longitude.toFixed(5) + ']');
        },
        err => {
            alert('Could not retrieve GPS coordinates: ' + err.message);
        },
        { enableHighAccuracy: true }
    );
}
</script>
@endpush
