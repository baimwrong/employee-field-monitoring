@extends('layouts.app')

@section('title', 'Track Inspection Reports')

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <h3 class="fw-bold text-dark mb-1">Track Inspection Reports</h3>
        <p class="text-muted mb-0" style="font-size: 0.9rem;">
            Monitor field audit findings, compliance scores, checklist evaluations, and supervisor quality approvals.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('inspections.exportCsv', request()->query()) }}" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" title="Export Current Filtered Reports to CSV">
            <i class="fa-solid fa-file-csv text-success"></i>
            <span>Export CSV</span>
        </a>
        <a href="{{ route('inspections.create') }}" class="btn btn-primary btn-sm d-flex align-items-center gap-2">
            <i class="fa-solid fa-plus"></i>
            <span>Submit Inspection Report</span>
        </a>
    </div>
</div>

<!-- Score & Statistics Summary -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card-custom p-3 d-flex align-items-center gap-3">
            <div class="bg-primary-subtle text-primary rounded-3 p-3">
                <i class="fa-solid fa-file-shield fs-4"></i>
            </div>
            <div>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Total Reports</small>
                <div class="fs-4 fw-bold text-dark">{{ $totalReports }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-custom p-3 d-flex align-items-center gap-3">
            <div class="bg-success-subtle text-success rounded-3 p-3">
                <i class="fa-solid fa-circle-check fs-4"></i>
            </div>
            <div>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Passed Audits</small>
                <div class="fs-4 fw-bold text-success">{{ $passedCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-custom p-3 d-flex align-items-center gap-3">
            <div class="bg-warning-subtle text-warning rounded-3 p-3">
                <i class="fa-solid fa-triangle-exclamation fs-4"></i>
            </div>
            <div>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Warnings / Needs Fix</small>
                <div class="fs-4 fw-bold text-warning">{{ $warningCount }}</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-custom p-3 d-flex align-items-center gap-3">
            <div class="bg-info-subtle text-info rounded-3 p-3">
                <i class="fa-solid fa-gauge-high fs-4"></i>
            </div>
            <div>
                <small class="text-muted text-uppercase fw-semibold" style="font-size: 0.7rem;">Average Compliance</small>
                <div class="fs-4 fw-bold text-primary">{{ $avgScore }}%</div>
            </div>
        </div>
    </div>
</div>

<!-- Filters & Search -->
<div class="card-custom p-3 mb-4">
    <form action="{{ route('inspections.index') }}" method="GET" class="row g-2 align-items-center">
        <div class="col-12 col-md-4">
            <div class="input-group input-group-sm">
                <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" name="search" class="form-control" placeholder="Search report #, title, location..." value="{{ $search }}">
            </div>
        </div>
        <div class="col-6 col-md-3">
            <select name="grade" class="form-select form-select-sm">
                <option value="">All Result Grades</option>
                <option value="Passed" {{ $gradeFilter === 'Passed' ? 'selected' : '' }}>Passed</option>
                <option value="Warning / Needs Improvement" {{ $gradeFilter === 'Warning / Needs Improvement' ? 'selected' : '' }}>Warning / Needs Improvement</option>
                <option value="Failed" {{ $gradeFilter === 'Failed' ? 'selected' : '' }}>Failed</option>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <select name="status" class="form-select form-select-sm">
                <option value="">All Approval Statuses</option>
                <option value="submitted" {{ $statusFilter === 'submitted' ? 'selected' : '' }}>Submitted (Pending Review)</option>
                <option value="under_review" {{ $statusFilter === 'under_review' ? 'selected' : '' }}>Under Review</option>
                <option value="approved" {{ $statusFilter === 'approved' ? 'selected' : '' }}>Approved</option>
                <option value="rejected" {{ $statusFilter === 'rejected' ? 'selected' : '' }}>Rejected</option>
            </select>
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
            @if($search || $gradeFilter || $statusFilter)
                <a href="{{ route('inspections.index') }}" class="btn btn-sm btn-light border"><i class="fa-solid fa-rotate-left"></i></a>
            @endif
        </div>
    </form>
</div>

<!-- Reports Table -->
<div class="card-custom">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
            <thead class="table-light">
                <tr>
                    <th>Report Ref</th>
                    <th>Report Title & Category</th>
                    <th>Assigned Site / Location</th>
                    <th>Field Inspector</th>
                    <th>Compliance Score</th>
                    <th>Result Grade</th>
                    <th>Approval Status</th>
                    <th class="text-end">Dossier</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reports as $rpt)
                    <tr>
                        <td>
                            <span class="fw-bold text-dark">{{ $rpt->report_number }}</span>
                            <div class="text-muted" style="font-size: 0.75rem;">{{ $rpt->inspection_date->format('M d, Y') }}</div>
                        </td>
                        <td>
                            <a href="{{ route('inspections.show', $rpt) }}" class="fw-bold text-primary text-decoration-none">
                                {{ $rpt->title }}
                            </a>
                            <div class="text-muted" style="font-size: 0.75rem;"><i class="fa-solid fa-tag me-1"></i>{{ $rpt->category }}</div>
                        </td>
                        <td>
                            <div class="fw-medium text-dark">{{ $rpt->location->name ?? 'N/A' }}</div>
                            <small class="text-muted">{{ $rpt->location->geographicalArea->name ?? 'Zone' }}</small>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $rpt->inspector->name ?? 'Auditor' }}</div>
                            <small class="text-muted">{{ $rpt->inspector->employee_code ?? '' }}</small>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="fw-bold {{ $rpt->compliance_score >= 85 ? 'text-success' : ($rpt->compliance_score >= 65 ? 'text-warning' : 'text-danger') }}">
                                    {{ number_format($rpt->compliance_score, 1) }}%
                                </span>
                            </div>
                            <div class="progress" style="height: 4px; width: 80px;">
                                <div class="progress-bar {{ $rpt->compliance_score >= 85 ? 'bg-success' : ($rpt->compliance_score >= 65 ? 'bg-warning' : 'bg-danger') }}"
                                     style="width: {{ $rpt->compliance_score }}%"></div>
                            </div>
                        </td>
                        <td>
                            @if($rpt->result_grade === 'Passed')
                                <span class="status-badge bg-success-subtle text-success border border-success-subtle">
                                    <i class="fa-solid fa-check"></i> Passed
                                </span>
                            @elseif(str_contains($rpt->result_grade, 'Warning'))
                                <span class="status-badge bg-warning-subtle text-warning border border-warning-subtle">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Warning
                                </span>
                            @else
                                <span class="status-badge bg-danger-subtle text-danger border border-danger-subtle">
                                    <i class="fa-solid fa-circle-xmark"></i> Failed
                                </span>
                            @endif
                        </td>
                        <td>
                            @if($rpt->status === 'approved')
                                <span class="badge bg-success-subtle text-success border border-success-subtle">Approved</span>
                            @elseif($rpt->status === 'under_review')
                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Under Review</span>
                            @elseif($rpt->status === 'submitted')
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Submitted</span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle">{{ ucfirst($rpt->status) }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('inspections.show', $rpt) }}" class="btn btn-sm btn-outline-primary py-1 px-2" title="Inspect Full Dossier">
                                <i class="fa-solid fa-arrow-up-right-from-square me-1"></i>View
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted py-5">
                            <i class="fa-solid fa-clipboard-check fs-2 mb-2 d-block text-secondary"></i>
                            No inspection reports found matching your criteria.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="mt-4">
    {{ $reports->links('pagination::bootstrap-5') }}
</div>
@endsection
