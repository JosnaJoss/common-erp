@extends('layouts.app')

@section('title', 'Suppliers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Suppliers & Vendor Registry</h4>
        <p class="text-muted small mb-0">Vendors, purchase history, accounts payable, and GSTIN master.</p>
    </div>
    <a href="{{ route('suppliers.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Register Supplier
    </a>
</div>

<!-- FILTERS -->
<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-9">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search supplier name, code, phone, or GST number..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-search me-1"></i> Search</button>
                <a href="{{ route('suppliers.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
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
                        <th class="ps-4">Supplier Name & Code</th>
                        <th>Contact & Email</th>
                        <th>Location</th>
                        <th>GST & PAN</th>
                        <th class="text-end">Current Outstanding</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($suppliers as $supplier)
                    <tr>
                        <td class="ps-4">
                            <a href="{{ route('suppliers.show', $supplier) }}" class="fw-bold text-dark text-decoration-none">
                                {{ $supplier->name }}
                            </a>
                            <div><span class="badge bg-light text-primary border">{{ $supplier->code }}</span></div>
                        </td>
                        <td class="small">
                            <div class="fw-semibold">{{ $supplier->contact_person ?? 'N/A' }}</div>
                            <div class="text-muted"><i class="bi bi-telephone me-1"></i>{{ $supplier->phone ?? 'N/A' }}</div>
                        </td>
                        <td class="small text-muted">{{ $supplier->city ?? 'N/A' }}</td>
                        <td class="small">
                            <div>GST: <span class="fw-semibold">{{ $supplier->gst_number ?? 'N/A' }}</span></div>
                            <div class="text-muted">PAN: {{ $supplier->pan_number ?? 'N/A' }}</div>
                        </td>
                        <td class="text-end">
                            <span class="fw-bold {{ $supplier->current_balance > 0 ? 'text-danger' : 'text-success' }}">
                                ₹{{ number_format($supplier->current_balance, 2) }}
                            </span>
                            <div class="small text-muted text-uppercase" style="font-size: 0.7rem;">{{ $supplier->account_type }}</div>
                        </td>
                        <td>
                            <span class="badge bg-{{ $supplier->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($supplier->status) }}
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('suppliers.show', $supplier) }}" class="btn btn-sm btn-light border" title="Account Statement">
                                <i class="bi bi-journal-text"></i>
                            </a>
                            <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-light border" title="Edit Supplier">
                                <i class="bi bi-pencil"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center py-4 text-muted">No suppliers found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($suppliers->hasPages())
    <div class="card-footer bg-white border-top py-3">
        {{ $suppliers->links() }}
    </div>
    @endif
</div>
@endsection

