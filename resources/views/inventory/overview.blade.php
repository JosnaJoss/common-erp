@extends('layouts.app')

@section('title', 'Inventory & Stock Batches')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Inventory & Stock Batches</h4>
        <p class="text-muted small mb-0">Live stock valuation, godown balances, and batch tracking with FIFO</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('inventory.inward.create') }}" class="btn btn-primary">
            <i class="bi bi-box-arrow-in-down me-1"></i> Stock Inward
        </a>
        <a href="{{ route('inventory.outward.create') }}" class="btn btn-outline-danger">
            <i class="bi bi-box-arrow-up-right me-1"></i> Stock Outward
        </a>
        <a href="{{ route('inventory.transfers') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left-right me-1"></i> Transfers
        </a>
    </div>
</div>

<!-- Stock Overview Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm bg-primary text-white">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-white-50 small text-uppercase fw-bold">Total Stock Valuation</div>
                        <h3 class="fw-bold mb-0 mt-1">₹ {{ number_format($totalValuation, 2) }}</h3>
                    </div>
                    <i class="bi bi-currency-rupee fs-1 opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold">Active Stock Batches</div>
                        <h3 class="fw-bold mb-0 mt-1">{{ $batches->total() }}</h3>
                    </div>
                    <i class="bi bi-boxes fs-1 text-primary"></i>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <div class="text-muted small text-uppercase fw-bold">Total Godowns</div>
                        <h3 class="fw-bold mb-0 mt-1">{{ $godowns->count() }}</h3>
                    </div>
                    <i class="bi bi-building fs-1 text-success"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('inventory.overview') }}" class="row g-3 align-items-center">
            <div class="col-md-4">
                <label class="form-label small fw-bold">Filter by Godown / Warehouse</label>
                <select name="godown_id" class="form-select" onchange="this.form.submit()">
                    <option value="">All Warehouses & Godowns</option>
                    @foreach($godowns as $g)
                        <option value="{{ $g->id }}" {{ $godownId == $g->id ? 'selected' : '' }}>{{ $g->name }} ({{ $g->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-8 text-end pt-4">
                <a href="{{ route('inventory.overview') }}" class="btn btn-outline-secondary me-2">Reset</a>
                <a href="{{ route('inventory.movements') }}" class="btn btn-outline-info me-2"><i class="bi bi-journal-text me-1"></i> Stock Ledger / Movements</a>
                <a href="{{ route('inventory.alerts') }}" class="btn btn-outline-warning"><i class="bi bi-exclamation-triangle me-1"></i> Reorder & Expiry Alerts</a>
            </div>
        </form>
    </div>
</div>

<!-- Batches Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Item Details</th>
                        <th>Type</th>
                        <th>Godown / Warehouse</th>
                        <th>Batch Number</th>
                        <th class="text-end">Current Quantity</th>
                        <th class="text-end">Cost Price</th>
                        <th class="text-end">Batch Value</th>
                        <th>Expiry Date</th>
                        <th class="text-center">Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($batches as $batch)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">{{ $batch->item->name ?? 'Unknown' }}</div>
                                <small class="text-muted">Code: {{ $batch->item->code ?? 'N/A' }} | HSN: {{ $batch->item->hsn_code ?? '-' }}</small>
                            </td>
                            <td>
                                <span class="badge bg-secondary">{{ ucwords(str_replace('_', ' ', $batch->item->item_type ?? 'sales')) }}</span>
                            </td>
                            <td>
                                <i class="bi bi-building me-1 text-muted"></i>
                                {{ $batch->godown->name ?? 'Main Godown' }}
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $batch->batch_no }}</span>
                            </td>
                            <td class="text-end fw-bold {{ $batch->quantity <= 0 ? 'text-danger' : 'text-success' }}">
                                {{ number_format($batch->quantity, 2) }} {{ $batch->item->unit->symbol ?? 'Units' }}
                            </td>
                            <td class="text-end">
                                ₹ {{ number_format($batch->cost_price, 2) }}
                            </td>
                            <td class="text-end fw-semibold">
                                ₹ {{ number_format($batch->quantity * $batch->cost_price, 2) }}
                            </td>
                            <td>
                                @if($batch->expiry_date)
                                    @php $exp = \Carbon\Carbon::parse($batch->expiry_date); @endphp
                                    <span class="{{ $exp->isPast() ? 'text-danger fw-bold' : ($exp->diffInDays(now()) <= 30 ? 'text-warning fw-bold' : 'text-muted') }}">
                                        {{ $exp->format('d M Y') }}
                                        @if($exp->isPast()) (Expired) @endif
                                    </span>
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td class="text-center">
                                @if($batch->quantity <= 0)
                                    <span class="badge bg-danger">Out of Stock</span>
                                @elseif($batch->item && $batch->quantity <= $batch->item->reorder_quantity)
                                    <span class="badge bg-warning text-dark">Low Stock</span>
                                @else
                                    <span class="badge bg-success">Adequate</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i> No stock records found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($batches->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $batches->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection

