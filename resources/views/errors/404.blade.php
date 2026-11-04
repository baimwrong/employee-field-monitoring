<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Record Not Found | FieldTrack</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link href="{{ asset('vendor/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link href="{{ asset('vendor/fontawesome/css/all.min.css') }}" rel="stylesheet">
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #0f172a;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 1rem;
            max-width: 520px;
            width: 90%;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            text-align: center;
        }
        .error-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(59, 130, 246, 0.15);
            color: #60a5fa;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            margin-bottom: 1.5rem;
        }
        .error-code {
            font-size: 4rem;
            font-weight: 800;
            line-height: 1;
            letter-spacing: -2px;
            color: #60a5fa;
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="error-card">
        <div class="error-icon">
            <i class="fa-solid fa-map-location-dot"></i>
        </div>
        <div class="error-code">404</div>
        <h3 class="fw-bold mb-2">Record Not Located</h3>
        <p class="text-secondary mb-4">
            The requested field visit, inspection dossier, or geographical asset could not be found. It may have been archived or reassigned.
        </p>
        <div class="d-flex flex-column flex-sm-row gap-2 justify-content-center">
            <a href="{{ route('dashboard') }}" class="btn btn-primary px-4 py-2 fw-semibold">
                <i class="fa-solid fa-gauge me-2"></i>Return to Dashboard
            </a>
            <button onclick="window.history.back()" class="btn btn-outline-light px-4 py-2">
                <i class="fa-solid fa-arrow-left me-2"></i>Go Back
            </button>
        </div>
        <div class="mt-4 pt-3 border-top border-secondary text-secondary small">
            FieldTrack &bull; Enterprise Field Operations Monitoring
        </div>
    </div>
</body>
</html>
