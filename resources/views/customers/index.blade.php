@extends('layouts.app')

@section('title', 'Customers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Customer Registry & Price Levels</h4>
        <p class="text-muted small mb-0">Client accounts, pricing tiers, delivery territory assignments, and receivables ledger.</p>
    </div>
    <a href="{{ route('customers.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Register Customer
    </a>
</div>

<!-- FILTERS -->
<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-6">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search customer name, shop name, code, phone, or GST..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="price_level" class="form-select form-select-sm">
                    <option value="">All Price Tiers</option>
                    <option value="minimum" {{ request('price_level') == 'minimum' ? 'selected' : '' }}>Minimum (Wholesale Floor)</option>
                    <option value="normal" {{ request('price_level') == 'normal' ? 'selected' : '' }}>Normal (Standard Price)</option>
                    <option value="maximum" {{ request('price_level') == 'maximum' ? 'selected' : '' }}>Maximum (MRP / Retail)</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search me-1"></i> Search</button>
                <a href="{{ route('customers.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Customer & Shop</th>
                        <th>Contact</th>
                        <th>Route & Territory</th>
                        <th>Price Tier</th>
                        <th class="text-end">Outstanding Receivable</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                    <tr>
                        <td class="ps-4">
                            <a href="{{ route('customers.show', $customer) }}" class="fw-bold text-dark text-decoration-none">
                                {{ $customer->name }}
                            </a>
                            <div class="small text-muted">{{ $customer->shop_name ?? 'Individual' }}</div>
                            <span class="badge bg-light text-primary border" style="font-size: 0.7rem;">{{ $customer->code }}</span>
                        </td>
                        <td class="small">
                            <div><i class="bi bi-telephone me-1 text-muted"></i>{{ $customer->phone ?? 'N/A' }}</div>
                            <div class="text-muted"><i class="bi bi-geo-alt me-1"></i>{{ $customer->city ?? 'N/A' }}</div>
                        </td>
                        <td class="small">
                            <span class="badge bg-light text-dark border">{{ $customer->route->route_name ?? 'Direct' }}</span>
                            @if($customer->territory)
                            <div class="text-muted" style="font-size: 0.75rem;">{{ $customer->territory }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $customer->price_level === 'minimum' ? 'info text-dark' : ($customer->price_level === 'maximum' ? 'warning text-dark' : 'primary-subtle text-primary') }} text-capitalize">
                                {{ $customer->price_level }} Price
                            </span>
                        </td>
                        <td class="text-end">
                            <span class="fw-bold {{ $customer->current_balance > 0 ? 'text-danger' : 'text-success' }}">
                                ₹{{ number_format($customer->current_balance, 2) }}
                            </span>
                            <div class="small text-muted text-uppercase" style="font-size: 0.7rem;">{{ $customer->account_type }}</div>
                        </td>
                        <td>
                            <span class="badge bg-{{ $customer->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($customer->status) }}
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('customers.show', $customer) }}" class="btn btn-sm btn-light border" title="Account Statement">
                                <i class="bi bi-journal-text"></i>
                            </a>
                            <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-light border" title="Edit Customer">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">No customers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($customers->hasPages())
    <div class="card-footer bg-white border-top py-3">
        {{ $customers->links() }}
    </div>
    @endif
</div>
@endsection

