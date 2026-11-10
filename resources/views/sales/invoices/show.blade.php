@extends('layouts.app')

@section('title', 'Sales Invoice ' . $invoice->invoice_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('sales.invoices.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
        <h4 class="fw-bold mb-0">Sales Tax Invoice: {{ $invoice->invoice_number }}</h4>
        <span class="badge bg-{{ $invoice->payment_status === 'paid' ? 'success' : ($invoice->payment_status === 'partial' ? 'warning text-dark' : 'danger') }} fs-6">
            {{ strtoupper($invoice->payment_status) }}
        </span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('sales.invoices.print', $invoice) }}" target="_blank" class="btn btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Official Print View
        </a>
        @if($invoice->balance_amount > 0)
            <a href="{{ route('sales.collections.index') }}" class="btn btn-success">
                <i class="bi bi-wallet2 me-1"></i> Collect Payment
            </a>
        @endif
    </div>
</div>

<div class="card shadow-sm border p-4 bg-white" id="invoice-details-view">
    <!-- Header -->
    <div class="row border-bottom pb-3 mb-4">
        <div class="col-8">
            <h3 class="fw-bold text-primary mb-1">{{ $invoice->company->name ?? auth()->user()->company->name }}</h3>
            <p class="text-muted small mb-0">{{ $invoice->company->address ?? auth()->user()->company->address }}</p>
            <p class="text-muted small mb-0">GSTIN: {{ $invoice->company->gst_no ?? 'N/A' }} | PAN: {{ $invoice->company->pan_no ?? 'N/A' }}</p>
        </div>
        <div class="col-4 text-end">
            <h5 class="fw-bold text-dark mb-1">TAX INVOICE</h5>
            <div class="fw-bold text-primary fs-5">{{ $invoice->invoice_number }}</div>
            <div class="small text-muted">Date: {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d M Y') }}</div>
            <div class="small text-muted">Due Date: {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') : 'Immediate' }}</div>
            <div class="small text-muted">Dispatch Godown: <strong>{{ $invoice->godown->name ?? 'Main Godown' }}</strong></div>
        </div>
    </div>

    <!-- Customer Details & Accounting -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-light border-0 p-3 h-100">
                <h6 class="fw-bold text-uppercase text-muted small mb-2">Billed To (Customer)</h6>
                <div class="fw-bold fs-6">{{ $invoice->customer->name }}</div>
                <div class="small text-muted">{{ $invoice->customer->address }}, {{ $invoice->customer->city }} - {{ $invoice->customer->pincode }}</div>
                <div class="small text-muted">Phone: {{ $invoice->customer->phone }} | GSTIN: {{ $invoice->customer->gst_number ?? 'URP / Consumer' }}</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-light border-0 p-3 h-100">
                <h6 class="fw-bold text-uppercase text-muted small mb-2">Accounting & Stock Integrity</h6>
                <div class="small mb-1">
                    <strong>Journal Voucher:</strong>
                    @if($invoice->journalEntry)
                        <a href="{{ route('accounts.vouchers.show', $invoice->journalEntry) }}" class="badge bg-light text-primary border text-decoration-none">
                            <i class="bi bi-journal-check me-1"></i> {{ $invoice->journalEntry->voucher_no }} (Balanced Debit/Credit)
                        </a>
                    @else
                        <span class="text-muted">-</span>
                    @endif
                </div>
                <div class="small mb-1"><strong>Stock Outward:</strong> <span class="badge bg-success">Deducted Automatically</span></div>
                <div class="small"><strong>Terms:</strong> {{ $invoice->payment_terms ?? 'Net 30 Days' }}</div>
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
                    <th class="text-end" style="width: 15%;">Total Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $idx => $line)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <div class="fw-bold">{{ $line->item->name }}</div>
                            <small class="text-muted">Code: {{ $line->item->code }} | HSN: {{ $line->item->hsn_code ?? '-' }}</small>
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $line->batch_number }}</span></td>
                        <td class="text-end fw-bold">{{ number_format($line->quantity, 2) }} {{ $line->item->unit->symbol ?? '' }}</td>
                        <td class="text-end">₹ {{ number_format($line->rate, 2) }}</td>
                        <td class="text-end fw-bold text-dark">₹ {{ number_format($line->line_total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-light">
                <tr>
                    <td colspan="5" class="text-end fw-bold">Taxable Subtotal:</td>
                    <td class="text-end fw-bold">₹ {{ number_format($invoice->subtotal, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="text-end fw-bold">Output GST Liability:</td>
                    <td class="text-end fw-bold">₹ {{ number_format($invoice->tax_amount, 2) }}</td>
                </tr>
                <tr class="table-primary">
                    <td colspan="5" class="text-end fw-bold fs-6">Grand Total Invoice:</td>
                    <td class="text-end fw-bold fs-6 text-primary">₹ {{ number_format($invoice->grand_total, 2) }}</td>
                </tr>
                <tr>
                    <td colspan="5" class="text-end text-success fw-bold">Amount Collected:</td>
                    <td class="text-end text-success fw-bold">₹ {{ number_format($invoice->paid_amount, 2) }}</td>
                </tr>
                <tr class="table-warning">
                    <td colspan="5" class="text-end text-danger fw-bold fs-6">Outstanding Receivable:</td>
                    <td class="text-end text-danger fw-bold fs-6">₹ {{ number_format($invoice->balance_amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Collections Log if any -->
    @if($invoice->collections && $invoice->collections->count())
        <div class="card border mb-4">
            <div class="card-header bg-light py-2">
                <h6 class="mb-0 fw-bold"><i class="bi bi-clock-history me-1"></i> Receipts & Collections Log</h6>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Receipt #</th>
                            <th>Date</th>
                            <th>Mode</th>
                            <th>Ref</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($invoice->collections as $col)
                            <tr>
                                <td>{{ $col->collection_number }}</td>
                                <td>{{ \Carbon\Carbon::parse($col->collection_date)->format('d M Y') }}</td>
                                <td><span class="badge bg-secondary">{{ $col->payment_mode }}</span></td>
                                <td>{{ $col->transaction_reference ?? '-' }}</td>
                                <td class="text-end fw-bold text-success">₹ {{ number_format($col->collection_amount, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Signatures -->
    <div class="row mt-5 pt-4 text-center">
        <div class="col-6">
            <div class="border-top pt-2 mx-4">
                <small class="text-muted fw-bold">Customer Signature & Seal</small>
            </div>
        </div>
        <div class="col-6">
            <div class="border-top pt-2 mx-4">
                <small class="text-muted fw-bold">For {{ $invoice->company->name ?? 'Company ERP' }} (Authorised Signatory)</small>
            </div>
        </div>
    </div>
</div>
@endsection

