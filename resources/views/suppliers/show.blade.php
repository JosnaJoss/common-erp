@extends('layouts.app')

@section('title', 'Supplier Profile - ' . $supplier->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">{{ $supplier->name }}</h4>
        <span class="badge bg-light text-primary border me-2">{{ $supplier->code }}</span>
        <span class="text-muted small">GSTIN: <span class="fw-semibold">{{ $supplier->gst_number ?? 'Unregistered' }}</span></span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-outline-secondary btn-sm">Edit Profile</a>
        <a href="{{ route('purchase.entries.create') }}" class="btn btn-primary btn-sm">+ New Purchase Bill</a>
    </div>
</div>

<!-- SUPPLIER SUMMARY CARDS -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card card-custom p-3 border-start border-danger border-4">
            <div class="text-muted small fw-semibold text-uppercase">Current Outstanding Payable</div>
            <h3 class="fw-bold mb-0 text-danger mt-1">₹{{ number_format($supplier->current_balance, 2) }}</h3>
            <small class="text-muted">Opening: ₹{{ number_format($supplier->opening_balance, 2) }} ({{ ucfirst($supplier->account_type) }})</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom p-3 border-start border-primary border-4">
            <div class="text-muted small fw-semibold text-uppercase">Total Purchase Invoices</div>
            <h3 class="fw-bold mb-0 text-primary mt-1">{{ $supplier->purchaseEntries->count() }}</h3>
            <small class="text-muted">Sum: ₹{{ number_format($supplier->purchaseEntries->sum('grand_total'), 2) }}</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom p-3 border-start border-success border-4">
            <div class="text-muted small fw-semibold text-uppercase">Total Payments Disbursed</div>
            <h3 class="fw-bold mb-0 text-success mt-1">{{ $supplier->payments->count() }}</h3>
            <small class="text-muted">Sum: ₹{{ number_format($supplier->payments->sum('payment_amount'), 2) }}</small>
        </div>
    </div>
</div>

<!-- TABS: Invoices / Payments / Ledger -->
<div class="card card-custom">
    <div class="card-header bg-white border-bottom p-0">
        <ul class="nav nav-tabs card-header-tabs m-0 px-3" id="supplierTabs">
            <li class="nav-item">
                <button class="nav-link active py-3 fw-semibold" data-bs-toggle="tab" data-bs-target="#invoicesTab">Purchase Bills</button>
            </li>
            <li class="nav-item">
                <button class="nav-link py-3 fw-semibold" data-bs-toggle="tab" data-bs-target="#paymentsTab">Disbursed Payments</button>
            </li>
            <li class="nav-item">
                <button class="nav-link py-3 fw-semibold" data-bs-toggle="tab" data-bs-target="#ledgerTab">Ledger Statement</button>
            </li>
        </ul>
    </div>
    <div class="card-body p-0 tab-content">
        <!-- Invoices Tab -->
        <div class="tab-pane fade show active" id="invoicesTab">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-4">Bill Reference</th>
                            <th>Supplier Invoice #</th>
                            <th>Date</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Balance</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @forelse($supplier->purchaseEntries as $pe)
                        <tr>
                            <td class="ps-4"><a href="{{ route('purchase.entries.show', $pe) }}" class="fw-semibold text-decoration-none">{{ $pe->purchase_number }}</a></td>
                            <td>{{ $pe->invoice_number }}</td>
                            <td>{{ \Carbon\Carbon::parse($pe->invoice_date)->format('d M Y') }}</td>
                            <td class="text-end fw-bold">₹{{ number_format($pe->grand_total, 2) }}</td>
                            <td class="text-end text-success">₹{{ number_format($pe->paid_amount, 2) }}</td>
                            <td class="text-end text-danger fw-semibold">₹{{ number_format($pe->balance_amount, 2) }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $pe->payment_status === 'paid' ? 'success' : ($pe->payment_status === 'partial' ? 'warning text-dark' : 'danger') }}">
                                    {{ ucfirst($pe->payment_status) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">No purchase bills recorded for this supplier.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Payments Tab -->
        <div class="tab-pane fade" id="paymentsTab">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-4">Payment #</th>
                            <th>Payment Date</th>
                            <th>Mode</th>
                            <th>Reference / Cheque</th>
                            <th class="text-end pe-4">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @forelse($supplier->payments as $pay)
                        <tr>
                            <td class="ps-4 fw-semibold text-dark">{{ $pay->payment_number }}</td>
                            <td>{{ \Carbon\Carbon::parse($pay->payment_date)->format('d M Y') }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $pay->payment_mode }}</span></td>
                            <td class="text-muted">{{ $pay->transaction_reference ?? 'N/A' }}</td>
                            <td class="text-end pe-4 fw-bold text-success">₹{{ number_format($pay->payment_amount, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="5" class="text-center py-4 text-muted">No payments recorded for this supplier.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Ledger Tab -->
        <div class="tab-pane fade" id="ledgerTab">
            @if($supplier->ledger)
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-bold">Mapped Ledger Account:</span> {{ $supplier->ledger->name }}
                </div>
                <a href="{{ route('accounts.ledgers.statement', $supplier->ledger) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Full Ledger Book
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

