@extends('layouts.app')

@section('title', 'Bulk Opening Stock Entry')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Bulk Opening Stock Entry</h4>
        <p class="text-muted small mb-0">Initialize or migrate stock balances into warehouses: generates official opening stock audit entries</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('inventory.overview') }}" class="btn btn-outline-secondary">
            <i class="bi bi-box-seam me-1"></i> Stock Overview
        </a>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('inventory.opening_stock.index') }}" class="row g-3 align-items-end" id="godownFilterForm">
            <div class="col-md-5">
                <label class="form-label fw-bold">Select Target Warehouse / Godown <span class="text-danger">*</span></label>
                <select name="godown_id" class="form-select" onchange="document.getElementById('godownFilterForm').submit()">
                    @foreach($godowns as $g)
                        <option value="{{ $g->id }}" {{ $selectedGodownId == $g->id ? 'selected' : '' }}>{{ $g->name }} ({{ $g->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-7 text-md-end text-muted small">
                <i class="bi bi-info-circle me-1"></i> Enter quantities and cost rates for items to initialize their opening balance in this warehouse. Leave empty or 0 for unchanged items.
            </div>
        </form>
    </div>
</div>

<form action="{{ route('inventory.opening_stock.store') }}" method="POST">
    @csrf
    <input type="hidden" name="godown_id" value="{{ $selectedGodownId }}">

    <div class="card shadow-sm border-0">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-list-check me-1"></i> Items Stock List</h6>
            <span class="badge bg-light text-dark border">{{ count($items) }} active items</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 35%;">Item Name & Code</th>
                            <th style="width: 15%;">Unit</th>
                            <th style="width: 15%;" class="text-end">Current Stock</th>
                            <th style="width: 15%;">Opening Qty to Add</th>
                            <th style="width: 15%;">Unit Cost (₹)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($items as $idx => $item)
                            @php
                                $curQty = $item->batches->sum('quantity');
                            @endphp
                            <tr>
                                <td>{{ $idx + 1 }}</td>
                                <td>
                                    <input type="hidden" name="items[{{ $idx }}][item_id]" value="{{ $item->id }}">
                                    <div class="fw-bold">{{ $item->name }}</div>
                                    <small class="text-muted">Code: {{ $item->code }} | HSN: {{ $item->hsn_code ?? '-' }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-secondary border">{{ $item->unit->symbol ?? 'Units' }}</span>
                                </td>
                                <td class="text-end fw-semibold {{ $curQty > 0 ? 'text-success' : 'text-muted' }}">
                                    {{ number_format($curQty, 2) }}
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm" placeholder="0.00">
                                </td>
                                <td>
                                    <input type="number" step="0.01" min="0" name="items[{{ $idx }}][unit_cost]" class="form-control form-control-sm" value="{{ $item->min_price ?? 0 }}" placeholder="0.00">
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">
                                    No items found. Create items in Master Data first.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-3 text-end">
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check2-circle me-1"></i> Save Bulk Opening Stock
            </button>
        </div>
    </div>
</form>
@endsection
