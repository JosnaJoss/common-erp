@extends('layouts.app')

@section('title', 'Internal Stock Request Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Internal Stock Request Report</h4>
        <p class="text-muted small mb-0">Audit departmental material requests, approval status, and fulfillment ratios</p>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('inventory.reports.requests') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small text-muted">Department</label>
                <select name="from_department_id" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" {{ request('from_department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="requested" {{ request('status') === 'requested' ? 'selected' : '' }}>Requested</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="partially_approved" {{ request('status') === 'partially_approved' ? 'selected' : '' }}>Partially Approved</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">From Date</label>
                <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
            </div>
            <div class="col-md-2">
                <label class="form-label small text-muted">To Date</label>
                <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
            </div>
            <div class="col-md-2 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                <a href="{{ route('inventory.reports.requests') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Request #</th>
                        <th>Date</th>
                        <th>Department</th>
                        <th>Source Location</th>
                        <th>Destination Location</th>
                        <th>Item</th>
                        <th class="text-end">Requested</th>
                        <th class="text-end">Approved</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($requests as $req)
                        @foreach($req->items as $idx => $item)
                            <tr>
                                @if($idx === 0)
                                    <td rowspan="{{ $req->items->count() }}" class="fw-bold align-top">
                                        <a href="{{ route('inventory.requests.show', $req->id) }}">{{ $req->request_no }}</a>
                                    </td>
                                    <td rowspan="{{ $req->items->count() }}" class="align-top">{{ \Carbon\Carbon::parse($req->request_date)->format('d M Y') }}</td>
                                    <td rowspan="{{ $req->items->count() }}" class="align-top fw-bold">{{ $req->fromDepartment->name ?? '-' }}</td>
                                    <td rowspan="{{ $req->items->count() }}" class="align-top">{{ $req->sourceGodown->name ?? '-' }}</td>
                                    <td rowspan="{{ $req->items->count() }}" class="align-top">{{ $req->fromGodown->name ?? 'Central Store' }}</td>
                                @endif
                                <td>{{ $item->item->name ?? '-' }}</td>
                                <td class="text-end">{{ number_format($item->requested_qty, 2) }} {{ $item->item->unit->symbol ?? '' }}</td>
                                <td class="text-end fw-bold text-success">{{ number_format($item->approved_qty, 2) }}</td>
                                @if($idx === 0)
                                    <td rowspan="{{ $req->items->count() }}" class="align-top">
                                        <span class="badge bg-light text-dark border">{{ ucfirst($req->status) }}</span>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No request records found.</td>
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
