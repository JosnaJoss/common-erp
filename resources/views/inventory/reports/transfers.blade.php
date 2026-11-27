@extends('layouts.app')

@section('title', 'Stock Transfer Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Stock Transfer & Dispatch Report</h4>
        <p class="text-muted small mb-0">Trace inter-location inventory movements, batch identities, and shipment fulfillment</p>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('inventory.reports.transfers') }}" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small text-muted">Source Godown</label>
                <select name="source_godown_id" class="form-select form-select-sm">
                    <option value="">All Source Godowns</option>
                    @foreach($godowns as $g)
                        <option value="{{ $g->id }}" {{ request('source_godown_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Destination Godown</label>
                <select name="dest_godown_id" class="form-select form-select-sm">
                    <option value="">All Destination Godowns</option>
                    @foreach($godowns as $g)
                        <option value="{{ $g->id }}" {{ request('dest_godown_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small text-muted">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="in_transit" {{ request('status') === 'in_transit' ? 'selected' : '' }}>In-Transit</option>
                    <option value="partially_received" {{ request('status') === 'partially_received' ? 'selected' : '' }}>Partially Received</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                <a href="{{ route('inventory.reports.transfers') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
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
                        <th>Transfer #</th>
                        <th>Request Ref</th>
                        <th>Date</th>
                        <th>Source -> Destination</th>
                        <th>Item & Batch</th>
                        <th class="text-end">Dispatched</th>
                        <th class="text-end">Received</th>
                        <th class="text-end">Pending</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transfers as $trf)
                        @foreach($trf->items as $idx => $item)
                            <tr>
                                @if($idx === 0)
                                    <td rowspan="{{ $trf->items->count() }}" class="fw-bold align-top">
                                        {{ $trf->transfer_no }}
                                    </td>
                                    <td rowspan="{{ $trf->items->count() }}" class="align-top">
                                        {{ $trf->request->request_no ?? 'Direct' }}
                                    </td>
                                    <td rowspan="{{ $trf->items->count() }}" class="align-top">
                                        {{ \Carbon\Carbon::parse($trf->transfer_date)->format('d M Y') }}
                                    </td>
                                    <td rowspan="{{ $trf->items->count() }}" class="align-top">
                                        <div><i class="bi bi-box-arrow-up text-danger me-1"></i>{{ $trf->sourceGodown->name ?? '-' }}</div>
                                        <div><i class="bi bi-box-arrow-down text-success me-1"></i>{{ $trf->destGodown->name ?? '-' }}</div>
                                    </td>
                                @endif
                                <td>
                                    <strong>{{ $item->item->name }}</strong>
                                    <div class="small text-muted">Batch: <code>{{ $item->batch->batch_no ?? 'DEFAULT' }}</code></div>
                                </td>
                                <td class="text-end">{{ number_format($item->dispatched_qty, 2) }}</td>
                                <td class="text-end text-success">{{ number_format($item->received_qty, 2) }}</td>
                                <td class="text-end text-danger">{{ number_format($item->in_transit_qty, 2) }}</td>
                                @if($idx === 0)
                                    <td rowspan="{{ $trf->items->count() }}" class="align-top">
                                        <span class="badge bg-light text-dark border">{{ ucfirst($trf->status) }}</span>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No stock transfer records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $transfers->links() }}
        </div>
    </div>
</div>
@endsection
