<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In - FieldTrack Monitoring System</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- Local Vendor CSS (Self-Hosted) -->
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">

    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            color: #334155;
        }

        .login-card {
            background: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.4);
            max-width: 960px;
            width: 100%;
            overflow: hidden;
        }

        .role-switch-card {
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            padding: 1rem;
            transition: all 0.2s ease;
            background: #f8fafc;
            text-align: left;
            width: 100%;
            text-decoration: none;
            display: block;
        }

        .role-switch-card:hover {
            border-color: #3b82f6;
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="login-card">
        <div class="row g-0">
            <!-- Left Column: Login Form -->
            <div class="col-12 col-lg-6 p-4 p-md-5">
                <div class="d-flex align-items-center gap-2 mb-4">
                    <span class="bg-primary text-white rounded-3 p-2 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fa-solid fa-earth-americas"></i>
                    </span>
                    <div>
                        <h4 class="fw-bold mb-0 text-dark">FieldTrack</h4>
                        <small class="text-muted">Employee Field Monitoring System</small>
                    </div>
                </div>

                <h5 class="fw-bold text-dark mb-1">Account Sign In</h5>
                <p class="text-muted small mb-4">Enter your corporate credentials to access the monitoring dashboard.</p>

                @if(session('success'))
                    <div class="alert alert-success py-2 px-3 small border-0 mb-3">
                        <i class="fa-solid fa-check me-1"></i>{{ session('success') }}
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-danger py-2 px-3 small border-0 mb-3">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>{{ $errors->first() }}
                    </div>
                @endif

                <form action="{{ route('login.post') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Corporate Email Address</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-envelope text-muted"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="name@fieldmonitor.com" value="{{ old('email', 'admin@fieldmonitor.com') }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between">
                            <label class="form-label fw-semibold small">Password</label>
                            <small class="text-muted">Default: <code>password</code></small>
                        </div>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-solid fa-lock text-muted"></i></span>
                            <input type="password" name="password" class="form-control" placeholder="••••••••" value="password" required>
                        </div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between mb-4">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="remember" id="rememberMe" checked>
                            <label class="form-check-label small text-muted" for="rememberMe">Remember Session</label>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="fa-solid fa-right-to-bracket me-2"></i>Sign In to Console
                    </button>
                </form>

                <div class="mt-4 pt-3 border-top text-center text-muted" style="font-size: 0.75rem;">
                    PostgreSQL 18 &bull; Laravel 13 &bull; Enterprise Field Security
                </div>
            </div>

            <!-- Right Column: Quick Role Switcher for Pair-Programming & Demos -->
            <div class="col-12 col-lg-6 bg-light p-4 p-md-5 border-start d-flex flex-column justify-content-between">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="badge bg-primary-subtle text-primary border"><i class="fa-solid fa-bolt me-1"></i>Instant Demo Switcher</span>
                    </div>
                    <h5 class="fw-bold text-dark mb-1">Explore by Role (1-Click Login)</h5>
                    <p class="text-muted small mb-4">
                        Select any role below to instantly log in and experience the tailored interface and permissions:
                    </p>

                    <div class="d-flex flex-column gap-3 mb-4">
                        <!-- Admin -->
                        <a href="{{ route('login.quick', 'admin') }}" class="role-switch-card">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fa-solid fa-crown"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $demoUsers['admin']->name ?? 'Alexander Vance' }}</div>
                                        <small class="text-muted">Administrator &bull; Full Master Access</small>
                                    </div>
                                </div>
                                <span class="badge bg-primary text-white px-2 py-1">Login &rarr;</span>
                            </div>
                        </a>

                        <!-- Supervisor -->
                        <a href="{{ route('login.quick', 'supervisor') }}" class="role-switch-card">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-warning text-dark rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fa-solid fa-user-shield"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $demoUsers['supervisor']->name ?? 'Sarah Jenkins' }}</div>
                                        <small class="text-muted">Operations Supervisor &bull; Dispatch & Review</small>
                                    </div>
                                </div>
                                <span class="badge bg-warning text-dark px-2 py-1">Login &rarr;</span>
                            </div>
                        </a>

                        <!-- Field Officer -->
                        <a href="{{ route('login.quick', 'field_officer') }}" class="role-switch-card">
                            <div class="d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                        <i class="fa-solid fa-person-walking"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark">{{ $demoUsers['field_officer']->name ?? 'Ahmad Rifai' }}</div>
                                        <small class="text-muted">Field Officer &bull; GPS Check-In & Audits</small>
                                    </div>
                                </div>
                                <span class="badge bg-success text-white px-2 py-1">Login &rarr;</span>
                            </div>
                        </a>
                    </div>
                </div>

                <div class="p-3 bg-white rounded-3 border small">
                    <div class="fw-semibold text-dark mb-1"><i class="fa-solid fa-circle-info text-primary me-1"></i>Role Capabilities Summary:</div>
                    <ul class="text-muted mb-0 ps-3" style="font-size: 0.75rem;">
                        <li><b>Admin:</b> Manage zones, staff accounts, client locations, audit history.</li>
                        <li><b>Supervisor:</b> Monitor live map, dispatch field visits, approve inspection reports.</li>
                        <li><b>Field Officer:</b> Record GPS check-in/out, complete checklists.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
