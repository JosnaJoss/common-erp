@extends('layouts.app')

@section('title', 'Sales Invoices & Billing')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Sales Invoices & Billing</h4>
        <p class="text-muted small mb-0">Generate GST tax invoices, deduct outward stock batches, and post customer receivables</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('sales.invoices.create') }}" class="btn btn-primary">
            <i class="bi bi-receipt me-1"></i> New Sales Invoice
        </a>
        <a href="{{ route('sales.collections.index') }}" class="btn btn-outline-success">
            <i class="bi bi-wallet2 me-1"></i> Customer Collections
        </a>
    </div>
</div>

<!-- Filter -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('sales.invoices.index') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-bold">Filter by Customer</label>
                <select name="customer_id" class="form-select form-select-sm">
                    <option value="">All Customers</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>{{ $c->name }} ({{ $c->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-filter me-1"></i> Filter</button>
                <a href="{{ route('sales.invoices.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
            <div class="col-md-3 text-end">
                <a href="{{ route('sales.deliveries.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-truck me-1"></i> Dispatch & Deliveries
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Invoices Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Invoice #</th>
                        <th>Invoice Date</th>
                        <th>Customer</th>
                        <th>Dispatch Godown</th>
                        <th class="text-end">Grand Total</th>
                        <th class="text-end">Collected</th>
                        <th class="text-end">Balance Due</th>
                        <th>Status</th>
                        <th>Accounting Ref</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($invoices as $inv)
                        <tr>
                            <td><span class="badge bg-light text-primary border fw-bold">{{ $inv->invoice_number }}</span></td>
                            <td>{{ \Carbon\Carbon::parse($inv->invoice_date)->format('d M Y') }}</td>
                            <td>
                                <div class="fw-bold">{{ $inv->customer->name ?? 'Customer' }}</div>
                                <small class="text-muted">{{ $inv->customer->city ?? '' }}</small>
                            </td>
                            <td>{{ $inv->godown->name ?? 'Main Godown' }}</td>
                            <td class="text-end fw-bold">₹ {{ number_format($inv->grand_total, 2) }}</td>
                            <td class="text-end text-success">₹ {{ number_format($inv->paid_amount, 2) }}</td>
                            <td class="text-end fw-bold {{ $inv->balance_amount > 0 ? 'text-danger' : 'text-muted' }}">
                                ₹ {{ number_format($inv->balance_amount, 2) }}
                            </td>
                            <td>
                                @if($inv->payment_status === 'paid')
                                    <span class="badge bg-success">Paid</span>
                                @elseif($inv->payment_status === 'partial')
                                    <span class="badge bg-warning text-dark">Partial</span>
                                @else
                                    <span class="badge bg-danger">Unpaid</span>
                                @endif
                            </td>
                            <td>
                                @if($inv->journalEntry)
                                    <a href="{{ route('accounts.vouchers.show', $inv->journalEntry) }}" class="badge bg-light text-primary border text-decoration-none">
                                        <i class="bi bi-journal-text me-1"></i> {{ $inv->journalEntry->voucher_no }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('sales.invoices.show', $inv) }}" class="btn btn-outline-primary" title="View Details">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="{{ route('sales.invoices.print', $inv) }}" target="_blank" class="btn btn-outline-dark" title="Print Invoice">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="bi bi-receipt fs-2 d-block mb-2"></i> No sales invoices generated yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($invoices->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $invoices->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection

