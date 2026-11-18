@extends('layouts.app')

@section('title', 'Sales Returns & Credit Notes')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Sales Returns & Credit Notes</h4>
        <p class="text-muted small mb-0">Record customer product returns, inspect returned merchandise condition, restock usable items, and issue credit notes.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('sales.invoices.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-receipt me-1"></i> Sales Invoices
        </a>
        <a href="{{ route('sales.returns.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Sales Return
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('sales.returns.index') }}" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-bold">Filter by Customer</label>
                <select name="customer_id" class="form-select form-select-sm">
                    <option value="">All Customers</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->name }} ({{ $c->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-filter me-1"></i> Filter</button>
                <a href="{{ route('sales.returns.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Returns Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Credit Note #</th>
                        <th>Return Date</th>
                        <th>Customer</th>
                        <th>Against Invoice</th>
                        <th>Condition</th>
                        <th>Items</th>
                        <th class="text-end">Credit Amount</th>
                        <th>Reason</th>
                        <th>Accounting Ref</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $ret)
                        <tr>
                            <td><span class="badge bg-light text-success border fw-bold">{{ $ret->return_number }}</span></td>
                            <td>{{ $ret->return_date->format('d M Y') }}</td>
                            <td>
                                <div class="fw-bold">{{ $ret->customer->name ?? 'Customer' }}</div>
                                <small class="text-muted">{{ $ret->customer->city ?? '' }}</small>
                            </td>
                            <td>
                                @if($ret->invoice)
                                    <a href="{{ route('sales.invoices.show', $ret->invoice) }}" class="text-decoration-none fw-semibold">
                                        {{ $ret->invoice->invoice_number }}
                                    </a>
                                @else
                                    <span class="text-muted">Direct Return</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $ret->stock_condition === 'accept_to_stock' ? 'success' : 'warning text-dark' }}">
                                    {{ $ret->stock_condition === 'accept_to_stock' ? 'Restocked' : 'Scrap/Damaged' }}
                                </span>
                            </td>
                            <td>{{ $ret->items->count() }} items</td>
                            <td class="text-end fw-bold text-success">₹ {{ number_format($ret->total_amount, 2) }}</td>
                            <td><span class="small text-truncate d-inline-block" style="max-width: 180px;">{{ $ret->return_reason }}</span></td>
                            <td>
                                @if($ret->journalEntry)
                                    <a href="{{ route('accounts.vouchers.show', $ret->journalEntry) }}" class="badge bg-light text-primary border text-decoration-none">
                                        <i class="bi bi-journal-text me-1"></i> {{ $ret->journalEntry->voucher_no }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('sales.returns.show', $ret) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="bi bi-arrow-return-left fs-2 d-block mb-2"></i> No sales returns or credit notes recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($returns->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $returns->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection
