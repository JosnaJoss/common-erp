@extends('layouts.app')

@section('title', 'Reorder & Low Stock Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div>
        <h4 class="fw-bold mb-1">Stock Reorder & Replenishment Report</h4>
        <p class="text-muted small mb-0">Items whose current warehouse balance has fallen below minimum reorder thresholds</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Print Report
        </button>
        <a href="{{ route('purchase.orders.create') }}" class="btn btn-primary">
            <i class="bi bi-cart-plus me-1"></i> Create Purchase Order
        </a>
    </div>
</div>

<div class="card shadow-sm border p-4 bg-white" id="reorder-printable">
    <!-- Header -->
    <div class="text-center border-bottom pb-3 mb-4">
        <h3 class="fw-bold text-primary mb-1">{{ auth()->user()->company->name ?? 'Common ERP Enterprise' }}</h3>
        <h5 class="fw-bold text-dark mb-0">ITEMS BELOW REORDER POINT</h5>
        <div class="text-muted small">Report generated on {{ now()->format('d F Y H:i:s') }}</div>
    </div>

    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 35%;">Item Details</th>
                    <th style="width: 15%;">Category</th>
                    <th style="width: 15%;" class="text-end">Current Stock</th>
                    <th style="width: 15%;" class="text-end">Reorder Level</th>
                    <th style="width: 15%;" class="text-end">Suggested Reorder Qty</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $idx => $item)
                    @php
                        $currStock = $item->totalStockQuantity();
                        $reorderLevel = (float)$item->reorder_quantity;
                        $suggested = max(0, ($reorderLevel * 2) - $currStock);
                    @endphp
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <div class="fw-bold">{{ $item->name }}</div>
                            <small class="text-muted">Code: {{ $item->code }} | HSN: {{ $item->hsn_code ?? '-' }}</small>
                        </td>
                        <td>{{ $item->category->name ?? 'General' }}</td>
                        <td class="text-end fw-bold text-danger">
                            {{ number_format($currStock, 2) }} {{ $item->unit->symbol ?? '' }}
                        </td>
                        <td class="text-end fw-semibold">
                            {{ number_format($reorderLevel, 2) }} {{ $item->unit->symbol ?? '' }}
                        </td>
                        <td class="text-end fw-bold text-primary">
                            {{ number_format($suggested, 2) }} {{ $item->unit->symbol ?? '' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-success">
                            <i class="bi bi-check-circle fs-3 d-block mb-2"></i> All items currently maintain stock levels above reorder thresholds.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection

