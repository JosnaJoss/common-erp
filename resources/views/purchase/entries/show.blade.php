@extends('layouts.app')

@section('title', 'Purchase Invoice ' . $entry->purchase_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('purchase.entries.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <h4 class="fw-bold mb-0">Purchase Bill: {{ $entry->purchase_number }}</h4>
        <span class="badge bg-{{ $entry->payment_status === 'paid' ? 'success' : ($entry->payment_status === 'partial' ? 'warning text-dark' : 'danger') }} fs-6">
            {{ strtoupper($entry->payment_status) }}
        </span>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Print Bill
        </button>
        @if($entry->balance_amount > 0)
            <a href="{{ route('purchase.payments.index') }}" class="btn btn-success">
                <i class="bi bi-cash-coin me-1"></i> Pay Supplier
            </a>
        @endif
    </div>
</div>

<div class="card shadow-sm border p-4 bg-white" id="purchase-bill-printable">
    <!-- Header -->
    <div class="row border-bottom pb-3 mb-4">
        <div class="col-8">
            <h3 class="fw-bold text-primary mb-1">{{ auth()->user()->company->name ?? 'Common ERP Enterprise' }}</h3>
            <p class="text-muted small mb-0">{{ auth()->user()->company->address ?? 'Corporate Head Office' }}</p>
            <p class="text-muted small mb-0">Inward Warehouse: <strong>{{ $entry->godown->name ?? 'Main Godown' }}</strong></p>
        </div>
        <div class="col-4 text-end">
            <h5 class="fw-bold text-dark mb-1">PURCHASE BILL INWARD</h5>
            <div class="fw-bold text-primary fs-5">{{ $entry->purchase_number }}</div>
            <div class="small text-muted">Supplier Bill #: <strong>{{ $entry->invoice_number }}</strong></div>
            <div class="small text-muted">Bill Date: {{ \Carbon\Carbon::parse($entry->invoice_date)->format('d M Y') }}</div>
            <div class="small text-muted">Terms: {{ $entry->payment_type }}</div>
        </div>
    </div>

    <!-- Vendor Details & Accounting Voucher -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-light border-0 p-3 h-100">
                <h6 class="fw-bold text-uppercase text-muted small mb-2">Supplier / Vendor</h6>
                <div class="fw-bold fs-6">{{ $entry->supplier->name }}</div>
                <div class="small text-muted">{{ $entry->supplier->address }}, {{ $entry->supplier->city }} - {{ $entry->supplier->pincode }}</div>
                <div class="small text-muted">GSTIN: {{ $entry->supplier->gst_number ?? 'N/A' }} | PAN: {{ $entry->supplier->pan_number ?? 'N/A' }}</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-light border-0 p-3 h-100">
                <h6 class="fw-bold text-uppercase text-muted small mb-2">Financial Accounting & Stock</h6>
                <div class="small mb-1">
                    <strong>Journal Voucher:</strong>
                    @if($entry->journalEntry)
                        <a href="{{ route('accounts.vouchers.show', $entry->journalEntry) }}" class="badge bg-light text-primary border text-decoration-none">
                            <i class="bi bi-journal-check me-1"></i> {{ $entry->journalEntry->voucher_no }} (Balanced)
                        </a>
                    @else
                        <span class="text-muted">Not Posted</span>
                    @endif
                </div>
                <div class="small mb-1"><strong>Stock Status:</strong> <span class="badge bg-success">Inward Recorded (FIFO)</span></div>
                <div class="small"><strong>Remarks:</strong> {{ $entry->remarks ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <!-- Line Items Table -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 35%;">Item Description</th>
                    <th style="width: 15%;">Batch #</th>
                    <th class="text-end" style="width: 15%;">Quantity</th>
                    <th class="text-end" style="width: 15%;">Unit Rate (₹)</th>
                    <th class="text-end" style="width: 15%;">Line Total (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entry->items as $idx => $line)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <div class="fw-bold">{{ $line->item->name }}</div>
                            <small class="text-muted">Code: {{ $line->item->code }} | HSN: {{ $line->item->hsn_code ?? '-' }}</small>
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $line->batch_number }}</span></td>
                        <td class="text-end fw-bold">{{ number_format($line->quantity, 2) }} {{ $line->item->unit->symbol ?? '' }}</td>
                        <td class="text-end">₹ {{ number_format($line->rate, 2) }}</td>
                        <td class="text-end fw-bold">₹ {{ number_format($line->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-light">
                <tr>
                    <td colspan="5" class="text-end fw-bold">Subtotal:</td>
                    <td class="text-end fw-bold">₹ {{ number_format($entry->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="text-end fw-bold">Input GST (Tax):</td>
                    <td class="text-end fw-bold">₹ {{ number_format($entry->tax_amount, 2) }}</td>
                </tr>
                <tr class="table-primary">
                    <td colspan="5" class="text-end fw-bold fs-6">Grand Total:</td>
                    <td class="text-end fw-bold fs-6 text-primary">₹ {{ number_format($entry->grand_total, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="text-end text-success fw-bold">Amount Paid:</td>
                    <td class="text-end text-success fw-bold">₹ {{ number_format($entry->paid_amount, 2) }}</td>
                </tr>
                <tr class="table-warning">
                    <td colspan="5" class="text-end text-danger fw-bold fs-6">Outstanding Balance Due:</td>
                    <td class="text-end text-danger fw-bold fs-6">₹ {{ number_format($entry->balance_amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Signatures -->
    <div class="row mt-5 pt-4 text-center">
        <div class="col-6">
            <div class="border-top pt-2 mx-4">
                <small class="text-muted fw-bold">Accounts Officer / Verification</small>
            </div>
        </div>
        <div class="col-6">
            <div class="border-top pt-2 mx-4">
                <small class="text-muted fw-bold">Finance Manager / Comptroller</small>
            </div>
        </div>
    </div>
</div>
@endsection

