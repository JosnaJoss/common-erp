@extends('layouts.app')

@section('title', 'Customer Profile - ' . $customer->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">{{ $customer->name }}</h4>
        <span class="badge bg-light text-primary border me-2">{{ $customer->code }}</span>
        <span class="badge bg-info-subtle text-info text-capitalize me-2">{{ $customer->price_level }} Price Tier</span>
        <span class="text-muted small">GSTIN: <span class="fw-semibold">{{ $customer->gst_number ?? 'Unregistered' }}</span></span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('customers.edit', $customer) }}" class="btn btn-outline-secondary btn-sm">Edit Profile</a>
        <a href="{{ route('sales.invoices.create', ['customer_id' => $customer->id]) }}" class="btn btn-primary btn-sm">+ New Sales Invoice</a>
    </div>
</div>

<!-- CUSTOMER METRIC CARDS -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card card-custom p-3 border-start border-danger border-4">
            <div class="text-muted small fw-semibold text-uppercase">Current Balance (Receivable)</div>
            <h3 class="fw-bold mb-0 text-danger mt-1">₹{{ number_format($customer->current_balance, 2) }}</h3>
            <small class="text-muted">Opening: ₹{{ number_format($customer->opening_balance, 2) }} ({{ ucfirst($customer->account_type) }})</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom p-3 border-start border-primary border-4">
            <div class="text-muted small fw-semibold text-uppercase">Total Invoiced Sales</div>
            <h3 class="fw-bold mb-0 text-primary mt-1">{{ $customer->salesInvoices->count() }}</h3>
            <small class="text-muted">Sum: ₹{{ number_format($customer->salesInvoices->sum('grand_total'), 2) }}</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom p-3 border-start border-success border-4">
            <div class="text-muted small fw-semibold text-uppercase">Total Collections Received</div>
            <h3 class="fw-bold mb-0 text-success mt-1">{{ $customer->collections->count() }}</h3>
            <small class="text-muted">Sum: ₹{{ number_format($customer->collections->sum('collection_amount'), 2) }}</small>
        </div>
    </div>
</div>

<!-- TABS: Invoices / Collections / Ledger -->
<div class="card card-custom">
    <div class="card-header bg-white border-bottom p-0">
        <ul class="nav nav-tabs card-header-tabs m-0 px-3" id="customerTabs">
            <li class="nav-item">
                <button class="nav-link active py-3 fw-semibold" data-bs-toggle="tab" data-bs-target="#invoicesTab">Sales Invoices</button>
            </li>
            <li class="nav-item">
                <button class="nav-link py-3 fw-semibold" data-bs-toggle="tab" data-bs-target="#collectionsTab">Collections / Receipts</button>
            </li>
            <li class="nav-item">
                <button class="nav-link py-3 fw-semibold" data-bs-toggle="tab" data-bs-target="#ledgerTab">Ledger Account</button>
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
                            <th class="ps-4">Invoice #</th>
                            <th>Date</th>
                            <th>Due Date</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Paid</th>
                            <th class="text-end">Balance</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @forelse($customer->salesInvoices as $si)
                        <tr>
                            <td class="ps-4"><a href="{{ route('sales.invoices.show', $si) }}" class="fw-semibold text-decoration-none">{{ $si->invoice_number }}</a></td>
                            <td>{{ \Carbon\Carbon::parse($si->invoice_date)->format('d M Y') }}</td>
                            <td class="text-muted">{{ $si->due_date ? \Carbon\Carbon::parse($si->due_date)->format('d M Y') : 'N/A' }}</td>
                            <td class="text-end fw-bold">₹{{ number_format($si->grand_total, 2) }}</td>
                            <td class="text-end text-success">₹{{ number_format($si->paid_amount, 2) }}</td>
                            <td class="text-end text-danger fw-semibold">₹{{ number_format($si->balance_amount, 2) }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $si->payment_status === 'paid' ? 'success' : ($si->payment_status === 'partial' ? 'warning text-dark' : 'danger') }}">
                                    {{ ucfirst($si->payment_status) }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" class="text-center py-4 text-muted">No sales invoices recorded for this customer.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Collections Tab -->
        <div class="tab-pane fade" id="collectionsTab">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-4">Receipt #</th>
                            <th>Date</th>
                            <th>Mode</th>
                            <th>Collected By</th>
                            <th>Reference / Txn</th>
                            <th class="text-end pe-4">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        @forelse($customer->collections as $col)
                        <tr>
                            <td class="ps-4 fw-semibold text-dark">{{ $col->collection_number }}</td>
                            <td>{{ \Carbon\Carbon::parse($col->collection_date)->format('d M Y') }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $col->payment_mode }}</span></td>
                            <td>{{ $col->collected_by ?? 'Direct' }}</td>
                            <td class="text-muted">{{ $col->transaction_reference ?? 'N/A' }}</td>
                            <td class="text-end pe-4 fw-bold text-success">₹{{ number_format($col->collection_amount, 2) }}</td>
                        </tr>
                        @empty
                        <tr><td colspan="6" class="text-center py-4 text-muted">No collections recorded for this customer.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Ledger Tab -->
        <div class="tab-pane fade" id="ledgerTab">
            @if($customer->ledger)
            <div class="p-3 bg-light border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <span class="fw-bold">Mapped Ledger Account:</span> {{ $customer->ledger->name }}
                </div>
                <a href="{{ route('accounts.ledgers.statement', $customer->ledger) }}" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-box-arrow-up-right me-1"></i> Full Ledger Book
                </a>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection

