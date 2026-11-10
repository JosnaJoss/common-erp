@extends('layouts.app')

@section('title', 'Sales Orders')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Sales Orders (SO)</h4>
        <p class="text-muted small mb-0">Customer sales bookings, quotation confirmations, and fulfillment tracking</p>
    </div>
    <a href="{{ route('sales.orders.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New Sales Order
    </a>
</div>

<!-- Filter -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('sales.orders.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold">Filter by Customer</label>
                <select name="customer_id" class="form-select form-select-sm">
                    <option value="">All Customers</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Filter by Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                    <option value="invoiced" {{ request('status') == 'invoiced' ? 'selected' : '' }}>Invoiced</option>
                    <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                    <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-filter me-1"></i> Filter</button>
                <a href="{{ route('sales.orders.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
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
                        <th>Order #</th>
                        <th>Order Date</th>
                        <th>Customer</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end">Tax (GST)</th>
                        <th class="text-end">Grand Total</th>
                        <th>Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $so)
                        <tr>
                            <td><span class="badge bg-light text-primary border fw-bold">{{ $so->order_number }}</span></td>
                            <td>{{ \Carbon\Carbon::parse($so->order_date)->format('d M Y') }}</td>
                            <td>
                                <div class="fw-bold">{{ $so->customer->name ?? 'Customer' }}</div>
                                <small class="text-muted">{{ $so->customer->city ?? '' }}</small>
                            </td>
                            <td class="text-end">₹ {{ number_format($so->subtotal, 2) }}</td>
                            <td class="text-end">₹ {{ number_format($so->tax_amount, 2) }}</td>
                            <td class="text-end fw-bold text-success">₹ {{ number_format($so->grand_total, 2) }}</td>
                            <td>
                                @if($so->status === 'confirmed')
                                    <span class="badge bg-success">Confirmed</span>
                                @elseif($so->status === 'invoiced')
                                    <span class="badge bg-primary">Invoiced</span>
                                @elseif($so->status === 'delivered')
                                    <span class="badge bg-info text-dark">Delivered</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($so->status) }}</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('sales.orders.show', $so) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-cart-x fs-2 d-block mb-2"></i> No sales orders found.
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

