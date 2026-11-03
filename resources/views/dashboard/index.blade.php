@extends('layouts.app')

@section('title', 'Field Operations Dashboard')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Field Operations Command Center</h3>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">
            Real-time monitoring of geographical territories, assigned client locations, field staff attendance, and site inspections.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('visits.index') }}" class="btn btn-outline-primary btn-sm d-flex align-items-center gap-2">
            <i class="fa-solid fa-route"></i>
            <span>Record Field Visit</span>
        </a>
        <a href="{{ route('inspections.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-2">
            <i class="fa-solid fa-plus"></i>
            <span>New Inspection</span>
        </a>
    </div>
</div>

<!-- KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom kpi-card">
            <div class="kpi-icon bg-primary-subtle text-primary">
                <i class="fa-solid fa-user-shield"></i>
            </div>
            <div>
                <small class="text-muted fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Field Officers</small>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="fs-3 fw-bold text-dark">{{ $activeEmployees }}</span>
                    <small class="text-muted">/ {{ $totalEmployees }} Active</small>
                </div>
                <div class="text-success small fw-semibold"><i class="fa-solid fa-signal me-1"></i>100% Operational</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom kpi-card">
            <div class="kpi-icon bg-info-subtle text-info">
                <i class="fa-solid fa-map-location-dot"></i>
            </div>
            <div>
                <small class="text-muted fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Geographical Areas</small>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="fs-3 fw-bold text-dark">{{ $totalAreas }}</span>
                    <small class="text-muted">Zones Active</small>
                </div>
                <div class="text-muted small"><i class="fa-solid fa-draw-polygon me-1"></i>Geofenced coverage</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom kpi-card">
            <div class="kpi-icon bg-success-subtle text-success">
                <i class="fa-solid fa-location-dot"></i>
            </div>
            <div>
                <small class="text-muted fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Assigned Locations</small>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="fs-3 fw-bold text-dark">{{ $totalLocations }}</span>
                    <small class="text-muted">Premises</small>
                </div>
                <div class="text-success small"><i class="fa-solid fa-check-double me-1"></i>Radius monitored</div>
            </div>
        </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
        <div class="card-custom kpi-card">
            <div class="kpi-icon bg-warning-subtle text-warning">
                <i class="fa-solid fa-clipboard-check"></i>
            </div>
            <div>
                <small class="text-muted fw-semibold text-uppercase" style="font-size: 0.7rem; letter-spacing: 0.05em;">Today's Visits</small>
                <div class="d-flex align-items-baseline gap-2">
                    <span class="fs-3 fw-bold text-dark">{{ $completedVisitsCount }}</span>
                    <small class="text-muted">/ {{ $todayVisitsCount }} Done</small>
                </div>
                <div class="text-primary small fw-semibold"><i class="fa-solid fa-spinner fa-spin me-1"></i>{{ $inProgressVisitsCount }} In Progress</div>
            </div>
        </div>
    </div>
</div>

<!-- Interactive Live Operations Map -->
<div class="card-custom mb-4 overflow-hidden">
    <div class="card-header bg-white border-bottom p-3 d-flex flex-wrap align-items-center justify-content-between gap-3">
        <div class="d-flex align-items-center gap-2">
            <span class="badge bg-primary-subtle text-primary p-2 rounded-circle">
                <i class="fa-solid fa-satellite-dish"></i>
            </span>
            <div>
                <h6 class="fw-bold mb-0 text-dark">Live Geospatial Monitoring Map</h6>
                <small class="text-muted" style="font-size: 0.75rem;">Showing assigned territories, client locations, and field officer GPS pings</small>
            </div>
        </div>

        <div class="d-flex flex-wrap align-items-center gap-2">
            <!-- Map Layer Select Quick Pill -->
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary active map-layer-btn" data-layer="osm">
                    <i class="fa-solid fa-road me-1"></i>Street
                </button>
                <button type="button" class="btn btn-outline-secondary map-layer-btn" data-layer="google_satellite">
                    <i class="fa-solid fa-satellite me-1"></i>Google / Satellite
                </button>
                <button type="button" class="btn btn-outline-secondary map-layer-btn" data-layer="carto_positron">
                    <i class="fa-solid fa-sun me-1"></i>Light
                </button>
                <button type="button" class="btn btn-outline-secondary map-layer-btn" data-layer="carto_dark">
                    <i class="fa-solid fa-moon me-1"></i>Dark
                </button>
            </div>

            <!-- Locate Device Button -->
            <button class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1" id="locateMeBtn">
                <i class="fa-solid fa-location-crosshairs"></i>
                <span class="d-none d-sm-inline">My Location</span>
            </button>

            <!-- Reset Map View -->
            <button class="btn btn-sm btn-light border" id="resetMapZoomBtn" title="Reset View">
                <i class="fa-solid fa-arrows-to-dot"></i>
            </button>
        </div>
    </div>

    <!-- Map Filter Toolbar -->
    <div class="bg-light p-2 px-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2" style="font-size: 0.825rem;">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="checkbox" id="toggleAreasCheck" checked>
                <label class="form-check-label fw-semibold" for="toggleAreasCheck">
                    <span class="d-inline-block rounded-circle me-1" style="width: 10px; height: 10px; background-color: #2563eb;"></span>
                    Geographical Areas ({{ $areas->count() }})
                </label>
            </div>
            <div class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="checkbox" id="toggleLocationsCheck" checked>
                <label class="form-check-label fw-semibold" for="toggleLocationsCheck">
                    <i class="fa-solid fa-location-dot text-danger me-1"></i>
                    Assigned Locations ({{ $totalLocations }})
                </label>
            </div>
            <div class="form-check form-check-inline mb-0">
                <input class="form-check-input" type="checkbox" id="toggleOfficersCheck" checked>
                <label class="form-check-label fw-semibold" for="toggleOfficersCheck">
                    <i class="fa-solid fa-person-walking text-success me-1"></i>
                    Active Field Officers ({{ $activeEmployees }})
                </label>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2">
            <span class="text-muted">Filter Zone:</span>
            <select class="form-select form-select-sm" id="zoneFilterSelect" style="width: 180px;">
                <option value="all">All Geographical Zones</option>
                @foreach($areas as $area)
                    <option value="{{ $area->id }}">{{ $area->name }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <!-- The Map Canvas -->
    <div id="liveDashboardMap" style="height: 520px; width: 100%; position: relative; z-index: 1;"></div>
</div>

<!-- Recent Visits & Recent Inspection Reports -->
<div class="row g-4 mb-4">
    <!-- Recent Visits Table -->
    <div class="col-12 col-xl-7">
        <div class="card-custom h-100">
            <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-route text-primary"></i>
                    <h6 class="fw-bold mb-0">Recent Field Visits</h6>
                </div>
                <a href="{{ route('visits.index') }}" class="btn btn-sm btn-link text-decoration-none">View All Visits &rarr;</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Visit Code</th>
                            <th>Field Officer</th>
                            <th>Location / Area</th>
                            <th>Status</th>
                            <th>Check-In GPS</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($recentVisits as $visit)
                            <tr>
                                <td>
                                    <span class="fw-bold text-dark">{{ $visit->visit_code }}</span>
                                    <div class="text-muted" style="font-size: 0.75rem;">{{ $visit->scheduled_at->format('M d, H:i') }}</div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark">{{ $visit->user->name ?? 'Unassigned' }}</div>
                                    <small class="text-muted">{{ $visit->user->employee_code ?? '' }}</small>
                                </td>
                                <td>
                                    <div class="fw-medium text-dark">{{ $visit->location->name ?? 'N/A' }}</div>
                                    <small class="badge bg-light text-muted border">{{ $visit->geographicalArea->name ?? 'Zone' }}</small>
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
                                        <div class="text-success fw-semibold">
                                            <i class="fa-solid fa-location-crosshairs me-1"></i>{{ $visit->check_in_at->format('H:i') }}
                                        </div>
                                        <small class="text-muted" style="font-size: 0.7rem;">
                                            {{ $visit->check_in_distance_meters }}m ({{ $visit->is_within_geofence ? 'Geofence OK' : 'Outside' }})
                                        </small>
                                    @else
                                        <span class="text-muted">Not checked in</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('visits.index') }}" class="btn btn-sm btn-light border px-2 py-1" title="View Detail">
                                        <i class="fa-solid fa-eye text-muted"></i>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No field visits recorded yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Recent Inspection Reports -->
    <div class="col-12 col-xl-5">
        <div class="card-custom h-100">
            <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-clipboard-check text-warning"></i>
                    <h6 class="fw-bold mb-0">Recent Inspection Reports</h6>
                </div>
                <a href="{{ route('inspections.index') }}" class="btn btn-sm btn-link text-decoration-none">All Reports &rarr;</a>
            </div>
            <div class="p-3">
                @forelse($recentReports as $report)
                    <div class="p-3 mb-2 rounded-3 border bg-light bg-opacity-50">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                            <div>
                                <span class="badge bg-secondary-subtle text-secondary me-1">{{ $report->report_number }}</span>
                                <a href="{{ route('inspections.show', $report) }}" class="fw-bold text-dark text-decoration-none">
                                    {{ Str::limit($report->title, 38) }}
                                </a>
                            </div>
                            @if($report->result_grade === 'Passed')
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Passed</span>
                            @elseif(str_contains($report->result_grade, 'Warning'))
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Warning</span>
                            @else
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Failed</span>
                            @endif
                        </div>

                        <div class="d-flex align-items-center justify-content-between text-muted mt-2" style="font-size: 0.8rem;">
                            <div>
                                <i class="fa-solid fa-building me-1"></i>{{ $report->location->name ?? 'Site' }} &bull;
                                <i class="fa-solid fa-user me-1"></i>{{ $report->inspector->name ?? 'Auditor' }}
                            </div>
                            <div class="fw-bold text-primary">{{ number_format($report->compliance_score, 1) }}% Score</div>
                        </div>

                        <!-- Mini score progress bar -->
                        <div class="progress mt-2" style="height: 5px;">
                            <div class="progress-bar {{ $report->compliance_score >= 80 ? 'bg-success' : ($report->compliance_score >= 60 ? 'bg-warning' : 'bg-danger') }}"
                                 style="width: {{ $report->compliance_score }}%"></div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">No inspection reports submitted yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<!-- Activity Log Timeline Feed -->
<div class="card-custom">
    <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-clock-rotate-left text-info"></i>
            <h6 class="fw-bold mb-0">Live Operational Activity Stream</h6>
        </div>
        <a href="{{ route('activities.index') }}" class="btn btn-sm btn-link text-decoration-none">View Complete Audit Log &rarr;</a>
    </div>
    <div class="p-4">
        <div class="timeline">
            @foreach($recentActivities as $act)
                <div class="d-flex gap-3 mb-3 pb-3 border-bottom last-border-0">
                    <div class="flex-shrink-0">
                        @if(str_contains($act->action_type, 'checkin'))
                            <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                <i class="fa-solid fa-location-crosshairs fa-sm"></i>
                            </div>
                        @elseif(str_contains($act->action_type, 'checkout'))
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                <i class="fa-solid fa-flag-checkered fa-sm"></i>
                            </div>
                        @elseif(str_contains($act->action_type, 'inspection'))
                            <div class="bg-warning text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                <i class="fa-solid fa-clipboard-check fa-sm"></i>
                            </div>
                        @elseif(str_contains($act->action_type, 'area'))
                            <div class="bg-info text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                <i class="fa-solid fa-map-location-dot fa-sm"></i>
                            </div>
                        @else
                            <div class="bg-secondary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                <i class="fa-solid fa-bolt fa-sm"></i>
                            </div>
                        @endif
                    </div>
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center justify-content-between">
                            <span class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $act->title }}</span>
                            <small class="text-muted" style="font-size: 0.75rem;">{{ $act->created_at->diffForHumans() }}</small>
                        </div>
                        <p class="text-muted mb-1" style="font-size: 0.85rem;">{{ $act->details }}</p>
                        <div class="d-flex align-items-center gap-2 text-muted" style="font-size: 0.75rem;">
                            <span class="badge bg-light text-dark border"><i class="fa-solid fa-user me-1"></i>{{ $act->user->name ?? 'System' }}</span>
                            @if($act->latitude && $act->longitude)
                                <span><i class="fa-solid fa-location-dot text-danger me-1"></i>[{{ number_format($act->latitude, 4) }}, {{ number_format($act->longitude, 4) }}]</span>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Initialize Leaflet Map
    const map = L.map('liveDashboardMap', {
        center: [-6.2088, 106.8456],
        zoom: 12,
        zoomControl: true
    });

    // 2. Define Tile Layer Providers (including Google Maps Satellite & Hybrid via Esri & Google compatibility)
    const tileLayers = {
        osm: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }),
        google_satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
            maxZoom: 19,
            attribution: 'Tiles &copy; Esri / Google Hybrid Imagery'
        }),
        carto_positron: L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 19,
            attribution: '&copy; CARTO Positron'
        }),
        carto_dark: L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 19,
            attribution: '&copy; CARTO Dark Matter'
        }),
        opentopo: L.tileLayer('https://{s}.tile.opentopomap.org/{z}/{x}/{y}.png', {
            maxZoom: 17,
            attribution: '&copy; OpenTopoMap'
        })
    };

    // Set initial layer from preference
    let currentLayerKey = window.getSavedMapLayer();
    if (!tileLayers[currentLayerKey]) currentLayerKey = 'osm';
    let currentTileLayer = tileLayers[currentLayerKey].addTo(map);

    // Function to switch active layer
    window.switchActiveMapLayer = function(layerKey) {
        if (tileLayers[layerKey]) {
            map.removeLayer(currentTileLayer);
            currentTileLayer = tileLayers[layerKey].addTo(map);
            window.setSavedMapLayer(layerKey);

            // Update UI buttons
            document.querySelectorAll('.map-layer-btn').forEach(btn => {
                btn.classList.toggle('active', btn.dataset.layer === layerKey);
            });
        }
    };

    // Layer buttons event
    document.querySelectorAll('.map-layer-btn').forEach(btn => {
        btn.addEventListener('click', function () {
            window.switchActiveMapLayer(this.dataset.layer);
        });
    });

    // 3. Feature Layer Groups
    const areaLayerGroup = L.layerGroup().addTo(map);
    const locationLayerGroup = L.layerGroup().addTo(map);
    const officerLayerGroup = L.layerGroup().addTo(map);

    let allMapData = null;

    // Custom Icon Generators
    function createLocationIcon(type, priority) {
        let iconClass = 'fa-building';
        let bgClass = '#2563eb';

        if (type === 'Retail') { iconClass = 'fa-shop'; bgClass = '#059669'; }
        else if (type === 'Warehouse') { iconClass = 'fa-warehouse'; bgClass = '#d97706'; }
        else if (type === 'Infrastructure') { iconClass = 'fa-tower-cell'; bgClass = '#7c3aed'; }
        else if (type === 'Site') { iconClass = 'fa-ship'; bgClass = '#dc2626'; }

        return L.divIcon({
            className: 'custom-map-icon',
            html: `<div style="background-color: ${bgClass}; color: white; width: 34px; height: 34px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(0,0,0,0.3); border: 2px solid white;">
                     <i class="fa-solid ${iconClass}" style="font-size: 14px;"></i>
                   </div>`,
            iconSize: [34, 34],
            iconAnchor: [17, 17]
        });
    }

    function createOfficerIcon(name) {
        return L.divIcon({
            className: 'officer-map-icon',
            html: `<div style="position: relative;">
                     <div style="background-color: #10b981; color: white; width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 10px rgba(16,185,129,0.5); border: 2px solid white;">
                        <i class="fa-solid fa-person-walking" style="font-size: 14px;"></i>
                     </div>
                     <span style="position: absolute; top: -6px; right: -6px; width: 12px; height: 12px; background-color: #22c55e; border-radius: 50%; border: 2px solid white;"></span>
                   </div>`,
            iconSize: [32, 32],
            iconAnchor: [16, 16]
        });
    }

    // 4. Fetch Map Data via JSON API
    function loadMapData(selectedZoneId = 'all') {
        const url = selectedZoneId !== 'all' ? `/api/map-data?area_id=${selectedZoneId}` : '/api/map-data';

        fetch(url)
            .then(res => res.json())
            .then(res => {
                if (res.status !== 'success') return;
                allMapData = res.data;

                // Clear existing
                areaLayerGroup.clearLayers();
                locationLayerGroup.clearLayers();
                officerLayerGroup.clearLayers();

                const bounds = L.latLngBounds();

                // Draw Geographical Areas
                res.data.areas.forEach(area => {
                    const circle = L.circle(area.center, {
                        color: area.color,
                        fillColor: area.color,
                        fillOpacity: 0.15,
                        weight: 2,
                        radius: area.radius
                    });

                    bounds.extend(circle.getBounds());

                    let employeeListHtml = area.employees.map(e => `<li><b>${escapeHtml(e.name)}</b> (${escapeHtml(e.role_in_area)})</li>`).join('');
                    if (!employeeListHtml) employeeListHtml = '<li><span class="text-muted">No employees assigned</span></li>';

                    circle.bindPopup(`
                        <div style="min-width: 220px;">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <span class="d-inline-block rounded-circle" style="width: 12px; height: 12px; background-color: ${escapeHtml(area.color)};"></span>
                                <h6 class="fw-bold mb-0 text-dark">${escapeHtml(area.name)}</h6>
                            </div>
                            <small class="badge bg-light text-dark border mb-2">${escapeHtml(area.code)} &bull; ${escapeHtml(area.city)}</small>
                            <p class="text-muted small mb-2">${escapeHtml(area.description || 'Geofenced operational territory.')}</p>
                            <div class="small fw-semibold text-dark mb-1"><i class="fa-solid fa-users me-1 text-primary"></i>Assigned Field Staff:</div>
                            <ul class="small ps-3 mb-0">${employeeListHtml}</ul>
                        </div>
                    `);

                    areaLayerGroup.addLayer(circle);
                });

                // Draw Assigned Locations
                res.data.locations.forEach(loc => {
                    const marker = L.marker(loc.coords, {
                        icon: createLocationIcon(loc.location_type, loc.priority)
                    });

                    // Geofence boundary around site
                    const geofenceCircle = L.circle(loc.coords, {
                        color: loc.area_color || '#2563eb',
                        fillColor: '#3b82f6',
                        fillOpacity: 0.08,
                        weight: 1,
                        dashArray: '4, 4',
                        radius: loc.geofence_radius
                    });

                    locationLayerGroup.addLayer(geofenceCircle);
                    bounds.extend(loc.coords);

                    marker.bindPopup(`
                        <div style="min-width: 230px;">
                            <div class="d-flex align-items-center justify-content-between mb-1">
                                <span class="badge bg-primary-subtle text-primary">${escapeHtml(loc.location_type)}</span>
                                <small class="text-muted">${escapeHtml(loc.code || '')}</small>
                            </div>
                            <h6 class="fw-bold text-dark mb-1">${escapeHtml(loc.name)}</h6>
                            <p class="text-muted small mb-1"><i class="fa-solid fa-building me-1"></i>${escapeHtml(loc.client_name || 'Direct Client')}</p>
                            <p class="text-muted small mb-2"><i class="fa-solid fa-location-dot me-1"></i>${escapeHtml(loc.address || 'Address registered')}</p>
                            <div class="p-2 bg-light rounded small border">
                                <div><b>Contact:</b> ${escapeHtml(loc.contact_person || 'N/A')} (${escapeHtml(loc.contact_phone || '-')})</div>
                                <div><b>Geofence Radius:</b> ${escapeHtml(loc.geofence_radius)} meters</div>
                            </div>
                        </div>
                    `);

                    locationLayerGroup.addLayer(marker);
                });

                // Draw Field Officers
                res.data.employees.forEach(emp => {
                    const marker = L.marker(emp.coords, {
                        icon: createOfficerIcon(emp.name)
                    });

                    bounds.extend(emp.coords);

                    marker.bindPopup(`
                        <div style="min-width: 200px;">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="fa-solid fa-person-walking text-success fs-5"></i>
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark">${escapeHtml(emp.name)}</h6>
                                    <small class="text-muted">${escapeHtml(emp.code || 'Field Officer')}</small>
                                </div>
                            </div>
                            <div class="small p-2 bg-light rounded border mb-2">
                                <div><i class="fa-solid fa-phone me-1 text-muted"></i>${escapeHtml(emp.phone || '-')}</div>
                                <div><i class="fa-regular fa-clock me-1 text-muted"></i>Active: ${escapeHtml(emp.last_active)}</div>
                            </div>
                            <a href="/visits?user_id=${encodeURIComponent(emp.id)}" class="btn btn-sm btn-primary w-100 py-1" style="font-size: 0.75rem;">
                                View Officer Visits
                            </a>
                        </div>
                    `);

                    officerLayerGroup.addLayer(marker);
                });

                // Fit bounds if we have elements
                if (bounds.isValid()) {
                    map.fitBounds(bounds, { padding: [40, 40] });
                }
            })
            .catch(err => console.error('Error fetching map data:', err));
    }

    // Initial Load
    loadMapData();

    // Zone Filter Select
    document.getElementById('zoneFilterSelect').addEventListener('change', function () {
        loadMapData(this.value);
    });

    // Layer Toggles
    document.getElementById('toggleAreasCheck').addEventListener('change', function () {
        if (this.checked) map.addLayer(areaLayerGroup);
        else map.removeLayer(areaLayerGroup);
    });

    document.getElementById('toggleLocationsCheck').addEventListener('change', function () {
        if (this.checked) map.addLayer(locationLayerGroup);
        else map.removeLayer(locationLayerGroup);
    });

    document.getElementById('toggleOfficersCheck').addEventListener('change', function () {
        if (this.checked) map.addLayer(officerLayerGroup);
        else map.removeLayer(officerLayerGroup);
    });

    // Reset View Button
    document.getElementById('resetMapZoomBtn').addEventListener('click', function () {
        loadMapData(document.getElementById('zoneFilterSelect').value);
    });

    // Locate Me GPS
    document.getElementById('locateMeBtn').addEventListener('click', function () {
        if (!navigator.geolocation) {
            alert('Geolocation is not supported by your browser.');
            return;
        }

        const btn = this;
        btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Locating...';

        navigator.geolocation.getCurrentPosition(
            pos => {
                btn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> My Location';
                const myLat = pos.coords.latitude;
                const myLng = pos.coords.longitude;

                const myIcon = L.divIcon({
                    className: 'my-loc-icon',
                    html: `<div style="background-color: #ef4444; color: white; width: 36px; height: 36px; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 0 0 6px rgba(239, 68, 68, 0.3); border: 2px solid white;">
                             <i class="fa-solid fa-street-view" style="font-size: 16px;"></i>
                           </div>`,
                    iconSize: [36, 36],
                    iconAnchor: [18, 18]
                });

                L.marker([myLat, myLng], { icon: myIcon })
                    .addTo(map)
                    .bindPopup(`<div class="fw-bold text-danger"><i class="fa-solid fa-location-dot me-1"></i>Your Current Device Location</div><small class="text-muted">[${myLat.toFixed(5)}, ${myLng.toFixed(5)}]</small>`)
                    .openPopup();

                map.setView([myLat, myLng], 15);
            },
            err => {
                btn.innerHTML = '<i class="fa-solid fa-location-crosshairs"></i> My Location';
                alert('Could not retrieve device location: ' + err.message);
            },
            { enableHighAccuracy: true, timeout: 8000 }
        );
    });
});
</script>
@endpush
