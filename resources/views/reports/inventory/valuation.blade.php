@extends('layouts.app')

@section('title', 'Stock Valuation Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div>
        <h4 class="fw-bold mb-1">Stock Valuation Report</h4>
        <p class="text-muted small mb-0">Financial value of inventory by godown, item, and batch using FIFO cost valuation</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Print Report
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm border-0 mb-4 d-print-none">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.inventory.valuation') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-bold">Godown / Warehouse</label>
                <select name="godown_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Godowns & Warehouses</option>
                    @foreach($godowns as $g)
                        <option value="{{ $g->id }}" {{ $godownId == $g->id ? 'selected' : '' }}>{{ $g->name }} ({{ $g->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <a href="{{ route('reports.inventory.valuation') }}" class="btn btn-sm btn-outline-secondary">Reset Filter</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border p-4 bg-white" id="valuation-printable">
    <!-- Header -->
    <div class="text-center border-bottom pb-3 mb-4">
        <h3 class="fw-bold text-primary mb-1">{{ auth()->user()->company->name ?? 'Common ERP Enterprise' }}</h3>
        <h5 class="fw-bold text-dark mb-0">STOCK VALUATION SUMMARY</h5>
        <div class="text-muted small">Generated on {{ now()->format('d F Y H:i:s') }}</div>
    </div>

    <!-- Summary Total Card -->
    <div class="alert alert-primary d-flex justify-content-between align-items-center mb-4">
        <div>
            <div class="small text-uppercase fw-bold">Total Inventory Valuation</div>
            <h4 class="fw-bold mb-0 text-primary">₹ {{ number_format($totalValuation, 2) }}</h4>
        </div>
        <div>
            <span class="badge bg-primary px-3 py-2 fs-6">{{ $batches->count() }} Batches Audited</span>
        </div>
    </div>

    <!-- Table -->
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 35%;">Item Name & Code</th>
                    <th style="width: 20%;">Godown</th>
                    <th style="width: 15%;">Batch #</th>
                    <th style="width: 10%;" class="text-end">Quantity</th>
                    <th style="width: 10%;" class="text-end">Cost Price (₹)</th>
                    <th style="width: 10%;" class="text-end">Total Value (₹)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $batch)
                    <tr>
                        <td>
                            <div class="fw-bold">{{ $batch->item->name ?? 'Item' }}</div>
                            <small class="text-muted">Code: {{ $batch->item->code ?? '-' }} | HSN: {{ $batch->item->hsn_code ?? '-' }}</small>
                        </td>
                        <td>{{ $batch->godown->name ?? 'Main Godown' }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $batch->batch_no }}</span></td>
                        <td class="text-end fw-bold">{{ number_format($batch->quantity, 2) }} {{ $batch->item->unit->symbol ?? '' }}</td>
                        <td class="text-end">₹ {{ number_format($batch->cost_price, 2) }}</td>
                        <td class="text-end fw-bold text-primary">₹ {{ number_format($batch->quantity * $batch->cost_price, 2) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            No active stock batches found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-light fw-bold fs-6">
                <tr>
                    <td colspan="5" class="text-end">Total Stock Valuation:</td>
                    <td class="text-end text-primary">₹ {{ number_format($totalValuation, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection

