<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'FieldTrack') - Employee Field Monitoring System</title>

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">

    <!-- Local Vendor CSS (Self-Hosted for High Availability / Air-Gapped Networks) -->
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/leaflet/leaflet.css') }}" />

    <style>
        :root {
            --brand-primary: #2563eb;
            --brand-primary-hover: #1d4ed8;
            --brand-dark: #0f172a;
            --brand-sidebar: #0f172a;
            --brand-sidebar-hover: #1e293b;
            --brand-sidebar-active: #3b82f6;
            --brand-bg: #f8fafc;
            --brand-card-border: #e2e8f0;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: var(--brand-bg);
            color: #334155;
            min-height: 100vh;
        }

        /* Layout */
        .app-wrapper {
            display: flex;
            min-height: 100vh;
        }

        .app-sidebar {
            width: 260px;
            background-color: var(--brand-sidebar);
            color: #94a3b8;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            transition: all 0.25s ease;
            z-index: 1030;
        }

        .app-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .sidebar-brand {
            padding: 1.25rem 1.5rem;
            color: #ffffff;
            font-size: 1.2rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            text-decoration: none;
        }

        .sidebar-nav {
            padding: 1rem 0.75rem;
            flex-grow: 1;
            overflow-y: auto;
        }

        .nav-section-title {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            padding: 0.75rem 0.75rem 0.35rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.65rem 0.85rem;
            border-radius: 0.5rem;
            color: #94a3b8;
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: all 0.2s ease;
            margin-bottom: 0.25rem;
        }

        .sidebar-link:hover {
            background-color: var(--brand-sidebar-hover);
            color: #ffffff;
        }

        .sidebar-link.active {
            background-color: var(--brand-sidebar-active);
            color: #ffffff;
            font-weight: 600;
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .sidebar-link i {
            width: 20px;
            text-align: center;
            font-size: 1rem;
        }

        .top-navbar {
            background: #ffffff;
            border-bottom: 1px solid var(--brand-card-border);
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 1020;
        }

        .main-container {
            padding: 1.75rem 1.5rem 3rem;
            flex-grow: 1;
        }

        /* Modern Cards */
        .card-custom {
            background: #ffffff;
            border: 1px solid var(--brand-card-border);
            border-radius: 0.75rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
            transition: box-shadow 0.2s ease;
        }

        .card-custom:hover {
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.06);
        }

        .kpi-card {
            border-radius: 0.75rem;
            border: 1px solid var(--brand-card-border);
            background: #ffffff;
            padding: 1.25rem;
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .kpi-icon {
            width: 52px;
            height: 52px;
            border-radius: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.35rem;
            flex-shrink: 0;
        }

        /* Leaflet custom styling */
        .leaflet-popup-content-wrapper {
            border-radius: 0.75rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.15);
            font-family: inherit;
        }

        .leaflet-container {
            font-family: 'Plus Jakarta Sans', sans-serif !important;
        }

        .status-badge {
            font-size: 0.75rem;
            padding: 0.3em 0.7em;
            border-radius: 2rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        @media (max-width: 991.98px) {
            .app-sidebar {
                position: fixed;
                left: -260px;
                top: 0;
                bottom: 0;
            }
            .app-sidebar.show {
                left: 0;
            }
            .sidebar-backdrop {
                position: fixed;
                inset: 0;
                background: rgba(15, 23, 42, 0.6);
                z-index: 1025;
                display: none;
            }
            .sidebar-backdrop.show {
                display: block;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>

    <div class="app-wrapper">
        <!-- Sidebar Navigation -->
        <aside class="app-sidebar" id="appSidebar">
            <a href="{{ route('dashboard') }}" class="sidebar-brand">
                <i class="fa-solid fa-earth-americas text-primary fa-lg"></i>
                <div>
                    <div>FieldTrack</div>
                    <small style="font-size: 0.65rem; color: #64748b; font-weight: 500;">Field Force Monitoring</small>
                </div>
            </a>

            <div class="sidebar-nav">
                <div class="nav-section-title">Core Operations</div>
                <a href="{{ route('dashboard') }}" class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span>Overview Dashboard</span>
                </a>
                @if(auth()->user()?->hasAnyRole(['admin', 'supervisor']))
                <a href="{{ route('users.index') }}" class="sidebar-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-users-gear"></i>
                    <span>Staff & Roles</span>
                </a>
                @endif
                <a href="{{ route('areas.index') }}" class="sidebar-link {{ request()->routeIs('areas.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-map-location-dot"></i>
                    <span>Geographical Areas</span>
                </a>
                <a href="{{ route('locations.index') }}" class="sidebar-link {{ request()->routeIs('locations.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-location-crosshairs"></i>
                    <span>Assigned Locations</span>
                </a>

                <div class="nav-section-title">Field Monitoring</div>
                <a href="{{ route('visits.index') }}" class="sidebar-link {{ request()->routeIs('visits.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-route"></i>
                    <span>Field Visits</span>
                </a>
                <a href="{{ route('inspections.index') }}" class="sidebar-link {{ request()->routeIs('inspections.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-clipboard-check"></i>
                    <span>Inspection Reports</span>
                </a>

                @if(auth()->user()?->hasAnyRole(['admin', 'supervisor']))
                <div class="nav-section-title">Audit & History</div>
                <a href="{{ route('activities.index') }}" class="sidebar-link {{ request()->routeIs('activities.*') ? 'active' : '' }}">
                    <i class="fa-solid fa-clock-rotate-left"></i>
                    <span>Activity History</span>
                </a>
                @endif
            </div>

            <!-- Bottom System Info -->
            <div class="p-3 border-top border-secondary border-opacity-10 text-center" style="font-size: 0.75rem; color: #64748b;">
                <div class="d-flex align-items-center justify-content-center gap-2 mb-1">
                    <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-circle text-success" style="font-size: 0.5rem;"></i> Postgres 18 Connected</span>
                </div>
                <div>Laravel 13 &bull; FieldTrack v2.4</div>
            </div>
        </aside>

        <!-- Main Content Area -->
        <div class="app-content">
            <!-- Top Navbar -->
            <header class="top-navbar">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-light d-lg-none border" id="sidebarToggle" type="button">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div class="d-none d-md-flex align-items-center gap-2 text-muted" style="font-size: 0.85rem;">
                        <i class="fa-solid fa-building-user text-primary"></i>
                        <span>Field Operations Monitoring Console</span>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <!-- Quick Map Layers Switcher Modal Trigger -->
                    <button class="btn btn-sm btn-outline-secondary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#mapSettingsModal">
                        <i class="fa-solid fa-layer-group text-primary"></i>
                        <span class="d-none d-sm-inline">Map Layer / Google Maps</span>
                    </button>

                    <!-- Field Officer Live Duty GPS Tracker Switch -->
                    @if(auth()->user()?->role === 'field_officer')
                    <button type="button" id="dutyGpsToggleBtn" class="btn btn-sm btn-outline-success d-flex align-items-center gap-2" title="Toggle automatic background GPS beacon to operations dispatch">
                        <span class="spinner-grow spinner-grow-sm text-success d-none" id="dutyGpsPulse" role="status" style="width: 8px; height: 8px;"></span>
                        <i class="fa-solid fa-satellite-dish" id="dutyGpsIcon"></i>
                        <span class="d-none d-md-inline" id="dutyGpsText">Live Duty GPS: Off</span>
                    </button>
                    @endif

                    <!-- Realtime Clock -->
                    <div class="d-none d-lg-flex align-items-center gap-2 px-3 py-1 bg-light rounded-pill border text-muted" style="font-size: 0.8rem;">
                        <i class="fa-regular fa-clock"></i>
                        <span id="liveClock">{{ now()->format('H:i:s') }}</span>
                    </div>

                    <!-- User Profile Dropdown -->
                    @php
                        $currentUser = auth()->user();
                    @endphp
                    @if($currentUser)
                    <div class="dropdown">
                        <button class="btn btn-light border dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 32px; height: 32px; font-size: 0.85rem;">
                                {{ strtoupper(substr($currentUser->name, 0, 2)) }}
                            </div>
                            <div class="text-start d-none d-sm-block">
                                <div class="fw-semibold lh-1" style="font-size: 0.85rem;">{{ $currentUser->name }}</div>
                                <small class="badge bg-primary-subtle text-primary border border-primary-subtle py-0 px-1 mt-1" style="font-size: 0.65rem;">
                                    {{ ucfirst(str_replace('_', ' ', $currentUser->role)) }}
                                </small>
                            </div>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="min-width: 250px;">
                            <li><h6 class="dropdown-header text-uppercase" style="font-size: 0.7rem;">Active Profile</h6></li>
                            <li class="px-3 py-1">
                                <div class="fw-bold text-dark" style="font-size: 0.85rem;">{{ $currentUser->name }}</div>
                                <small class="text-muted">{{ $currentUser->email }}</small>
                            </li>
                            @if(app()->isLocal())
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header text-uppercase" style="font-size: 0.7rem;">Quick Role Switcher (Local Demo)</h6></li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center justify-content-between" href="{{ route('login.quick', 'admin') }}">
                                        <span><i class="fa-solid fa-crown text-primary me-2"></i>Admin</span>
                                        <small class="text-muted">Alexander</small>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center justify-content-between" href="{{ route('login.quick', 'supervisor') }}">
                                        <span><i class="fa-solid fa-user-shield text-warning me-2"></i>Supervisor</span>
                                        <small class="text-muted">Sarah</small>
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item d-flex align-items-center justify-content-between" href="{{ route('login.quick', 'field_officer') }}">
                                        <span><i class="fa-solid fa-person-walking text-success me-2"></i>Field Officer</span>
                                        <small class="text-muted">Ahmad</small>
                                    </a>
                                </li>
                            @endif
                            @if(auth()->user()?->hasAnyRole(['admin', 'supervisor']))
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <a class="dropdown-item" href="{{ route('users.index') }}"><i class="fa-solid fa-users-gear me-2 text-muted"></i>Staff Directory & Roles</a>
                            </li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                    <i class="fa-solid fa-key me-2 text-muted"></i>Change Password
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('logout') }}" method="POST" class="px-2">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-outline-danger w-100 text-start">
                                        <i class="fa-solid fa-power-off me-2"></i>Sign Out
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-sm btn-primary">
                            <i class="fa-solid fa-arrow-right-to-bracket me-1"></i>Sign In
                        </a>
                    @endif
                </div>
            </header>

            <!-- Flash Alerts -->
            <div class="px-4 pt-3">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm border-0" role="alert">
                        <i class="fa-solid fa-circle-check fs-5"></i>
                        <div>{{ session('success') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('warning'))
                    <div class="alert alert-warning alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm border-0" role="alert">
                        <i class="fa-solid fa-triangle-exclamation fs-5"></i>
                        <div>{{ session('warning') }}</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0" role="alert">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <i class="fa-solid fa-circle-exclamation fs-5"></i>
                            <strong>Validation Notice:</strong>
                        </div>
                        <ul class="mb-0 ps-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif
            </div>

            <!-- Main Page Content -->
            <main class="main-container">
                @yield('content')
            </main>
        </div>
    </div>

    <!-- Map Settings / Layer Switcher Modal -->
    <div class="modal fade" id="mapSettingsModal" tabindex="-1" aria-labelledby="mapSettingsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="mapSettingsModalLabel">
                        <i class="fa-solid fa-map-location-dot text-primary me-2"></i>Map Provider & Display Mode
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="text-muted" style="font-size: 0.9rem;">
                        You can easily change the interactive map layer between street view, Google Maps satellite imagery, clean light, or high-contrast dark theme.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-semibold">Select Active Map Layer</label>
                        <select class="form-select" id="globalLayerSelect">
                            <option value="osm">OpenStreetMap Standard (Clean Street View)</option>
                            <option value="google_satellite">Google Satellite / Esri Imagery (High-Resolution Satellite)</option>
                            <option value="carto_positron">Carto Positron (Modern Light Minimalist)</option>
                            <option value="carto_dark">Carto Dark Matter (Night Operations / Dark)</option>
                            <option value="opentopo">OpenTopoMap (Topographic & Elevation Contours)</option>
                        </select>
                    </div>

                    <div class="p-3 bg-light rounded-3 border">
                        <div class="d-flex align-items-center gap-2 mb-1 fw-semibold" style="font-size: 0.85rem;">
                            <i class="fa-brands fa-google text-danger"></i> Google Maps Platform Integration
                        </div>
                        <small class="text-muted d-block mb-2" style="font-size: 0.8rem;">
                            The application automatically uses tile imagery compatible with Google Maps and GIS overlays. If you have an official Google Maps API key, you can enter it below for native SDK features.
                        </small>
                        <input type="text" class="form-control form-control-sm" id="googleMapsApiKeyInput" placeholder="Optional: Enter Google Maps API Key (AIzaSy...)" value="">
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-sm" id="applyMapLayerBtn" data-bs-dismiss="modal">
                        <i class="fa-solid fa-check me-1"></i>Apply Map Layer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Self-Service Change Password Modal -->
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-sm">
            <div class="modal-content border-0 shadow">
                <form action="{{ route('profile.password') }}" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="modal-header bg-light">
                        <h6 class="modal-title fw-bold" id="changePasswordModalLabel">
                            <i class="fa-solid fa-key text-primary me-2"></i>Change Password
                        </h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Current Password</label>
                            <input type="password" name="current_password" class="form-control form-control-sm" required autocomplete="current-password">
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">New Password (min 8 chars)</label>
                            <input type="password" name="password" class="form-control form-control-sm" required minlength="8" autocomplete="new-password">
                        </div>
                        <div class="mb-2">
                            <label class="form-label fw-semibold small">Confirm New Password</label>
                            <input type="password" name="password_confirmation" class="form-control form-control-sm" required minlength="8" autocomplete="new-password">
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fa-solid fa-check me-1"></i>Update Password
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Local Vendor JS (Self-Hosted) -->
    <script src="{{ asset('vendor/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('vendor/leaflet/leaflet.js') }}"></script>

    <script>
        // Sidebar Mobile Toggle
        const sidebar = document.getElementById('appSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        const toggleBtn = document.getElementById('sidebarToggle');

        if (toggleBtn) {
            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('show');
                backdrop.classList.toggle('show');
            });
        }

        if (backdrop) {
            backdrop.addEventListener('click', () => {
                sidebar.classList.remove('show');
                backdrop.classList.remove('show');
            });
        }

        // Global HTML sanitizer for Leaflet popups and dynamic UI strings
        window.escapeHtml = function(str) {
            if (str === null || str === undefined) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        };

        // Live Clock
        function updateClock() {
            const now = new Date();
            const timeStr = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false });
            const clockEl = document.getElementById('liveClock');
            if (clockEl) clockEl.textContent = timeStr;
        }
        setInterval(updateClock, 1000);

        // Global Map Layer Preference Store
        window.getSavedMapLayer = function() {
            return localStorage.getItem('fieldtrack_map_layer') || 'osm';
        };

        window.setSavedMapLayer = function(layerKey) {
            localStorage.setItem('fieldtrack_map_layer', layerKey);
        };

        document.getElementById('applyMapLayerBtn')?.addEventListener('click', function() {
            const selected = document.getElementById('globalLayerSelect').value;
            window.setSavedMapLayer(selected);
            if (typeof window.switchActiveMapLayer === 'function') {
                window.switchActiveMapLayer(selected);
            }
        });

        // Initialize modal select with saved layer
        const layerSelect = document.getElementById('globalLayerSelect');
        if (layerSelect) {
            layerSelect.value = window.getSavedMapLayer();
        }

        // Field Officer Live Duty GPS Beacon
        (function() {
            const toggleBtn = document.getElementById('dutyGpsToggleBtn');
            if (!toggleBtn) return;

            let watchId = null;
            let lastPingTime = 0;
            const PING_INTERVAL_MS = 25000;

            const pulseEl = document.getElementById('dutyGpsPulse');
            const iconEl = document.getElementById('dutyGpsIcon');
            const textEl = document.getElementById('dutyGpsText');

            function sendPing(payload) {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                fetch('{{ route("api.pingLocation") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(payload)
                }).catch(err => console.debug('GPS beacon network error:', err));
            }

            function startGpsTracking() {
                if (!navigator.geolocation) {
                    alert('Geolocation is not supported by your browser.');
                    return;
                }

                toggleBtn.classList.remove('btn-outline-success');
                toggleBtn.classList.add('btn-success', 'text-white');
                if (pulseEl) pulseEl.classList.remove('d-none');
                if (textEl) textEl.textContent = 'Live Duty GPS: Active';
                localStorage.setItem('fieldtrack_duty_gps', 'active');

                watchId = navigator.geolocation.watchPosition(
                    position => {
                        const now = Date.now();
                        if (now - lastPingTime < PING_INTERVAL_MS) return;
                        lastPingTime = now;

                        const payload = {
                            latitude: position.coords.latitude,
                            longitude: position.coords.longitude,
                            battery_level: null
                        };

                        if (navigator.getBattery) {
                            navigator.getBattery().then(batt => {
                                payload.battery_level = Math.round(batt.level * 100);
                                sendPing(payload);
                            }).catch(() => sendPing(payload));
                        } else {
                            sendPing(payload);
                        }
                    },
                    err => console.warn('Duty GPS error:', err.message),
                    { enableHighAccuracy: true, timeout: 15000, maximumAge: 10000 }
                );
            }

            function stopGpsTracking() {
                if (watchId !== null) {
                    navigator.geolocation.clearWatch(watchId);
                    watchId = null;
                }
                toggleBtn.classList.remove('btn-success', 'text-white');
                toggleBtn.classList.add('btn-outline-success');
                if (pulseEl) pulseEl.classList.add('d-none');
                if (textEl) textEl.textContent = 'Live Duty GPS: Off';
                localStorage.setItem('fieldtrack_duty_gps', 'inactive');
            }

            toggleBtn.addEventListener('click', function() {
                if (watchId === null) {
                    startGpsTracking();
                } else {
                    stopGpsTracking();
                }
            });

            if (localStorage.getItem('fieldtrack_duty_gps') === 'active') {
                startGpsTracking();
            }
        })();
    </script>
    @stack('scripts')
</body>
</html>
