@extends('layouts.app')

@section('title', 'Stock Discrepancy & Investigation Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Stock Discrepancies & Transit Losses</h4>
        <p class="text-muted small mb-0">Investigate, reconcile, and approve write-offs for shortages, damaged goods, and delivery variances</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('inventory.reports.in_transit') }}" class="btn btn-outline-info">
            <i class="bi bi-truck me-1"></i> In-Transit Stock Report
        </a>
        <a href="{{ route('inventory.transfers') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Stock Transfers
        </a>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<!-- Filters -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('inventory.discrepancies.index') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label small text-muted">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="reported" {{ request('status') === 'reported' ? 'selected' : '' }}>Reported (New)</option>
                    <option value="under_investigation" {{ request('status') === 'under_investigation' ? 'selected' : '' }}>Under Investigation</option>
                    <option value="pending_approval" {{ request('status') === 'pending_approval' ? 'selected' : '' }}>Pending Write-Off Approval</option>
                    <option value="resolved" {{ request('status') === 'resolved' ? 'selected' : '' }}>Resolved</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted">Discrepancy Type</label>
                <select name="discrepancy_type" class="form-select form-select-sm">
                    <option value="">All Types</option>
                    <option value="shortage" {{ request('discrepancy_type') === 'shortage' ? 'selected' : '' }}>Shortage (Missing)</option>
                    <option value="damaged_goods" {{ request('discrepancy_type') === 'damaged_goods' ? 'selected' : '' }}>Damaged Goods</option>
                    <option value="quality_rejection" {{ request('discrepancy_type') === 'quality_rejection' ? 'selected' : '' }}>Quality Rejection</option>
                    <option value="wrong_item" {{ request('discrepancy_type') === 'wrong_item' ? 'selected' : '' }}>Wrong Item</option>
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i> Filter</button>
                <a href="{{ route('inventory.discrepancies.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Discrepancy List -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID / Date</th>
                        <th>Transfer / Request</th>
                        <th>Material & Batch</th>
                        <th>Source -> Dest</th>
                        <th class="text-end">Discrepancy Qty</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($discrepancies as $disc)
                        <tr>
                            <td>
                                <a href="{{ route('inventory.discrepancies.show', $disc->id) }}" class="fw-bold text-decoration-none">
                                    #{{ $disc->id }}
                                </a>
                                <div class="text-muted small">{{ $disc->created_at->format('d M Y H:i') }}</div>
                            </td>
                            <td>
                                <div><a href="{{ route('inventory.transfers') }}">{{ $disc->transfer->transfer_no ?? '-' }}</a></div>
                                @if($disc->request)
                                    <small class="text-muted">Req: {{ $disc->request->request_no }}</small>
                                @endif
                            </td>
                            <td>
                                <span class="fw-bold">{{ $disc->item->name ?? 'Item' }}</span>
                                <div class="small text-muted">Batch: {{ $disc->batch->batch_no ?? 'DEFAULT' }}</div>
                            </td>
                            <td>
                                <div class="small">
                                    <div><i class="bi bi-box-arrow-up text-danger me-1"></i> {{ $disc->sourceGodown->name ?? 'Source' }}</div>
                                    <div><i class="bi bi-box-arrow-down text-success me-1"></i> {{ $disc->destGodown->name ?? 'Dest' }}</div>
                                </div>
                            </td>
                            <td class="text-end">
                                <span class="fw-bold text-danger">{{ number_format($disc->discrepancy_qty, 3) }}</span>
                                <div class="small text-muted">{{ $disc->item->unit->symbol ?? '' }}</div>
                            </td>
                            <td>
                                @switch($disc->discrepancy_type)
                                    @case('damaged_goods')
                                        <span class="badge bg-danger">Damaged Goods</span>
                                        @break
                                    @case('shortage')
                                        <span class="badge bg-warning text-dark">Shortage</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">{{ ucwords(str_replace('_', ' ', $disc->discrepancy_type)) }}</span>
                                @endswitch
                            </td>
                            <td>
                                @switch($disc->status)
                                    @case('reported')
                                        <span class="badge bg-warning text-dark"><i class="bi bi-exclamation-triangle me-1"></i>Reported</span>
                                        @break
                                    @case('under_investigation')
                                        <span class="badge bg-info text-dark"><i class="bi bi-search me-1"></i>Under Investigation</span>
                                        @break
                                    @case('pending_approval')
                                        <span class="badge bg-primary"><i class="bi bi-shield-check me-1"></i>Pending Approval</span>
                                        @break
                                    @case('resolved')
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Resolved</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">{{ ucfirst($disc->status) }}</span>
                                @endswitch
                            </td>
                            <td class="text-end">
                                <a href="{{ route('inventory.discrepancies.show', $disc->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i> Investigate
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-check-circle text-success fs-2 d-block mb-2"></i>
                                No discrepancies recorded. All transfers reconciled cleanly.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $discrepancies->links() }}
        </div>
    </div>
</div>
@endsection
