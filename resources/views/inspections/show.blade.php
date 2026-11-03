@extends('layouts.app')

@section('title', 'Inspection Dossier: ' . $inspection->report_number)

@section('content')
<div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <span class="badge bg-secondary-subtle text-secondary border">{{ $inspection->report_number }}</span>
            <span class="badge bg-info-subtle text-info border">{{ $inspection->category }}</span>
            @if($inspection->status === 'approved')
                <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="fa-solid fa-check me-1"></i>Approved</span>
            @elseif($inspection->status === 'under_review')
                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Under Review</span>
            @elseif($inspection->status === 'rejected')
                <span class="badge bg-danger-subtle text-danger border border-danger-subtle">Rejected</span>
            @else
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Submitted</span>
            @endif
        </div>
        <h3 class="fw-bold text-dark mb-0">{{ $inspection->title }}</h3>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="window.print()">
            <i class="fa-solid fa-print me-1"></i>Print Report
        </button>
        <a href="{{ route('inspections.index') }}" class="btn btn-light border btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i>Back
        </a>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Report Contents -->
    <div class="col-12 col-lg-8">
        <!-- Key Findings & Executive Summary -->
        <div class="card-custom p-4 mb-4">
            <h5 class="fw-bold text-dark mb-3"><i class="fa-solid fa-file-lines text-primary me-2"></i>Executive Summary</h5>
            <p class="text-secondary lh-lg mb-4" style="font-size: 0.95rem;">
                {{ $inspection->executive_summary }}
            </p>

            @if($inspection->major_findings)
                <div class="p-3 bg-warning-subtle bg-opacity-40 rounded-3 border border-warning-subtle mb-3">
                    <h6 class="fw-bold text-warning-emphasis mb-2"><i class="fa-solid fa-triangle-exclamation me-2"></i>Key Findings & Non-Compliances</h6>
                    <p class="text-dark small mb-0 lh-base">{{ $inspection->major_findings }}</p>
                </div>
            @endif

            @if($inspection->corrective_recommendations)
                <div class="p-3 bg-info-subtle bg-opacity-30 rounded-3 border border-info-subtle">
                    <h6 class="fw-bold text-info-emphasis mb-2"><i class="fa-solid fa-wrench me-2"></i>Corrective Actions & Remediation Plan</h6>
                    <p class="text-dark small mb-0 lh-base">{{ $inspection->corrective_recommendations }}</p>
                </div>
            @endif
        </div>

        <!-- Detailed Audit Checklist Items -->
        <div class="card-custom overflow-hidden mb-4">
            <div class="card-header bg-white border-bottom p-3 d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="fa-solid fa-list-check text-success"></i>
                    <h6 class="fw-bold mb-0">Checklist Items Evaluation ({{ $inspection->items->count() }})</h6>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size: 0.85rem;">
                    <thead class="table-light">
                        <tr>
                            <th>Checklist Item</th>
                            <th>Category</th>
                            <th>Status</th>
                            <th>Severity</th>
                            <th>Inspector Observations</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inspection->items as $item)
                            <tr>
                                <td class="fw-bold text-dark">{{ $item->item_name }}</td>
                                <td><span class="badge bg-light text-muted border">{{ $item->category }}</span></td>
                                <td>
                                    @if($item->status === 'pass')
                                        <span class="badge bg-success-subtle text-success border border-success-subtle">
                                            <i class="fa-solid fa-check me-1"></i>Pass
                                        </span>
                                    @elseif($item->status === 'fail')
                                        <span class="badge bg-danger-subtle text-danger border border-danger-subtle">
                                            <i class="fa-solid fa-xmark me-1"></i>Fail
                                        </span>
                                    @else
                                        <span class="badge bg-secondary-subtle text-secondary">N/A</span>
                                    @endif
                                </td>
                                <td>
                                    @if($item->severity === 'critical')
                                        <span class="badge bg-danger text-white">Critical</span>
                                    @elseif($item->severity === 'high')
                                        <span class="badge bg-warning text-dark">High</span>
                                    @elseif($item->severity === 'medium')
                                        <span class="badge bg-info-subtle text-info border">Medium</span>
                                    @else
                                        <span class="badge bg-light text-muted border">Low</span>
                                    @endif
                                </td>
                                <td class="text-secondary">{{ $item->notes ?? '-' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">No checklist items recorded for this report.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Right Column: Audit Score & Supervisor Review -->
    <div class="col-12 col-lg-4">
        <!-- Audit Scorecard -->
        <div class="card-custom p-4 text-center mb-4">
            <h6 class="text-muted text-uppercase fw-semibold mb-2" style="font-size: 0.75rem;">Overall Compliance Score</h6>
            <div class="display-4 fw-bold {{ $inspection->compliance_score >= 85 ? 'text-success' : ($inspection->compliance_score >= 65 ? 'text-warning' : 'text-danger') }} mb-1">
                {{ number_format($inspection->compliance_score, 1) }}%
            </div>
            <div class="mb-3">
                @if($inspection->result_grade === 'Passed')
                    <span class="badge bg-success-subtle text-success fs-6 border border-success-subtle px-3 py-1">
                        <i class="fa-solid fa-shield-check me-1"></i>Passed Compliance
                    </span>
                @elseif(str_contains($inspection->result_grade, 'Warning'))
                    <span class="badge bg-warning-subtle text-warning fs-6 border border-warning-subtle px-3 py-1">
                        <i class="fa-solid fa-triangle-exclamation me-1"></i>Needs Improvement
                    </span>
                @else
                    <span class="badge bg-danger-subtle text-danger fs-6 border border-danger-subtle px-3 py-1">
                        <i class="fa-solid fa-circle-xmark me-1"></i>Failed
                    </span>
                @endif
            </div>

            <div class="p-3 bg-light rounded-3 border text-start small">
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Target Site:</span>
                    <span class="fw-bold text-dark">{{ $inspection->location->name ?? 'Site' }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Geographical Area:</span>
                    <span class="text-primary fw-semibold">{{ $inspection->location->geographicalArea->name ?? 'Zone' }}</span>
                </div>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Auditor / Officer:</span>
                    <span class="fw-semibold text-dark">{{ $inspection->inspector->name ?? 'Field Auditor' }}</span>
                </div>
                <div class="d-flex justify-content-between">
                    <span class="text-muted">Inspection Date:</span>
                    <span>{{ $inspection->inspection_date->format('M d, Y') }}</span>
                </div>
            </div>
        </div>

        <!-- Supervisor Review Section -->
        <div class="card-custom p-4">
            <h6 class="fw-bold text-dark mb-3"><i class="fa-solid fa-signature text-primary me-2"></i>Supervisor Review & Sign-Off</h6>

            @if($inspection->reviewed_by)
                <div class="p-3 bg-light rounded-3 border mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="fw-bold text-dark">{{ $inspection->reviewer->name ?? 'Supervisor' }}</span>
                        <small class="text-muted">{{ $inspection->reviewed_at ? $inspection->reviewed_at->format('M d, Y H:i') : '' }}</small>
                    </div>
                    <p class="text-muted small mb-0">{{ $inspection->reviewer_remarks }}</p>
                </div>
            @endif

            <!-- Review / Update Action Form -->
            <form action="{{ route('inspections.review', $inspection) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label fw-semibold small">Set Approval Status</label>
                    <select name="status" class="form-select form-select-sm" required>
                        <option value="approved" {{ $inspection->status === 'approved' ? 'selected' : '' }}>Approve Report</option>
                        <option value="under_review" {{ $inspection->status === 'under_review' ? 'selected' : '' }}>Mark Under Review</option>
                        <option value="rejected" {{ $inspection->status === 'rejected' ? 'selected' : '' }}>Reject / Request Re-audit</option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold small">Supervisor Review Remarks</label>
                    <textarea name="reviewer_remarks" class="form-control form-control-sm" rows="3" placeholder="Enter quality remarks, follow-up instructions..." required>{{ $inspection->reviewer_remarks }}</textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-sm w-100">
                    <i class="fa-solid fa-stamp me-1"></i>Submit Review Decision
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
