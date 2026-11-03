@extends('layouts.app')

@section('title', 'Activity History & Audit Trail')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Operational Activity History</h3>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">
            Chronological audit trail of all field officer movements, area assignments, GPS check-ins, and inspection submissions.
        </p>
    </div>
</div>

<!-- Summary Metrics -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card-custom p-3 d-flex align-items-center gap-3">
            <div class="bg-primary-subtle text-primary rounded-3 p-3">
                <i class="fa-solid fa-clock-rotate-left fs-4"></i>
            </div>
            <div>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Actions Today</small>
                <div class="fs-4 fw-bold text-dark">{{ $todayCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-custom p-3 d-flex align-items-center gap-3">
            <div class="bg-success-subtle text-success rounded-3 p-3">
                <i class="fa-solid fa-location-crosshairs fs-4"></i>
            </div>
            <div>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">GPS Check-Ins</small>
                <div class="fs-4 fw-bold text-success">{{ $checkInCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-custom p-3 d-flex align-items-center gap-3">
            <div class="bg-warning-subtle text-warning rounded-3 p-3">
                <i class="fa-solid fa-clipboard-check fs-4"></i>
            </div>
            <div>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Inspections Logged</small>
                <div class="fs-4 fw-bold text-warning">{{ $reportCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-custom p-3 d-flex align-items-center gap-3">
            <div class="bg-info-subtle text-info rounded-3 p-3">
                <i class="fa-solid fa-map-location-dot fs-4"></i>
            </div>
            <div>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Area Assignments</small>
                <div class="fs-4 fw-bold text-primary">{{ $assignmentCount }}</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters Bar -->
<div class="card-custom p-3 mb-4">
    <form action="{{ route('activities.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-12 col-md-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search event title, details, IP..." value="{{ $search }}">
            </div>
        </div>
        <div class="col-6 col-md-3">
            <select name="action_type" class="form-select form-select-sm">
                <option value="">All Action Types</option>
                @foreach($actionTypes as $type)
                    <option value="{{ $type }}" {{ $actionFilter === $type ? 'selected' : '' }}>
                        {{ ucwords(str_replace('_', ' ', $type)) }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3">
            <select name="user_id" class="form-select form-select-sm">
                <option value="">All Staff Members</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ $userFilter == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->role }})</option>
                @endforeach
            </select>
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary w-100">Filter History</button>
            @if($search || $actionFilter || $userFilter || $dateFilter)
                <a href="{{ route('activities.index') }}" class="btn btn-sm btn-light border"><i class="fa-solid fa-rotate-left"></i></a>
            @endif
        </div>
    </form>
</div>

<!-- Timeline & Table View -->
<div class="card-custom">
    <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-list-timeline text-primary"></i>
            <h6 class="fw-bold mb-0">System Activity Logs</h6>
            <span class="badge bg-light text-muted border">{{ $activities->total() }} Events</span>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
            <thead class="table-light">
                <tr>
                    <th style="width: 140px;">Timestamp</th>
                    <th>Action Category</th>
                    <th>Event Description</th>
                    <th>Responsible User</th>
                    <th>GPS Coordinates</th>
                    <th>IP Address</th>
                </tr>
            </thead>
            <tbody>
                @forelse($activities as $act)
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark">{{ $act->created_at->format('M d, Y') }}</div>
                            <small class="text-muted">{{ $act->created_at->format('H:i:s') }} ({{ $act->created_at->diffForHumans() }})</small>
                        </td>
                        <td>
                            @if(str_contains($act->action_type, 'checkin'))
                                <span class="status-badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="fa-solid fa-location-crosshairs"></i> Check-In
                                </span>
                            @elseif(str_contains($act->action_type, 'checkout'))
                                <span class="status-badge bg-primary-subtle text-primary border border-primary-subtle">
                                    <i class="fa-solid fa-flag-checkered"></i> Check-Out
                                </span>
                            @elseif(str_contains($act->action_type, 'inspection') || str_contains($act->action_type, 'report'))
                                <span class="status-badge bg-warning-subtle text-warning border border-warning-subtle">
                                    <i class="fa-solid fa-clipboard-check"></i> Inspection
                                </span>
                            @elseif(str_contains($act->action_type, 'area'))
                                <span class="status-badge bg-info-subtle text-info border border-info-subtle">
                                    <i class="fa-solid fa-map-location-dot"></i> Territory
                                </span>
                            @elseif(str_contains($act->action_type, 'location'))
                                <span class="status-badge bg-danger-subtle text-danger border border-danger-subtle">
                                    <i class="fa-solid fa-location-dot"></i> Location
                                </span>
                            @else
                                <span class="status-badge bg-secondary-subtle text-secondary border border-secondary-subtle">
                                    <i class="fa-solid fa-bolt"></i> {{ ucfirst(str_replace('_', ' ', $act->action_type)) }}
                                </span>
                            @endif
                        </td>
                        <td>
                            <div class="fw-bold text-dark">{{ $act->title }}</div>
                            <div class="text-secondary small">{{ $act->details }}</div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $act->user->name ?? 'System' }}</div>
                            <small class="text-muted">{{ $act->user->role ?? '' }}</small>
                        </td>
                        <td>
                            @if($act->latitude && $act->longitude)
                                <a href="https://maps.google.com/?q={{ $act->latitude }},{{ $act->longitude }}" target="_blank" class="text-decoration-none badge bg-light text-primary border" title="View on Google Maps">
                                    <i class="fa-solid fa-crosshairs me-1 text-danger"></i>[{{ number_format($act->latitude, 4) }}, {{ number_format($act->longitude, 4) }}]
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td>
                            <small class="text-muted font-monospace">{{ $act->ip_address ?? '127.0.0.1' }}</small>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-5">
                            <i class="fa-solid fa-clock-rotate-left fs-2 mb-2 d-block text-secondary"></i>
                            No activity logs recorded.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $activities->links('pagination::bootstrap-5') }}
</div>
@endsection
