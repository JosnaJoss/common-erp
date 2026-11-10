@extends('layouts.app')

@section('title', 'Purchase Orders')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Purchase Orders (PO)</h4>
        <p class="text-muted small mb-0">Manage supplier purchase contracts, delivery schedules, and fulfillment</p>
    </div>
    <a href="{{ route('purchase.orders.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> Create Purchase Order
    </a>
</div>

<!-- Filter -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('purchase.orders.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold">Filter by Supplier</label>
                <select name="supplier_id" class="form-select form-select-sm">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }} ({{ $s->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Filter by Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="approved" {{ request('status') == 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="received" {{ request('status') == 'received' ? 'selected' : '' }}>Goods Received</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-filter me-1"></i> Filter</button>
                <a href="{{ route('purchase.orders.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Orders Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>PO Number</th>
                        <th>Date</th>
                        <th>Supplier</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">Tax (GST)</th>
                        <th class="text-end">Grand Total</th>
                        <th>Expected Delivery</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $po)
                        <tr>
                            <td><span class="badge bg-light text-primary border fw-bold">{{ $po->po_number }}</span></td>
                            <td>{{ \Carbon\Carbon::parse($po->po_date)->format('d M Y') }}</td>
                            <td>
                                <div class="fw-bold">{{ $po->supplier->name ?? 'Supplier' }}</div>
                                <small class="text-muted">{{ $po->supplier->city ?? '' }}</small>
                            </td>
                            <td class="text-end">₹ {{ number_format($po->subtotal, 2) }}</td>
                            <td class="text-end">₹ {{ number_format($po->tax_amount, 2) }}</td>
                            <td class="text-end fw-bold text-primary">₹ {{ number_format($po->total_amount, 2) }}</td>
                            <td>{{ $po->expected_delivery_date ? \Carbon\Carbon::parse($po->expected_delivery_date)->format('d M Y') : '-' }}</td>
                            <td>
                                @if($po->status === 'approved')
                                    <span class="badge bg-success">Approved</span>
                                @elseif($po->status === 'received')
                                    <span class="badge bg-primary">Fulfilled</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($po->status) }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('purchase.orders.show', $po) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-cart-x fs-2 d-block mb-2"></i> No purchase orders found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($orders->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $orders->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection

