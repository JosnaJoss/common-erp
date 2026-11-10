@extends('layouts.app')

@section('title', 'Financial Years')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Financial Years</h4>
        <p class="text-muted small mb-0">Manage accounting calendar years, books closing, and financial period locks</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newFyModal">
        <i class="bi bi-calendar-plus me-1"></i> New Financial Year
    </button>
</div>

<!-- Years Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Financial Year Title</th>
                        <th>Start Date</th>
                        <th>End Date</th>
                        <th>Status</th>
                        <th>Lock Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($years as $fy)
                        <tr>
                            <td><strong class="text-primary">{{ $fy->title }}</strong></td>
                            <td>{{ \Carbon\Carbon::parse($fy->start_date)->format('d M Y') }}</td>
                            <td>{{ \Carbon\Carbon::parse($fy->end_date)->format('d M Y') }}</td>
                            <td>
                                @php
                                    $today = now();
                                    $isCurrent = $today->between($fy->start_date, $fy->end_date);
                                @endphp
                                @if($isCurrent)
                                    <span class="badge bg-success">Current Active Period</span>
                                @else
                                    <span class="badge bg-secondary">Other Period</span>
                                @endif
                            </td>
                            <td>
                                @if($fy->is_closed)
                                    <span class="badge bg-danger"><i class="bi bi-lock-fill me-1"></i> Books Closed</span>
                                @else
                                    <span class="badge bg-success"><i class="bi bi-unlock me-1"></i> Open for Postings</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                <i class="bi bi-calendar-x fs-2 d-block mb-2"></i> No financial years configured yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- New FY Modal -->
<div class="modal fade" id="newFyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('accounts.financial_years.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-calendar-plus me-2 text-primary"></i> Create Financial Year</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Financial Year Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" placeholder="e.g. FY 2024-2025" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Period Start Date <span class="text-danger">*</span></label>
                        <input type="date" name="start_date" class="form-control" value="{{ date('Y-04-01') }}" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Period End Date <span class="text-danger">*</span></label>
                        <input type="date" name="end_date" class="form-control" value="{{ date('Y-03-31', strtotime('+1 year')) }}" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Create Period</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

