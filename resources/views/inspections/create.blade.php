@extends('layouts.app')

@section('title', 'Submit New Inspection Report')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Submit Field Inspection Report</h3>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">
            Document physical site assessments, record compliance checklist results, and submit findings for quality review.
        </p>
    </div>
    <a href="{{ route('inspections.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="fa-solid fa-arrow-left me-1"></i>Back to Reports
    </a>
</div>

<!-- LocalStorage Auto-Saved Draft Restoration Banner -->
<div id="draftAlert" class="alert alert-warning border-warning d-none d-flex flex-column flex-md-row align-items-md-center justify-content-between mb-4 shadow-sm" role="alert">
    <div class="d-flex align-items-center gap-2 mb-2 mb-md-0">
        <i class="fa-solid fa-floppy-disk text-warning fs-5"></i>
        <div>
            <span class="fw-bold">Unsaved assessment draft recovered!</span>
            <span class="text-muted small ms-1" id="draftTimestamp"></span>
            <div class="small text-muted">A locally cached version was preserved on this device. Would you like to restore it?</div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-sm btn-primary" id="restoreDraftBtn">
            <i class="fa-solid fa-rotate-left me-1"></i>Restore Draft
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary" id="discardDraftBtn">
            <i class="fa-solid fa-trash me-1"></i>Discard
        </button>
    </div>
</div>

<form id="inspectionForm" action="{{ route('inspections.store') }}" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="row g-4">
        <!-- Left: General Information & Photographic Evidence -->
        <div class="col-12 col-lg-7">
            <div class="card-custom p-4 mb-4">
                <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-file-invoice text-primary me-2"></i>Report Details</h5>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Report Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" placeholder="e.g. Wisma Nusantara Q4 Electrical & Fire Compliance Inspection" required>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Target Location <span class="text-danger">*</span></label>
                        <select name="location_id" class="form-select" required>
                            <option value="">-- Choose Location --</option>
                            @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ ($selectedVisit && $selectedVisit->location_id == $loc->id) ? 'selected' : '' }}>
                                    {{ $loc->name }} ({{ $loc->client_name ?? 'Client' }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Field Inspector / Officer <span class="text-danger">*</span></label>
                        @if(auth()->user()?->hasAnyRole(['admin', 'supervisor']))
                        <select name="user_id" class="form-select" required>
                            <option value="">-- Choose Inspector --</option>
                            @foreach($inspectors as $ins)
                                <option value="{{ $ins->id }}" {{ ($selectedVisit && $selectedVisit->user_id == $ins->id) || (auth()->id() == $ins->id) ? 'selected' : '' }}>
                                    {{ $ins->name }} ({{ $ins->employee_code }})
                                </option>
                            @endforeach
                        </select>
                        @else
                        <input type="text" class="form-control bg-light" value="{{ auth()->user()->name }} ({{ auth()->user()->employee_code ?? 'EMP' }})" readonly>
                        <input type="hidden" name="user_id" value="{{ auth()->id() }}">
                        @endif
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Inspection Category <span class="text-danger">*</span></label>
                        <select name="category" class="form-select" required>
                            <option value="Safety & Quality" selected>Safety & Quality Audit</option>
                            <option value="Facility & Grounds">Facility & Physical Grounds</option>
                            <option value="Equipment & Machinery">Equipment & Critical Machinery</option>
                            <option value="Environmental & Hazardous">Environmental & Health</option>
                            <option value="Infrastructure Integrity">Infrastructure & Telecommunications</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Inspection Date <span class="text-danger">*</span></label>
                        <input type="date" name="inspection_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Linked Field Visit (Optional)</label>
                    <select name="field_visit_id" class="form-select">
                        <option value="">-- Standalone Report (No visit linked) --</option>
                        @foreach($availableVisits as $v)
                            <option value="{{ $v->id }}" {{ ($selectedVisit && $selectedVisit->id == $v->id) ? 'selected' : '' }}>
                                {{ $v->visit_code }} &bull; {{ $v->location->name ?? 'Site' }} &bull; {{ $v->user->name ?? 'Officer' }} ({{ $v->scheduled_at->format('M d') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Executive Summary <span class="text-danger">*</span></label>
                    <textarea name="executive_summary" class="form-control" rows="3" placeholder="Provide a high-level summary of the site walkthrough and overall condition..." required></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Major Observations & Findings</label>
                    <textarea name="major_findings" class="form-control" rows="3" placeholder="Detail any non-compliances, defective equipment, or safety risks observed..."></textarea>
                </div>

                <div class="mb-0">
                    <label class="form-label fw-semibold">Corrective Actions & Recommendations</label>
                    <textarea name="corrective_recommendations" class="form-control" rows="3" placeholder="List action items required from the property management team..."></textarea>
                </div>
            </div>

            <!-- Photographic Evidence Uploads -->
            <div class="card-custom p-4 mb-4">
                <h5 class="fw-bold text-dark mb-2"><i class="fa-solid fa-camera text-primary me-2"></i>Photographic Audit Evidence</h5>
                <p class="text-muted small mb-3">Attach geo-tagged site photos, hazard evidence, or asset condition snapshots (JPEG/PNG, up to 8MB each).</p>

                <div class="mb-3">
                    <input type="file" name="photos[]" id="photosInput" class="form-control" multiple accept="image/*" capture="environment">
                </div>
                <div id="photosPreviewList" class="d-flex flex-column gap-2"></div>
            </div>
        </div>

        <!-- Right: Automated Compliance Scoring & Dynamic Checklist Items -->
        <div class="col-12 col-lg-5">
            <!-- Score Card -->
            <div class="card-custom p-4 mb-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-gauge-high text-warning me-2"></i>Audit Scoring</h5>
                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 0.7rem;">Automated</span>
                </div>
                <small class="text-muted d-block mb-3" style="font-size: 0.8rem;">
                    Compliance score is dynamically calculated from your checklist evaluations (Pass vs Fail).
                </small>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Compliance Score (0 - 100%)</label>
                    <div class="input-group">
                        <input type="number" step="0.1" name="compliance_score" id="scoreInput" class="form-control bg-light fw-bold fs-5 text-primary" value="100.0" min="0" max="100" readonly required>
                        <span class="input-group-text fw-bold">%</span>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-semibold">Result Grade</label>
                    <select name="result_grade" id="gradeSelect" class="form-select bg-light fw-semibold" required>
                        <option value="Passed" selected>Passed</option>
                        <option value="Warning / Needs Improvement">Warning / Needs Improvement</option>
                        <option value="Failed">Failed</option>
                    </select>
                </div>
            </div>

            <!-- Dynamic Checklist Items -->
            <div class="card-custom p-4">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h5 class="fw-bold text-dark mb-0"><i class="fa-solid fa-list-check text-success me-2"></i>Audit Checklist</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addChecklistItemBtn">
                        <i class="fa-solid fa-plus me-1"></i>Add Item
                    </button>
                </div>

                <div id="checklistContainer" class="d-flex flex-column gap-3">
                    <!-- Default Item 1 -->
                    <div class="p-3 bg-light rounded-3 border checklist-row">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold small text-muted">Item #1</span>
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 remove-item-btn"><i class="fa-solid fa-trash"></i></button>
                        </div>
                        <input type="text" name="items[0][item_name]" class="form-control form-control-sm mb-2" value="Emergency Exit Doors & Egress Paths Unblocked" placeholder="Item Name" required>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <select name="items[0][status]" class="form-select form-select-sm item-status-select">
                                    <option value="pass" selected>Pass</option>
                                    <option value="fail">Fail</option>
                                    <option value="na">N/A</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select name="items[0][severity]" class="form-select form-select-sm item-severity-select">
                                    <option value="low" selected>Low Severity</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                        </div>
                        <input type="text" name="items[0][notes]" class="form-control form-control-sm" placeholder="Observation notes..." value="All 4 exit routes clear of storage or obstruction.">
                    </div>

                    <!-- Default Item 2 -->
                    <div class="p-3 bg-light rounded-3 border checklist-row">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="fw-bold small text-muted">Item #2</span>
                            <button type="button" class="btn btn-sm btn-link text-danger p-0 remove-item-btn"><i class="fa-solid fa-trash"></i></button>
                        </div>
                        <input type="text" name="items[1][item_name]" class="form-control form-control-sm mb-2" value="Electrical Distribution Panels Sealed & Grounded" placeholder="Item Name" required>
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <select name="items[1][status]" class="form-select form-select-sm item-status-select">
                                    <option value="pass" selected>Pass</option>
                                    <option value="fail">Fail</option>
                                    <option value="na">N/A</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select name="items[1][severity]" class="form-select form-select-sm item-severity-select">
                                    <option value="low" selected>Low Severity</option>
                                    <option value="medium">Medium</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                        </div>
                        <input type="text" name="items[1][notes]" class="form-control form-control-sm" placeholder="Observation notes..." value="Breaker panel locked with authorized hazard signage.">
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold">
                        <i class="fa-solid fa-cloud-arrow-up me-2"></i>Submit Inspection Dossier
                    </button>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    let itemIndex = 2;
    const container = document.getElementById('checklistContainer');
    const addBtn = document.getElementById('addChecklistItemBtn');
    const scoreInput = document.getElementById('scoreInput');
    const gradeSelect = document.getElementById('gradeSelect');

    // Session Keep-Alive ping every 4 minutes (240,000ms) to prevent 419 session expiration during long audits
    setInterval(function () {
        fetch('{{ route("api.keepAlive") }}', {
            headers: { 'Accept': 'application/json' }
        }).catch(err => console.debug('Keep-alive ping failed', err));
    }, 240000);

    function calculateAuditScore() {
        const rows = document.querySelectorAll('.checklist-row');
        let total = 0;
        let passed = 0;
        let hasCriticalFail = false;

        rows.forEach(r => {
            const status = r.querySelector('.item-status-select')?.value;
            const severity = r.querySelector('.item-severity-select')?.value;

            if (status && status !== 'na') {
                total++;
                if (status === 'pass') {
                    passed++;
                } else if (status === 'fail' && severity === 'critical') {
                    hasCriticalFail = true;
                }
            }
        });

        let score = 100.0;
        if (total > 0) {
            score = Math.round((passed / total) * 1000) / 10;
        }

        scoreInput.value = score.toFixed(1);

        if (hasCriticalFail || score < 65) {
            gradeSelect.value = 'Failed';
        } else if (score < 85) {
            gradeSelect.value = 'Warning / Needs Improvement';
        } else {
            gradeSelect.value = 'Passed';
        }
    }

    function renderChecklistRow(idx, name = '', status = 'pass', severity = 'low', notes = '') {
        const div = document.createElement('div');
        div.className = 'p-3 bg-light rounded-3 border checklist-row';
        div.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-bold small text-muted">Item #${idx + 1}</span>
                <button type="button" class="btn btn-sm btn-link text-danger p-0 remove-item-btn"><i class="fa-solid fa-trash"></i></button>
            </div>
            <input type="text" name="items[${idx}][item_name]" class="form-control form-control-sm mb-2" value="${escapeHtml(name)}" placeholder="Item Name e.g. HVAC Air Filters Checked" required>
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <select name="items[${idx}][status]" class="form-select form-select-sm item-status-select">
                        <option value="pass" ${status === 'pass' ? 'selected' : ''}>Pass</option>
                        <option value="fail" ${status === 'fail' ? 'selected' : ''}>Fail</option>
                        <option value="na" ${status === 'na' ? 'selected' : ''}>N/A</option>
                    </select>
                </div>
                <div class="col-6">
                    <select name="items[${idx}][severity]" class="form-select form-select-sm item-severity-select">
                        <option value="low" ${severity === 'low' ? 'selected' : ''}>Low Severity</option>
                        <option value="medium" ${severity === 'medium' ? 'selected' : ''}>Medium</option>
                        <option value="high" ${severity === 'high' ? 'selected' : ''}>High</option>
                        <option value="critical" ${severity === 'critical' ? 'selected' : ''}>Critical</option>
                    </select>
                </div>
            </div>
            <input type="text" name="items[${idx}][notes]" class="form-control form-control-sm" value="${escapeHtml(notes)}" placeholder="Observation notes...">
        `;
        container.appendChild(div);
    }

    addBtn.addEventListener('click', function () {
        renderChecklistRow(itemIndex, '', 'pass', 'low', '');
        itemIndex++;
        calculateAuditScore();
    });

    container.addEventListener('click', function (e) {
        if (e.target.closest('.remove-item-btn')) {
            const row = e.target.closest('.checklist-row');
            if (document.querySelectorAll('.checklist-row').length > 1) {
                row.remove();
                calculateAuditScore();
            } else {
                alert('At least one checklist item is required.');
            }
        }
    });

    container.addEventListener('change', function (e) {
        if (e.target.classList.contains('item-status-select') || e.target.classList.contains('item-severity-select')) {
            calculateAuditScore();
        }
    });

    // Client-side HTML5 canvas image downscaling (1920x1080 @ 0.8 JPEG quality)
    function compressImage(file, maxWidth = 1920, maxHeight = 1080, quality = 0.8) {
        return new Promise((resolve) => {
            if (!file.type.startsWith('image/')) {
                resolve({ file, originalSize: file.size, compressedSize: file.size });
                return;
            }
            const reader = new FileReader();
            reader.onload = (e) => {
                const img = new Image();
                img.onload = () => {
                    let width = img.width;
                    let height = img.height;
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
                    canvas.toBlob((blob) => {
                        if (!blob || blob.size >= file.size) {
                            resolve({ file, originalSize: file.size, compressedSize: file.size });
                        } else {
                            const compressedFile = new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), {
                                type: 'image/jpeg',
                                lastModified: Date.now()
                            });
                            resolve({ file: compressedFile, originalSize: file.size, compressedSize: blob.size });
                        }
                    }, 'image/jpeg', quality);
                };
                img.onerror = () => resolve({ file, originalSize: file.size, compressedSize: file.size });
                img.src = e.target.result;
            };
            reader.onerror = () => resolve({ file, originalSize: file.size, compressedSize: file.size });
            reader.readAsDataURL(file);
        });
    }

    // Photographic evidence preview list with auto-compression
    const photosInput = document.getElementById('photosInput');
    const previewList = document.getElementById('photosPreviewList');

    if (photosInput && previewList) {
        photosInput.addEventListener('change', async function () {
            if (!this.files.length) return;

            previewList.innerHTML = '<div class="text-muted small"><i class="fa-solid fa-spinner fa-spin me-1"></i>Optimizing evidence photographs...</div>';

            const dt = new DataTransfer();
            const results = [];

            for (let i = 0; i < this.files.length; i++) {
                const res = await compressImage(this.files[i]);
                dt.items.add(res.file);
                results.push(res);
            }

            this.files = dt.files;
            previewList.innerHTML = '';

            results.forEach((res, idx) => {
                const origKb = (res.originalSize / 1024).toFixed(0);
                const compKb = (res.compressedSize / 1024).toFixed(0);
                const ratio = res.originalSize > 0 ? Math.round((1 - (res.compressedSize / res.originalSize)) * 100) : 0;
                const ratioBadge = ratio > 0 ? `<span class="badge bg-success-subtle text-success ms-1">-${ratio}%</span>` : '';

                const item = document.createElement('div');
                item.className = 'd-flex align-items-center justify-content-between p-2 bg-light rounded border small';
                item.innerHTML = `
                    <div class="d-flex align-items-center gap-2">
                        <i class="fa-solid fa-image text-primary"></i>
                        <span class="text-truncate" style="max-width: 180px;">${escapeHtml(res.file.name)}</span>
                        <small class="text-muted">(${compKb} KB ${ratioBadge})</small>
                    </div>
                    <input type="text" name="photo_captions[${idx}]" class="form-control form-control-sm w-50" placeholder="Caption for photo #${idx + 1}...">
                `;
                previewList.appendChild(item);
            });
        });
    }

    // LocalStorage Draft Auto-Save & Recovery
    const DRAFT_KEY = 'fieldtrack_inspection_draft_v1';
    const form = document.getElementById('inspectionForm');
    const draftAlert = document.getElementById('draftAlert');
    const draftTimestamp = document.getElementById('draftTimestamp');
    const restoreDraftBtn = document.getElementById('restoreDraftBtn');
    const discardDraftBtn = document.getElementById('discardDraftBtn');

    function collectFormData() {
        const data = {
            title: form.querySelector('[name="title"]')?.value || '',
            location_id: form.querySelector('[name="location_id"]')?.value || '',
            category: form.querySelector('[name="category"]')?.value || '',
            inspection_date: form.querySelector('[name="inspection_date"]')?.value || '',
            field_visit_id: form.querySelector('[name="field_visit_id"]')?.value || '',
            executive_summary: form.querySelector('[name="executive_summary"]')?.value || '',
            major_findings: form.querySelector('[name="major_findings"]')?.value || '',
            corrective_recommendations: form.querySelector('[name="corrective_recommendations"]')?.value || '',
            items: [],
            timestamp: new Date().toLocaleString()
        };

        document.querySelectorAll('.checklist-row').forEach(row => {
            data.items.push({
                item_name: row.querySelector('input[name*="[item_name]"]')?.value || '',
                status: row.querySelector('.item-status-select')?.value || 'pass',
                severity: row.querySelector('.item-severity-select')?.value || 'low',
                notes: row.querySelector('input[name*="[notes]"]')?.value || ''
            });
        });

        return data;
    }

    let saveTimeout = null;
    function queueAutoSave() {
        clearTimeout(saveTimeout);
        saveTimeout = setTimeout(() => {
            const data = collectFormData();
            if (data.title || data.executive_summary || data.major_findings) {
                localStorage.setItem(DRAFT_KEY, JSON.stringify(data));
            }
        }, 800);
    }

    if (form) {
        form.addEventListener('input', queueAutoSave);
        form.addEventListener('change', queueAutoSave);
        form.addEventListener('submit', function () {
            localStorage.removeItem(DRAFT_KEY);
        });
    }

    // Check for existing draft on page load
    try {
        const saved = localStorage.getItem(DRAFT_KEY);
        if (saved) {
            const draft = JSON.parse(saved);
            if (draft && (draft.title || draft.executive_summary)) {
                if (draftTimestamp) draftTimestamp.textContent = `(Saved on ${draft.timestamp})`;
                if (draftAlert) draftAlert.classList.remove('d-none');
            }
        }
    } catch (e) {
        console.warn('Could not read draft', e);
    }

    if (restoreDraftBtn) {
        restoreDraftBtn.addEventListener('click', function () {
            try {
                const saved = localStorage.getItem(DRAFT_KEY);
                if (!saved) return;
                const draft = JSON.parse(saved);

                if (draft.title) form.querySelector('[name="title"]').value = draft.title;
                if (draft.location_id) form.querySelector('[name="location_id"]').value = draft.location_id;
                if (draft.category) form.querySelector('[name="category"]').value = draft.category;
                if (draft.inspection_date) form.querySelector('[name="inspection_date"]').value = draft.inspection_date;
                if (draft.field_visit_id) form.querySelector('[name="field_visit_id"]').value = draft.field_visit_id;
                if (draft.executive_summary) form.querySelector('[name="executive_summary"]').value = draft.executive_summary;
                if (draft.major_findings) form.querySelector('[name="major_findings"]').value = draft.major_findings;
                if (draft.corrective_recommendations) form.querySelector('[name="corrective_recommendations"]').value = draft.corrective_recommendations;

                if (Array.isArray(draft.items) && draft.items.length > 0) {
                    container.innerHTML = '';
                    draft.items.forEach((item, idx) => {
                        renderChecklistRow(idx, item.item_name, item.status, item.severity, item.notes);
                    });
                    itemIndex = draft.items.length;
                }

                calculateAuditScore();
                if (draftAlert) draftAlert.classList.add('d-none');
            } catch (e) {
                alert('Error restoring draft: ' + e.message);
            }
        });
    }

    if (discardDraftBtn) {
        discardDraftBtn.addEventListener('click', function () {
            localStorage.removeItem(DRAFT_KEY);
            if (draftAlert) draftAlert.classList.add('d-none');
        });
    }

    calculateAuditScore();
});
</script>
@endpush
