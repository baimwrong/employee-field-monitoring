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

<form action="{{ route('inspections.store') }}" method="POST">
    @csrf
    <div class="row g-4">
        <!-- Left: General Information -->
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
                        <select name="user_id" class="form-select" required>
                            <option value="">-- Choose Inspector --</option>
                            @foreach($inspectors as $ins)
                                <option value="{{ $ins->id }}" {{ ($selectedVisit && $selectedVisit->user_id == $ins->id) ? 'selected' : '' }}>
                                    {{ $ins->name }} ({{ $ins->employee_code }})
                                </option>
                            @endforeach
                        </select>
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
        </div>

        <!-- Right: Compliance Score & Dynamic Checklist Items -->
        <div class="col-12 col-lg-5">
            <!-- Score Card -->
            <div class="card-custom p-4 mb-4">
                <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-gauge-high text-warning me-2"></i>Audit Scoring</h5>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Compliance Score (0 - 100%) <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="number" step="0.1" name="compliance_score" id="scoreInput" class="form-control" value="95.0" min="0" max="100" required>
                        <span class="input-group-text">%</span>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label fw-semibold">Result Grade <span class="text-danger">*</span></label>
                    <select name="result_grade" id="gradeSelect" class="form-select" required>
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
                                <select name="items[0][status]" class="form-select form-select-sm">
                                    <option value="pass" selected>Pass</option>
                                    <option value="fail">Fail</option>
                                    <option value="na">N/A</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select name="items[0][severity]" class="form-select form-select-sm">
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
                                <select name="items[1][status]" class="form-select form-select-sm">
                                    <option value="pass" selected>Pass</option>
                                    <option value="fail">Fail</option>
                                    <option value="na">N/A</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <select name="items[1][severity]" class="form-select form-select-sm">
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
                        <i class="fa-solid fa-cloud-arrow-up me-2"></i>Submit Inspection Report
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

    addBtn.addEventListener('click', function () {
        const div = document.createElement('div');
        div.className = 'p-3 bg-light rounded-3 border checklist-row';
        div.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-2">
                <span class="fw-bold small text-muted">Item #${itemIndex + 1}</span>
                <button type="button" class="btn btn-sm btn-link text-danger p-0 remove-item-btn"><i class="fa-solid fa-trash"></i></button>
            </div>
            <input type="text" name="items[${itemIndex}][item_name]" class="form-control form-control-sm mb-2" placeholder="Item Name e.g. HVAC Air Quality" required>
            <div class="row g-2 mb-2">
                <div class="col-6">
                    <select name="items[${itemIndex}][status]" class="form-select form-select-sm">
                        <option value="pass" selected>Pass</option>
                        <option value="fail">Fail</option>
                        <option value="na">N/A</option>
                    </select>
                </div>
                <div class="col-6">
                    <select name="items[${itemIndex}][severity]" class="form-select form-select-sm">
                        <option value="low" selected>Low Severity</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="critical">Critical</option>
                    </select>
                </div>
            </div>
            <input type="text" name="items[${itemIndex}][notes]" class="form-control form-control-sm" placeholder="Observation notes...">
        `;
        container.appendChild(div);
        itemIndex++;
    });

    container.addEventListener('click', function (e) {
        if (e.target.closest('.remove-item-btn')) {
            const row = e.target.closest('.checklist-row');
            if (document.querySelectorAll('.checklist-row').length > 1) {
                row.remove();
            } else {
                alert('At least one checklist item is required.');
            }
        }
    });

    // Auto-select grade when score changes
    document.getElementById('scoreInput').addEventListener('input', function () {
        const val = parseFloat(this.value);
        const grade = document.getElementById('gradeSelect');
        if (val >= 80) grade.value = 'Passed';
        else if (val >= 60) grade.value = 'Warning / Needs Improvement';
        else grade.value = 'Failed';
    });
});
</script>
@endpush
