@extends('layouts.app')

@section('title', 'Assigned Locations & Client Premises')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Assigned Client Locations & Sites</h3>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">
            Display assigned locations across geographical territories, inspect individual geofence radii, and manage target sites.
        </p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" id="toggleViewBtn">
            <i class="fa-solid fa-map me-1"></i><span id="viewModeText">Show Full Map</span>
        </button>
        <button type="button" class="btn btn-primary btn-sm d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#createLocationModal">
            <i class="fa-solid fa-plus"></i>
            <span>Add Assigned Location</span>
        </button>
    </div>
</div>

<!-- Interactive Map Container (Collapsible or Full Toggle) -->
<div class="card-custom mb-4 overflow-hidden" id="locationsMapContainer">
    <div class="card-header bg-white border-bottom p-3 d-flex flex-wrap align-items-center justify-content-between gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="fa-solid fa-location-dot text-danger"></i>
            <h6 class="fw-bold mb-0">Assigned Locations Geospatial Overview</h6>
            <span class="badge bg-light text-muted border">{{ $allMapLocations->count() }} Target Sites</span>
        </div>
        <div class="d-flex align-items-center gap-2">
            <div class="btn-group btn-group-sm" role="group">
                <button type="button" class="btn btn-outline-secondary active loc-layer-btn" data-layer="osm">Street</button>
                <button type="button" class="btn btn-outline-secondary loc-layer-btn" data-layer="google_satellite">Google / Satellite</button>
                <button type="button" class="btn btn-outline-secondary loc-layer-btn" data-layer="carto_positron">Light</button>
                <button type="button" class="btn btn-outline-secondary loc-layer-btn" data-layer="carto_dark">Dark</button>
            </div>
            <button class="btn btn-sm btn-light border" id="locLocateMeBtn" title="My GPS Location">
                <i class="fa-solid fa-crosshairs text-primary"></i>
            </button>
        </div>
    </div>
    <div id="assignedLocationsMap" style="height: 440px; width: 100%;"></div>
</div>

<!-- Filters and Search Bar -->
<div class="card-custom p-3 mb-4">
    <form action="{{ route('locations.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-12 col-md-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search site name, code, client, address..." value="{{ $search }}">
            </div>
        </div>
        <div class="col-6 col-md-3">
            <select name="area_id" class="form-select form-select-sm">
                <option value="">All Geographical Areas</option>
                @foreach($areas as $area)
                    <option value="{{ $area->id }}" {{ $areaFilter == $area->id ? 'selected' : '' }}>{{ $area->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-6 col-md-3">
            <select name="type" class="form-select form-select-sm">
                <option value="">All Location Types</option>
                <option value="Retail" {{ $typeFilter === 'Retail' ? 'selected' : '' }}>Retail & Malls</option>
                <option value="Office" {{ $typeFilter === 'Office' ? 'selected' : '' }}>Corporate Office</option>
                <option value="Warehouse" {{ $typeFilter === 'Warehouse' ? 'selected' : '' }}>Logistics & Warehouse</option>
                <option value="Infrastructure" {{ $typeFilter === 'Infrastructure' ? 'selected' : '' }}>Infrastructure & Data Center</option>
                <option value="Site" {{ $typeFilter === 'Site' ? 'selected' : '' }}>Industrial / Port Site</option>
            </select>
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary w-100">Apply Filters</button>
            @if($search || $areaFilter || $typeFilter)
                <a href="{{ route('locations.index') }}" class="btn btn-sm btn-light border"><i class="fa-solid fa-rotate-left"></i></a>
            @endif
        </div>
    </form>
</div>

<!-- Locations Cards Grid -->
<div class="row g-3">
    @forelse($locations as $loc)
        <div class="col-12 col-md-6 col-xl-4">
            <div class="card-custom h-100 p-3 position-relative d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                        <div>
                            <span class="badge bg-light text-primary border mb-1" style="font-size: 0.7rem;">
                                <i class="fa-solid fa-map-pin me-1"></i>{{ $loc->geographicalArea->name ?? 'Zone' }}
                            </span>
                            <h6 class="fw-bold text-dark mb-0">{{ $loc->name }}</h6>
                            <small class="text-muted">{{ $loc->client_name ?? 'Client Site' }}</small>
                        </div>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-light border" type="button" data-bs-toggle="dropdown">
                                <i class="fa-solid fa-ellipsis-vertical text-muted"></i>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                                <li>
                                    <button class="dropdown-item" data-bs-toggle="modal" data-bs-target="#editLocModal{{ $loc->id }}">
                                        <i class="fa-regular fa-pen-to-square me-2 text-primary"></i>Edit Location
                                    </button>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('visits.index', ['location_id' => $loc->id]) }}">
                                        <i class="fa-solid fa-route me-2 text-success"></i>View Visit History
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form action="{{ route('locations.destroy', $loc) }}" method="POST" onsubmit="return confirm('Delete location {{ $loc->name }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button class="dropdown-item text-danger" type="submit">
                                            <i class="fa-solid fa-trash me-2"></i>Delete
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <p class="text-muted small mb-3">
                        <i class="fa-solid fa-location-dot text-danger me-1"></i>{{ $loc->address ?? 'No physical address recorded.' }}
                    </p>

                    <div class="p-2 bg-light rounded-3 border mb-3" style="font-size: 0.8rem;">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Contact:</span>
                            <span class="fw-semibold text-dark">{{ $loc->contact_person ?? 'N/A' }} ({{ $loc->contact_phone ?? '-' }})</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Geofence Radius:</span>
                            <span class="fw-bold text-primary">{{ $loc->geofence_radius_meters }} meters</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Coordinates:</span>
                            <span class="text-secondary font-monospace">[{{ number_format($loc->latitude, 4) }}, {{ number_format($loc->longitude, 4) }}]</span>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center justify-content-between pt-2 border-top" style="font-size: 0.75rem;">
                    <div>
                        <span class="badge {{ $loc->priority === 'urgent' ? 'bg-danger' : ($loc->priority === 'high' ? 'bg-warning text-dark' : 'bg-secondary') }}">
                            {{ ucfirst($loc->priority) }} Priority
                        </span>
                        <span class="badge bg-info-subtle text-info ms-1">{{ $loc->location_type }}</span>
                    </div>
                    <a href="{{ route('visits.index') }}" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size: 0.75rem;">
                        <i class="fa-solid fa-plus me-1"></i>Log Visit
                    </a>
                </div>
            </div>
        </div>

        <!-- Edit Location Modal -->
        <div class="modal fade" id="editLocModal{{ $loc->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 shadow">
                    <form action="{{ route('locations.update', $loc) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="modal-header bg-light">
                            <h5 class="modal-title fw-bold">Edit Location: {{ $loc->name }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body p-4">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Geographical Area <span class="text-danger">*</span></label>
                                <select name="geographical_area_id" class="form-select" required>
                                    @foreach($areas as $area)
                                        <option value="{{ $area->id }}" {{ $loc->geographical_area_id == $area->id ? 'selected' : '' }}>{{ $area->name }} ({{ $area->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Location / Site Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" class="form-control" value="{{ $loc->name }}" required>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Client Company</label>
                                    <input type="text" name="client_name" class="form-control" value="{{ $loc->client_name }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Site Code</label>
                                    <input type="text" name="code" class="form-control" value="{{ $loc->code }}">
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Latitude</label>
                                    <input type="number" step="0.000001" name="latitude" class="form-control" value="{{ $loc->latitude }}" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Longitude</label>
                                    <input type="number" step="0.000001" name="longitude" class="form-control" value="{{ $loc->longitude }}" required>
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Geofence Radius (m)</label>
                                    <input type="number" name="geofence_radius_meters" class="form-control" value="{{ $loc->geofence_radius_meters }}" min="20" max="2000" required>
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Type</label>
                                    <select name="location_type" class="form-select" required>
                                        <option value="Retail" {{ $loc->location_type === 'Retail' ? 'selected' : '' }}>Retail</option>
                                        <option value="Office" {{ $loc->location_type === 'Office' ? 'selected' : '' }}>Office</option>
                                        <option value="Warehouse" {{ $loc->location_type === 'Warehouse' ? 'selected' : '' }}>Warehouse</option>
                                        <option value="Infrastructure" {{ $loc->location_type === 'Infrastructure' ? 'selected' : '' }}>Infrastructure</option>
                                        <option value="Site" {{ $loc->location_type === 'Site' ? 'selected' : '' }}>Site</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Contact Person</label>
                                    <input type="text" name="contact_person" class="form-control" value="{{ $loc->contact_person }}">
                                </div>
                                <div class="col-6">
                                    <label class="form-label fw-semibold">Contact Phone</label>
                                    <input type="text" name="contact_phone" class="form-control" value="{{ $loc->contact_phone }}">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Priority</label>
                                <select name="priority" class="form-select" required>
                                    <option value="low" {{ $loc->priority === 'low' ? 'selected' : '' }}>Low</option>
                                    <option value="normal" {{ $loc->priority === 'normal' ? 'selected' : '' }}>Normal</option>
                                    <option value="high" {{ $loc->priority === 'high' ? 'selected' : '' }}>High</option>
                                    <option value="urgent" {{ $loc->priority === 'urgent' ? 'selected' : '' }}>Urgent</option>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label fw-semibold">Address</label>
                                <textarea name="address" class="form-control" rows="2">{{ $loc->address }}</textarea>
                            </div>
                        </div>
                        <div class="modal-footer bg-light">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary btn-sm">Update Location</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @empty
        <div class="col-12">
            <div class="card-custom p-5 text-center text-muted">
                <i class="fa-solid fa-location-crosshairs fs-1 mb-3 text-secondary"></i>
                <h5>No Locations Found</h5>
                <p>Add target premises to monitor visits and calculate geofenced arrivals.</p>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#createLocationModal">
                    <i class="fa-solid fa-plus me-1"></i>Add First Location
                </button>
            </div>
        </div>
    @endforelse
</div>

<div class="mt-4">
    {{ $locations->links('pagination::bootstrap-5') }}
</div>

<!-- Create Location Modal -->
<div class="modal fade" id="createLocationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('locations.store') }}" method="POST">
                @csrf
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold">
                        <i class="fa-solid fa-location-dot text-danger me-2"></i>Add Assigned Location
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Geographical Area <span class="text-danger">*</span></label>
                        <select name="geographical_area_id" class="form-select" required>
                            <option value="">-- Select Territory --</option>
                            @foreach($areas as $area)
                                <option value="{{ $area->id }}">{{ $area->name }} ({{ $area->code }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Location / Site Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Sudirman Central Branch" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Client Company</label>
                            <input type="text" name="client_name" class="form-control" placeholder="Client Name">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Site Code (Optional)</label>
                            <input type="text" name="code" class="form-control" placeholder="LOC-CBD-005">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Latitude <span class="text-danger">*</span></label>
                            <input type="number" step="0.000001" name="latitude" id="newLocLat" class="form-control" placeholder="-6.2088" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Longitude <span class="text-danger">*</span></label>
                            <input type="number" step="0.000001" name="longitude" id="newLocLng" class="form-control" placeholder="106.8456" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mb-3">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="fillCurrentCoordsBtn">
                            <i class="fa-solid fa-crosshairs me-1 text-primary"></i>Use My Current GPS Position
                        </button>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Geofence Radius (Meters) <span class="text-danger">*</span></label>
                            <input type="number" name="geofence_radius_meters" class="form-control" value="150" min="20" max="2000" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Location Type <span class="text-danger">*</span></label>
                            <select name="location_type" class="form-select" required>
                                <option value="Retail">Retail Store / Mall</option>
                                <option value="Office" selected>Corporate Office</option>
                                <option value="Warehouse">Logistics & Warehouse</option>
                                <option value="Infrastructure">Infrastructure / Telecom</option>
                                <option value="Site">Site / Industrial Terminal</option>
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-semibold">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control" placeholder="Full name">
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-semibold">Contact Phone</label>
                            <input type="text" name="contact_phone" class="form-control" placeholder="+62 21-...">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Priority</label>
                        <select name="priority" class="form-select" required>
                            <option value="low">Low</option>
                            <option value="normal" selected>Normal</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label fw-semibold">Full Physical Address</label>
                        <textarea name="address" class="form-control" rows="2" placeholder="Street, district, landmarks..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-plus me-1"></i>Save Location
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const map = L.map('assignedLocationsMap', {
        center: [-6.2088, 106.8456],
        zoom: 12
    });

    const tileLayers = {
        osm: L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19 }),
        google_satellite: L.tileLayer('https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', { maxZoom: 19 }),
        carto_positron: L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', { maxZoom: 19 }),
        carto_dark: L.tileLayer('https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png', { maxZoom: 19 })
    };

    let activeLayerKey = window.getSavedMapLayer() || 'osm';
    let currentTileLayer = (tileLayers[activeLayerKey] || tileLayers.osm).addTo(map);

    function switchLayer(key) {
        if (tileLayers[key]) {
            map.removeLayer(currentTileLayer);
            currentTileLayer = tileLayers[key].addTo(map);
            window.setSavedMapLayer(key);
            document.querySelectorAll('.loc-layer-btn').forEach(b => b.classList.toggle('active', b.dataset.layer === key));
        }
    }

    document.querySelectorAll('.loc-layer-btn').forEach(b => {
        b.addEventListener('click', function() { switchLayer(this.dataset.layer); });
    });

    // Populate Locations on Map
    const locationsData = @json($allMapLocations);
    const bounds = L.latLngBounds();

    locationsData.forEach(loc => {
        const coords = [loc.latitude, loc.longitude];
        bounds.extend(coords);

        const circle = L.circle(coords, {
            color: loc.geographical_area ? loc.geographical_area.color_code : '#2563eb',
            fillColor: '#3b82f6',
            fillOpacity: 0.12,
            radius: loc.geofence_radius_meters,
            weight: 2
        }).addTo(map);

        const marker = L.marker(coords).addTo(map);
        marker.bindPopup(`
            <div style="min-width: 220px;">
                <span class="badge bg-primary-subtle text-primary mb-1">${loc.location_type}</span>
                <h6 class="fw-bold text-dark mb-1">${loc.name}</h6>
                <small class="text-muted d-block mb-1">${loc.client_name || ''}</small>
                <div class="small text-muted mb-2">${loc.address || ''}</div>
                <div class="p-2 bg-light rounded small border">
                    <div><b>Geofence:</b> ${loc.geofence_radius_meters}m</div>
                    <div><b>Area:</b> ${loc.geographical_area ? loc.geographical_area.name : 'N/A'}</div>
                </div>
            </div>
        `);
    });

    if (bounds.isValid()) {
        map.fitBounds(bounds, { padding: [50, 50] });
    }

    // Toggle View button
    const container = document.getElementById('locationsMapContainer');
    const toggleBtn = document.getElementById('toggleViewBtn');
    const viewText = document.getElementById('viewModeText');
    let mapVisible = true;

    toggleBtn.addEventListener('click', function () {
        mapVisible = !mapVisible;
        container.style.display = mapVisible ? 'block' : 'none';
        viewText.textContent = mapVisible ? 'Hide Map' : 'Show Full Map';
        if (mapVisible) map.invalidateSize();
    });

    // Use Current GPS Position in Modal
    document.getElementById('fillCurrentCoordsBtn')?.addEventListener('click', function () {
        if (!navigator.geolocation) {
            alert('Geolocation not supported');
            return;
        }
        this.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i>Getting GPS...';
        navigator.geolocation.getCurrentPosition(
            pos => {
                document.getElementById('newLocLat').value = pos.coords.latitude.toFixed(6);
                document.getElementById('newLocLng').value = pos.coords.longitude.toFixed(6);
                this.innerHTML = '<i class="fa-solid fa-check text-success me-1"></i>GPS Captured!';
            },
            err => {
                this.innerHTML = '<i class="fa-solid fa-crosshairs me-1 text-primary"></i>Use My Current GPS Position';
                alert('GPS Error: ' + err.message);
            }
        );
    });
});
</script>
@endpush
