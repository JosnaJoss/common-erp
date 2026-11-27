@extends('layouts.app')

@section('title', 'Location-Wise Stock Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Location-Wise & Department Inventory Report</h4>
        <p class="text-muted small mb-0">Multi-location stock balances segmented by warehouse, department, item, batch, and expiry date</p>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('inventory.reports.location_stock') }}" class="row g-3">
            <div class="col-md-6">
                <label class="form-label small text-muted">Filter by Stock Location / Godown</label>
                <select name="godown_id" class="form-select form-select-sm">
                    <option value="">All Locations & Stores</option>
                    @foreach($godowns as $g)
                        <option value="{{ $g->id }}" {{ $selectedGodownId == $g->id ? 'selected' : '' }}>
                            {{ $g->name }} ({{ strtoupper($g->location_type) }}) {{ $g->department ? '- Dept: ' . $g->department->name : '' }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter Location</button>
                <a href="{{ route('inventory.reports.location_stock') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
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
                        <th>Location / Warehouse</th>
                        <th>Associated Department</th>
                        <th>Material / Item</th>
                        <th>Batch Number</th>
                        <th>Expiry Date</th>
                        <th class="text-end">Current Stock Quantity</th>
                        <th class="text-end">Unit Cost</th>
                        <th class="text-end">Stock Valuation</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $b)
                        @php
                            $val = (float)$b->quantity * (float)$b->cost_price;
                        @endphp
                        <tr>
                            <td>
                                <span class="fw-bold">{{ $b->godown->name ?? '-' }}</span>
                                <div class="small text-muted">{{ strtoupper($b->godown->location_type ?? 'warehouse') }}</div>
                            </td>
                            <td>{{ $b->godown->department->name ?? 'General' }}</td>
                            <td>
                                <strong>{{ $b->item->name }}</strong>
                                <div class="small text-muted"><code>{{ $b->item->code }}</code></div>
                            </td>
                            <td><code>{{ $b->batch_no }}</code></td>
                            <td>
                                @if($b->expiry_date)
                                    {{ $b->expiry_date->format('d M Y') }}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold">{{ number_format($b->quantity, 3) }} {{ $b->item->unit->symbol ?? '' }}</td>
                            <td class="text-end">₹{{ number_format($b->cost_price, 2) }}</td>
                            <td class="text-end fw-bold text-success">₹{{ number_format($val, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">No stock balance records found for selected filter.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $batches->links() }}
        </div>
    </div>
</div>
@endsection
