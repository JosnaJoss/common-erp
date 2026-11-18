@extends('layouts.app')

@section('title', 'Credit Note ' . $return->return_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('sales.returns.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Returns
        </a>
        <h4 class="fw-bold mb-0">Credit Note: {{ $return->return_number }}</h4>
        <span class="badge bg-success fs-6">APPROVED & POSTED</span>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Print Credit Note
        </button>
        @if($return->invoice)
            <a href="{{ route('sales.invoices.show', $return->invoice) }}" class="btn btn-outline-primary">
                <i class="bi bi-receipt me-1"></i> Original Invoice
            </a>
        @endif
    </div>
</div>

<div class="card shadow-sm border p-4 bg-white" id="credit-note-printable">
    <!-- Header -->
    <div class="row border-bottom pb-3 mb-4">
        <div class="col-8">
            <h3 class="fw-bold text-primary mb-1">{{ auth()->user()->company->name ?? 'Common ERP Enterprise' }}</h3>
            <p class="text-muted small mb-0">{{ auth()->user()->company->address ?? 'Corporate Head Office' }}</p>
            <p class="text-muted small mb-0">Destination Warehouse: <strong>{{ $return->godown->name ?? 'Main Godown' }}</strong></p>
        </div>
        <div class="col-4 text-end">
            <span class="badge bg-success text-uppercase px-3 py-2 fs-6">CREDIT NOTE</span>
            <div class="fw-bold text-dark fs-5 mt-2">{{ $return->return_number }}</div>
            <div class="small text-muted">Date: {{ $return->return_date->format('d M Y') }}</div>
            @if($return->invoice)
                <div class="small text-muted">Against Invoice: <strong>{{ $return->invoice->invoice_number }}</strong></div>
            @endif
        </div>
    </div>

    <!-- Customer Details & Accounting -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card bg-light border-0 p-3 h-100">
                <h6 class="fw-bold text-uppercase text-muted small mb-2">Issued To (Customer)</h6>
                <div class="fw-bold fs-6">{{ $return->customer->name }}</div>
                <div class="small text-muted">{{ $return->customer->address }}, {{ $return->customer->city }} - {{ $return->customer->pincode }}</div>
                <div class="small text-muted">GSTIN: {{ $return->customer->gst_number ?? 'N/A' }} | Phone: {{ $return->customer->phone }}</div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card bg-light border-0 p-3 h-100">
                <h6 class="fw-bold text-uppercase text-muted small mb-2">Accounting & Quality Inspection</h6>
                <div class="small mb-1">
                    <strong>Journal Voucher:</strong>
                    @if($return->journalEntry)
                        <a href="{{ route('accounts.vouchers.show', $return->journalEntry) }}" class="badge bg-light text-primary border text-decoration-none">
                            {{ $return->journalEntry->voucher_no }} (Credit Note Posted)
                        </a>
                    @else
                        <span class="text-muted">Not Posted</span>
                    @endif
                </div>
                <div class="small mb-1">
                    <strong>Stock Action:</strong>
                    <span class="badge bg-{{ $return->stock_condition === 'accept_to_stock' ? 'success' : 'warning text-dark' }}">
                        {{ $return->stock_condition === 'accept_to_stock' ? 'Inwarded back into Godown Stock' : 'Marked as Scrap / Damaged (Not Restocked)' }}
                    </span>
                </div>
                <div class="small mt-2">
                    <strong>Return Reason:</strong>
                    <div class="text-danger fst-italic">{{ $return->return_reason }}</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Items Table -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 40%;">Returned Item Description</th>
                    <th style="width: 15%;">Batch #</th>
                    <th class="text-end" style="width: 12%;">Qty</th>
                    <th class="text-end" style="width: 14%;">Rate (₹)</th>
                    <th class="text-end" style="width: 14%;">Credit Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($return->items as $idx => $line)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <div class="fw-bold">{{ $line->item->name }}</div>
                            <small class="text-muted">Code: {{ $line->item->code }} | HSN: {{ $line->item->hsn_code ?? '-' }}</small>
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $line->batch_number }}</span></td>
                        <td class="text-end fw-bold">{{ number_format($line->return_quantity, 2) }} {{ $line->item->unit->symbol ?? '' }}</td>
                        <td class="text-end">₹ {{ number_format($line->rate, 2) }}</td>
                        <td class="text-end fw-bold">₹ {{ number_format($line->total_amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-light">
                <tr class="table-success">
                    <td colspan="5" class="text-end fw-bold fs-6">Total Credit Note Issued:</td>
                    <td class="text-end fw-bold fs-6 text-success">₹ {{ number_format($return->total_amount, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Signatures -->
    <div class="row mt-5 pt-4 text-center">
        <div class="col-6">
            <div class="border-top pt-2 mx-4">
                <small class="text-muted fw-bold">Customer Acknowledgment / Seal</small>
            </div>
        </div>
        <div class="col-6">
            <div class="border-top pt-2 mx-4">
                <small class="text-muted fw-bold">For {{ auth()->user()->company->name ?? 'Company ERP' }} (Authorised Signatory)</small>
            </div>
        </div>
    </div>
</div>
@endsection
