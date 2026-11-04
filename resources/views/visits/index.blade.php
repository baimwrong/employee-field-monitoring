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
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('visits.exportCsv', request()->query()) }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" title="Export Current Filtered Visits to CSV">
            <i class="fa-solid fa-file-csv text-success"></i>
            <span>Export CSV</span>
        </a>
        @if(auth()->user()->hasAnyRole(['admin', 'supervisor']))
        <button type="button" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#bulkImportVisitsModal">
            <i class="fa-solid fa-file-csv"></i>
            <span>Bulk Dispatch CSV</span>
        </button>
        <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#scheduleVisitModal">
            <i class="fa-solid fa-plus"></i>
            <span>Record / Schedule Visit</span>
        </button>
        @endif
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
                                @if($visit->check_in_photo)
                                    <div class="mt-1">
                                        <button type="button" class="btn btn-outline-secondary py-0 px-1 d-inline-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#photoModal{{ $visit->id }}" style="font-size: 0.7rem;">
                                            <i class="fa-solid fa-camera text-primary"></i> Photo Proof
                                        </button>
                                    </div>
                                @endif
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
                                <!-- Check-in button if scheduled and authorized -->
                                @if($visit->status === 'scheduled' && (auth()->user()->hasAnyRole(['admin', 'supervisor']) || auth()->id() === $visit->user_id))
                                    <button class="btn btn-sm btn-outline-success py-1 px-2" data-bs-toggle="modal" data-bs-target="#checkInModal{{ $visit->id }}" title="Record GPS Check-in">
                                        <i class="fa-solid fa-location-crosshairs me-1"></i>Check-In
                                    </button>
                                @endif

                                <!-- Check-out button if in_progress and authorized -->
                                @if($visit->status === 'in_progress' && (auth()->user()->hasAnyRole(['admin', 'supervisor']) || auth()->id() === $visit->user_id))
                                    <button class="btn btn-sm btn-outline-primary py-1 px-2" data-bs-toggle="modal" data-bs-target="#checkOutModal{{ $visit->id }}" title="Record GPS Check-out">
                                        <i class="fa-solid fa-flag-checkered me-1"></i>Check-Out
                                    </button>
                                @endif

                                <!-- Supervisor force check-out for abandoned/stale visits -->
                                @if($visit->status === 'in_progress' && auth()->user()->hasAnyRole(['admin', 'supervisor']))
                                    <button class="btn btn-sm btn-outline-warning py-1 px-2" data-bs-toggle="modal" data-bs-target="#forceCheckOutModal{{ $visit->id }}" title="Supervisor Force Check-Out">
                                        <i class="fa-solid fa-clock-rotate-left me-1"></i>Force Close
                                    </button>
                                @endif

                                <!-- Create Inspection Report button -->
                                <a href="{{ route('inspections.create', ['visit_id' => $visit->id]) }}" class="btn btn-sm btn-light border py-1 px-2" title="Create Inspection Report">
                                    <i class="fa-solid fa-clipboard-check text-warning"></i>
                                </a>

                                <!-- Delete button (restricted to supervisor/admin) -->
                                @if(auth()->user()->hasAnyRole(['admin', 'supervisor']))
                                <form action="{{ route('visits.destroy', $visit) }}" method="POST" onsubmit="return confirm('Delete visit {{ $visit->visit_code }}?');" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light border py-1 px-2 text-danger" title="Cancel Visit">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </form>
                                @endif
                            </div>
                        </td>
                    </tr>

                    <!-- Check-In Modal -->
                    <div class="modal fade" id="checkInModal{{ $visit->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <form action="{{ route('visits.checkIn', $visit) }}" method="POST" enctype="multipart/form-data">
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
                                            Live hardware GPS must be captured from your device to verify geofence perimeter before check-in.
                                        </p>

                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <label class="form-label fw-semibold small">Live Latitude <span class="text-danger">*</span></label>
                                                <input type="number" step="0.000001" name="latitude" id="checkInLat{{ $visit->id }}" class="form-control form-control-sm bg-light" placeholder="GPS required" readonly required>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label fw-semibold small">Live Longitude <span class="text-danger">*</span></label>
                                                <input type="number" step="0.000001" name="longitude" id="checkInLng{{ $visit->id }}" class="form-control form-control-sm bg-light" placeholder="GPS required" readonly required>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <div id="checkInStatus{{ $visit->id }}" class="small text-muted">
                                                <i class="fa-solid fa-satellite-dish me-1"></i>Awaiting GPS signal...
                                            </div>
                                            <button type="button" class="btn btn-sm btn-primary" id="checkInGpsBtn{{ $visit->id }}" onclick="captureCheckInGPS({{ $visit->id }})">
                                                <i class="fa-solid fa-crosshairs me-1"></i>Grab Live Coordinates
                                            </button>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small">On-Site Photo Proof (Optional)</label>
                                            <input type="file" name="check_in_photo" class="form-control form-control-sm checkin-photo-input" accept="image/*" capture="environment">
                                            <div class="checkin-photo-feedback text-muted small mt-1" style="font-size: 0.75rem;">Photo at site entrance or lobby.</div>
                                        </div>

                                        <div class="mb-2">
                                            <label class="form-label fw-semibold small">Arrival Observation / Notes</label>
                                            <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="e.g. Arrived on-site. Security clearance obtained."></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-success btn-sm" id="confirmCheckInBtn{{ $visit->id }}" disabled>
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

                                        <div class="row g-2 mb-2">
                                            <div class="col-6">
                                                <label class="form-label fw-semibold small">Departure Latitude <span class="text-danger">*</span></label>
                                                <input type="number" step="0.000001" name="latitude" id="checkOutLat{{ $visit->id }}" class="form-control form-control-sm bg-light" placeholder="GPS required" readonly required>
                                            </div>
                                            <div class="col-6">
                                                <label class="form-label fw-semibold small">Departure Longitude <span class="text-danger">*</span></label>
                                                <input type="number" step="0.000001" name="longitude" id="checkOutLng{{ $visit->id }}" class="form-control form-control-sm bg-light" placeholder="GPS required" readonly required>
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <div id="checkOutStatus{{ $visit->id }}" class="small text-muted">
                                                <i class="fa-solid fa-satellite-dish me-1"></i>Awaiting GPS signal...
                                            </div>
                                            <button type="button" class="btn btn-sm btn-primary" id="checkOutGpsBtn{{ $visit->id }}" onclick="captureCheckOutGPS({{ $visit->id }})">
                                                <i class="fa-solid fa-crosshairs me-1"></i>Grab Live Coordinates
                                            </button>
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
                                        <button type="submit" class="btn btn-primary btn-sm" id="confirmCheckOutBtn{{ $visit->id }}" disabled>
                                            <i class="fa-solid fa-flag-checkered me-1"></i>Complete Field Visit
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    @if($visit->check_in_photo)
                    <!-- Check-in Photo Preview Modal -->
                    <div class="modal fade" id="photoModal{{ $visit->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <div class="modal-header bg-light">
                                    <h6 class="modal-title fw-bold">
                                        <i class="fa-solid fa-camera text-primary me-2"></i>Entrance Photo Proof: {{ $visit->visit_code }}
                                    </h6>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body p-3 text-center">
                                    <img src="{{ asset('storage/' . $visit->check_in_photo) }}" alt="Entrance Photo" class="img-fluid rounded border shadow-sm mb-3" style="max-height: 380px; width: 100%; object-fit: contain; background: #0f172a;">
                                    <div class="text-start p-2 bg-light rounded small border">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Target Site:</span>
                                            <span class="fw-bold text-dark">{{ $visit->location->name ?? 'Site' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Arrival Timestamp:</span>
                                            <span class="text-dark">{{ $visit->check_in_at ? $visit->check_in_at->format('M d, Y H:i:s') : 'N/A' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted">Geofence Proximity:</span>
                                            <span class="{{ $visit->is_within_geofence ? 'text-success fw-bold' : 'text-warning fw-bold' }}">
                                                {{ $visit->check_in_distance_meters }}m ({{ $visit->is_within_geofence ? 'Verified Within Perimeter' : 'Outside Boundary' }})
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer bg-light p-2">
                                    <a href="{{ asset('storage/' . $visit->check_in_photo) }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                        <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Open Full Size
                                    </a>
                                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($visit->status === 'in_progress' && auth()->user()->hasAnyRole(['admin', 'supervisor']))
                    <!-- Supervisor Force Check-Out Modal -->
                    <div class="modal fade" id="forceCheckOutModal{{ $visit->id }}" tabindex="-1" aria-hidden="true">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content border-0 shadow">
                                <form action="{{ route('visits.forceCheckOut', $visit) }}" method="POST">
                                    @csrf
                                    <div class="modal-header bg-warning-subtle text-warning-emphasis">
                                        <h6 class="modal-title fw-bold">
                                            <i class="fa-solid fa-triangle-exclamation me-2"></i>Supervisor Force Check-Out: {{ $visit->visit_code }}
                                        </h6>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-3">
                                        <div class="alert alert-warning small mb-3">
                                            <i class="fa-solid fa-info-circle me-1"></i>
                                            Use this action if the field officer forgot to check out upon leaving site. The visit will be marked as completed with supervisor sign-off.
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-semibold small">Force Check-Out Reason / Note <span class="text-danger">*</span></label>
                                            <textarea name="visit_summary" class="form-control form-control-sm" rows="3" placeholder="e.g. Officer left site at 17:00, confirmed by security log. Verified via phone." required></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-warning btn-sm">
                                            <i class="fa-solid fa-check me-1"></i>Confirm Force Check-Out
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endif
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

@if(auth()->user()->hasAnyRole(['admin', 'supervisor']))
<!-- Bulk Dispatch Field Visits Modal -->
<div class="modal fade" id="bulkImportVisitsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('visits.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">
                        <i class="fa-solid fa-file-csv text-primary me-2"></i>Bulk Dispatch Field Visits
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted small mb-3">
                        Upload a CSV route schedule to mass-dispatch visits for your field officers across target client sites.
                    </p>

                    <div class="p-3 bg-light rounded border mb-3 small">
                        <div class="fw-bold mb-1"><i class="fa-solid fa-circle-info text-primary me-1"></i>Expected Columns:</div>
                        <code class="text-break">employee, location, scheduled_at, purpose, instructions</code>
                        <div class="text-muted mt-1" style="font-size: 0.75rem;">
                            &bull; <code>employee</code> accepts Employee Code (e.g. EMP-1003) or Email.<br>
                            &bull; <code>location</code> accepts Location Code (e.g. LOC-WISM-01) or Name.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="csv_file" class="form-control" accept=".csv,text/csv,text/plain" required>
                        <small class="text-muted">Maximum file size: 5MB</small>
                    </div>

                    <div class="d-flex align-items-center justify-content-between p-2 rounded bg-light border">
                        <span class="small text-muted">Need a template file?</span>
                        <a href="{{ route('visits.importTemplate') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fa-solid fa-download me-1"></i>Download Sample CSV
                        </a>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-cloud-arrow-up me-1"></i>Upload & Schedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
function captureCheckInGPS(visitId) {
    if (!navigator.geolocation) {
        alert('Geolocation is not supported on this device/browser.');
        return;
    }
    const statusEl = document.getElementById('checkInStatus' + visitId);
    const btn = document.getElementById('checkInGpsBtn' + visitId);
    if (statusEl) statusEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-primary me-1"></i>Acquiring satellite lock...';
    if (btn) btn.disabled = true;

    navigator.geolocation.getCurrentPosition(
        pos => {
            const lat = pos.coords.latitude.toFixed(7);
            const lng = pos.coords.longitude.toFixed(7);
            const acc = Math.round(pos.coords.accuracy);

            document.getElementById('checkInLat' + visitId).value = lat;
            document.getElementById('checkInLng' + visitId).value = lng;

            if (statusEl) {
                statusEl.innerHTML = `<span class="text-success"><i class="fa-solid fa-circle-check me-1"></i>GPS Locked (Accuracy: ±${acc}m)</span>`;
            }

            const confirmBtn = document.getElementById('confirmCheckInBtn' + visitId);
            if (confirmBtn) confirmBtn.disabled = false;
            if (btn) btn.disabled = false;
        },
        err => {
            if (statusEl) {
                statusEl.innerHTML = `<span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i>${escapeHtml(err.message)}</span>`;
            }
            if (btn) btn.disabled = false;
            alert('Could not retrieve GPS coordinates: ' + err.message + '. Please ensure location permission is granted.');
        },
        { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
    );
}

function captureCheckOutGPS(visitId) {
    if (!navigator.geolocation) {
        alert('Geolocation is not supported on this device/browser.');
        return;
    }
    const statusEl = document.getElementById('checkOutStatus' + visitId);
    const btn = document.getElementById('checkOutGpsBtn' + visitId);
    if (statusEl) statusEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-primary me-1"></i>Acquiring satellite lock...';
    if (btn) btn.disabled = true;

    navigator.geolocation.getCurrentPosition(
        pos => {
            const lat = pos.coords.latitude.toFixed(7);
            const lng = pos.coords.longitude.toFixed(7);
            const acc = Math.round(pos.coords.accuracy);

            document.getElementById('checkOutLat' + visitId).value = lat;
            document.getElementById('checkOutLng' + visitId).value = lng;

            if (statusEl) {
                statusEl.innerHTML = `<span class="text-success"><i class="fa-solid fa-circle-check me-1"></i>GPS Locked (Accuracy: ±${acc}m)</span>`;
            }

            const confirmBtn = document.getElementById('confirmCheckOutBtn' + visitId);
            if (confirmBtn) confirmBtn.disabled = false;
            if (btn) btn.disabled = false;
        },
        err => {
            if (statusEl) {
                statusEl.innerHTML = `<span class="text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i>${escapeHtml(err.message)}</span>`;
            }
            if (btn) btn.disabled = false;
            alert('Could not retrieve GPS coordinates: ' + err.message + '. Please ensure location permission is granted.');
        },
        { enableHighAccuracy: true, timeout: 12000, maximumAge: 0 }
    );
}

// Client-side HTML5 canvas image downscaling for check-in photo capture
document.addEventListener('change', function(e) {
    if (e.target && e.target.classList.contains('checkin-photo-input')) {
        const input = e.target;
        const feedback = input.closest('.mb-3')?.querySelector('.checkin-photo-feedback');
        if (!input.files || !input.files[0]) return;

        if (feedback) {
            feedback.innerHTML = '<i class="fa-solid fa-spinner fa-spin text-primary me-1"></i>Optimizing photo capture...';
        }

        const file = input.files[0];
        if (!file.type.startsWith('image/')) return;

        const reader = new FileReader();
        reader.onload = function(evt) {
            const img = new Image();
            img.onload = function() {
                let width = img.width;
                let height = img.height;
                const maxWidth = 1920;
                const maxHeight = 1080;

                if (width > maxWidth || height > maxHeight) {
                    if (width / height > maxWidth / maxHeight) {
                        height = Math.round((height * maxWidth) / width);
                        width = maxWidth;
                    } else {
                        width = Math.round((width * maxHeight) / height);
                        height = maxHeight;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob(function(blob) {
                    if (blob && blob.size < file.size) {
                        const compressedFile = new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), {
                            type: 'image/jpeg',
                            lastModified: Date.now()
                        });
                        const dt = new DataTransfer();
                        dt.items.add(compressedFile);
                        input.files = dt.files;
                        if (feedback) {
                            const origKb = (file.size / 1024).toFixed(0);
                            const compKb = (blob.size / 1024).toFixed(0);
                            feedback.innerHTML = `<span class="text-success"><i class="fa-solid fa-check me-1"></i>Optimized: ${compKb} KB (down from ${origKb} KB)</span>`;
                        }
                    } else {
                        if (feedback) {
                            feedback.innerHTML = `<span class="text-success"><i class="fa-solid fa-check me-1"></i>Photo ready (${(file.size / 1024).toFixed(0)} KB)</span>`;
                        }
                    }
                }, 'image/jpeg', 0.8);
            };
            img.src = evt.target.result;
        };
        reader.readAsDataURL(file);
    }
});
</script>
@endpush
