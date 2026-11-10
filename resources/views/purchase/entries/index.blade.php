@extends('layouts.app')

@section('title', 'Purchase Bills & Inward Invoices')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Purchase Bills & Inward Invoices</h4>
        <p class="text-muted small mb-0">Record vendor tax invoices with automatic stock batch inward and double-entry accounting ledger posting</p>
    </div>
    <a href="{{ route('purchase.entries.create') }}" class="btn btn-primary">
        <i class="bi bi-plus-lg me-1"></i> New Purchase Bill Entry
    </a>
</div>

<!-- Filter -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('purchase.entries.index') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-bold">Filter by Supplier</label>
                <select name="supplier_id" class="form-select form-select-sm">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>{{ $s->name }} ({{ $s->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-filter me-1"></i> Filter</button>
                <a href="{{ route('purchase.entries.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
            <div class="col-md-3 text-end">
                <a href="{{ route('purchase.payments.index') }}" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-cash-stack me-1"></i> Supplier Payments
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Bills Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Bill Entry #</th>
                        <th>Supplier Invoice #</th>
                        <th>Invoice Date</th>
                        <th>Supplier</th>
                        <th>Warehouse</th>
                        <th class="text-end">Grand Total</th>
                        <th class="text-end">Paid Amount</th>
                        <th class="text-end">Balance Due</th>
                        <th>Payment Status</th>
                        <th>Accounting Ref</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $entry)
                        <tr>
                            <td><span class="badge bg-light text-primary border fw-bold">{{ $entry->purchase_number }}</span></td>
                            <td><strong class="text-dark">{{ $entry->invoice_number }}</strong></td>
                            <td>{{ \Carbon\Carbon::parse($entry->invoice_date)->format('d M Y') }}</td>
                            <td>
                                <div class="fw-bold">{{ $entry->supplier->name ?? 'Supplier' }}</div>
                                <small class="text-muted">{{ $entry->supplier->city ?? '' }}</small>
                            </td>
                            <td>{{ $entry->godown->name ?? 'Main Godown' }}</td>
                            <td class="text-end fw-bold">₹ {{ number_format($entry->grand_total, 2) }}</td>
                            <td class="text-end text-success">₹ {{ number_format($entry->paid_amount, 2) }}</td>
                            <td class="text-end fw-bold {{ $entry->balance_amount > 0 ? 'text-danger' : 'text-muted' }}">
                                ₹ {{ number_format($entry->balance_amount, 2) }}
                            </td>
                            <td>
                                @if($entry->payment_status === 'paid')
                                    <span class="badge bg-success">Paid</span>
                                @elseif($entry->payment_status === 'partial')
                                    <span class="badge bg-warning text-dark">Partial</span>
                                @else
                                    <span class="badge bg-danger">Unpaid</span>
                                @endif
                            </td>
                            <td>
                                @if($entry->journalEntry)
                                    <a href="{{ route('accounts.vouchers.show', $entry->journalEntry) }}" class="badge bg-light text-primary border text-decoration-none">
                                        <i class="bi bi-journal-text me-1"></i> {{ $entry->journalEntry->voucher_no }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('purchase.entries.show', $entry) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="text-center py-4 text-muted">
                                <i class="bi bi-receipt fs-2 d-block mb-2"></i> No purchase invoices recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($entries->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $entries->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection

