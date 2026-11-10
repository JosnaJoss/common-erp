@extends('layouts.app')

@section('title', 'Reorder & Expiry Alerts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Stock Alerts & Monitoring</h4>
        <p class="text-muted small mb-0">Live warnings for low stock below reorder levels and batches nearing expiration within 30 days</p>
    </div>
    <a href="{{ route('inventory.overview') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Stock Overview
    </a>
</div>

<div class="row g-4">
    <!-- Low Stock Items Alert -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-danger bg-opacity-10 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-danger">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> Low Stock Alerts ({{ $lowStock->count() }})
                    </h6>
                    <span class="badge bg-danger">Critical</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item</th>
                                <th class="text-end">Current Stock</th>
                                <th class="text-end">Reorder Point</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lowStock as $item)
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $item->name }}</div>
                                        <small class="text-muted">Code: {{ $item->code }}</small>
                                    </td>
                                    <td class="text-end fw-bold text-danger">
                                        {{ number_format($item->totalStockQuantity(), 2) }} {{ $item->unit->symbol ?? '' }}
                                    </td>
                                    <td class="text-end fw-semibold">
                                        {{ number_format($item->reorder_quantity, 2) }} {{ $item->unit->symbol ?? '' }}
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('purchase.orders.create') }}" class="btn btn-sm btn-outline-primary" title="Create PO">
                                            <i class="bi bi-cart-plus me-1"></i> Order
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-success">
                                        <i class="bi bi-check-circle fs-3 d-block mb-2"></i> All items have adequate stock above reorder thresholds.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Expiring Batches Alert -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-warning bg-opacity-10 py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h6 class="mb-0 fw-bold text-dark">
                        <i class="bi bi-hourglass-split me-2 text-warning"></i> Batches Expiring in 30 Days ({{ $expiringBatches->count() }})
                    </h6>
                    <span class="badge bg-warning text-dark">Attention</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Item & Batch</th>
                                <th>Godown</th>
                                <th class="text-end">Qty</th>
                                <th>Expiry Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expiringBatches as $batch)
                                @php $exp = \Carbon\Carbon::parse($batch->expiry_date); @endphp
                                <tr>
                                    <td>
                                        <div class="fw-bold">{{ $batch->item->name ?? 'Item' }}</div>
                                        <span class="badge bg-light text-dark border">{{ $batch->batch_no }}</span>
                                    </td>
                                    <td>{{ $batch->godown->name ?? 'Main Godown' }}</td>
                                    <td class="text-end fw-bold">{{ number_format($batch->quantity, 2) }}</td>
                                    <td>
                                        <span class="{{ $exp->isPast() ? 'badge bg-danger' : 'badge bg-warning text-dark' }}">
                                            {{ $exp->format('d M Y') }}
                                            ({{ $exp->diffForHumans() }})
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-success">
                                        <i class="bi bi-shield-check fs-3 d-block mb-2"></i> No batches expiring within the next 30 days.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

