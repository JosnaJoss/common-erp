@extends('layouts.app')

@section('title', 'Purchase Returns & Debit Notes')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Purchase Returns & Debit Notes</h4>
        <p class="text-muted small mb-0">Record rejected/returned materials to suppliers, deduct outward stock, and issue accounting debit notes.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('purchase.entries.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-file-earmark-text me-1"></i> Purchase Bills
        </a>
        <a href="{{ route('purchase.returns.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Purchase Return
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('purchase.returns.index') }}" class="row g-3 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-bold">Filter by Supplier</label>
                <select name="supplier_id" class="form-select form-select-sm">
                    <option value="">All Suppliers</option>
                    @foreach($suppliers as $s)
                        <option value="{{ $s->id }}" {{ request('supplier_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->name }} ({{ $s->code }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-filter me-1"></i> Filter</button>
                <a href="{{ route('purchase.returns.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
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
                        <th>Debit Note #</th>
                        <th>Return Date</th>
                        <th>Supplier</th>
                        <th>Against Bill #</th>
                        <th>Items Count</th>
                        <th class="text-end">Total Amount</th>
                        <th>Return Reason</th>
                        <th>Accounting Ref</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($returns as $ret)
                        <tr>
                            <td><span class="badge bg-light text-danger border fw-bold">{{ $ret->return_number }}</span></td>
                            <td>{{ $ret->return_date->format('d M Y') }}</td>
                            <td>
                                <div class="fw-bold">{{ $ret->supplier->name ?? 'Supplier' }}</div>
                                <small class="text-muted">{{ $ret->supplier->city ?? '' }}</small>
                            </td>
                            <td>
                                @if($ret->purchaseEntry)
                                    <a href="{{ route('purchase.entries.show', $ret->purchaseEntry) }}" class="text-decoration-none fw-semibold">
                                        {{ $ret->purchaseEntry->purchase_number }}
                                    </a>
                                    <div class="small text-muted">{{ $ret->purchaseEntry->invoice_number }}</div>
                                @else
                                    <span class="text-muted">Direct Return</span>
                                @endif
                            </td>
                            <td>{{ $ret->items->count() }} items</td>
                            <td class="text-end fw-bold text-danger">₹ {{ number_format($ret->total_amount, 2) }}</td>
                            <td><span class="small text-truncate d-inline-block" style="max-width: 200px;">{{ $ret->return_reason }}</span></td>
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
                                <a href="{{ route('purchase.returns.show', $ret) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-arrow-return-left fs-2 d-block mb-2"></i> No purchase returns or debit notes recorded yet.
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
