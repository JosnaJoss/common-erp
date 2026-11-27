@extends('layouts.app')

@section('title', 'Internal Department Stock Requests')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Internal Department Stock Requests</h4>
        <p class="text-muted small mb-0">Requisition raw materials, packaging, and items across departments and warehouses</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('inventory.configuration') }}" class="btn btn-outline-secondary">
            <i class="bi bi-gear me-1"></i> Stock Mode Config
        </a>
        <a href="{{ route('inventory.requests.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> New Stock Request
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
        <form method="GET" action="{{ route('inventory.requests.index') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small text-muted">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="requested" {{ request('status') === 'requested' ? 'selected' : '' }}>Requested (Pending Review)</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="partially_approved" {{ request('status') === 'partially_approved' ? 'selected' : '' }}>Partially Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed / Fulfilled</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Department</label>
                <select name="from_department_id" class="form-select form-select-sm">
                    <option value="">All Requesting Departments</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" {{ request('from_department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Priority</label>
                <select name="priority" class="form-select form-select-sm">
                    <option value="">All Priorities</option>
                    <option value="low" {{ request('priority') === 'low' ? 'selected' : '' }}>Low</option>
                    <option value="medium" {{ request('priority') === 'medium' ? 'selected' : '' }}>Medium</option>
                    <option value="high" {{ request('priority') === 'high' ? 'selected' : '' }}>High</option>
                    <option value="urgent" {{ request('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-funnel me-1"></i> Filter</button>
                <a href="{{ route('inventory.requests.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Requests Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Request #</th>
                        <th>Date / Priority</th>
                        <th>Requesting Department</th>
                        <th>Source Godown</th>
                        <th>Items Summary</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        <tr>
                            <td>
                                <a href="{{ route('inventory.requests.show', $req->id) }}" class="fw-bold text-decoration-none">
                                    {{ $req->request_no }}
                                </a>
                                <div class="text-muted small">By: {{ $req->creator->name ?? 'User' }}</div>
                            </td>
                            <td>
                                <div>{{ \Carbon\Carbon::parse($req->request_date)->format('d M Y') }}</div>
                                @switch($req->priority)
                                    @case('urgent')
                                        <span class="badge bg-danger">Urgent</span>
                                        @break
                                    @case('high')
                                        <span class="badge bg-warning text-dark">High</span>
                                        @break
                                    @case('low')
                                        <span class="badge bg-secondary">Low</span>
                                        @break
                                    @default
                                        <span class="badge bg-info text-dark">Medium</span>
                                @endswitch
                            </td>
                            <td>
                                <div class="fw-bold">{{ $req->fromDepartment->name ?? 'N/A' }}</div>
                                @if($req->fromGodown)
                                    <small class="text-muted"><i class="bi bi-geo-alt me-1"></i>{{ $req->fromGodown->name }}</small>
                                @else
                                    <small class="text-muted"><i class="bi bi-building me-1"></i>Direct Central Delivery</small>
                                @endif
                            </td>
                            <td>
                                <div><i class="bi bi-box-arrow-up text-primary me-1"></i>{{ $req->sourceGodown->name ?? 'Central Warehouse' }}</div>
                            </td>
                            <td>
                                <div class="small">
                                    @foreach($req->items->take(2) as $item)
                                        <div><strong>{{ $item->item->name ?? 'Item' }}:</strong> {{ number_format($item->requested_qty, 2) }} {{ $item->item->unit->symbol ?? '' }}</div>
                                    @endforeach
                                    @if($req->items->count() > 2)
                                        <span class="text-muted">+{{ $req->items->count() - 2 }} more items</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @switch($req->status)
                                    @case('requested')
                                        <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i>Requested</span>
                                        @break
                                    @case('approved')
                                        <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Approved</span>
                                        @break
                                    @case('partially_approved')
                                        <span class="badge bg-info text-dark"><i class="bi bi-pie-chart me-1"></i>Partially Approved</span>
                                        @break
                                    @case('rejected')
                                        <span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Rejected</span>
                                        @break
                                    @case('completed')
                                        <span class="badge bg-primary"><i class="bi bi-check-all me-1"></i>Completed</span>
                                        @break
                                    @default
                                        <span class="badge bg-secondary">{{ ucfirst($req->status) }}</span>
                                @endswitch
                            </td>
                            <td class="text-end">
                                <a href="{{ route('inventory.requests.show', $req->id) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i> View
                                </a>
                                @if(in_array($req->status, ['approved', 'partially_approved']))
                                    <a href="{{ route('inventory.requests.dispatch.create', $req->id) }}" class="btn btn-sm btn-success">
                                        <i class="bi bi-truck me-1"></i> Dispatch
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No internal stock requests found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $requests->links() }}
        </div>
    </div>
</div>
@endsection
