@extends('layouts.app')

@section('title', 'Voucher ' . $entry->voucher_no)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('accounts.vouchers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Vouchers
        </a>
        <h4 class="fw-bold mb-0">Voucher: {{ $entry->voucher_no }}</h4>
        <span class="badge bg-secondary fs-6">{{ $entry->voucher_type }}</span>
    </div>
    <button onclick="window.print()" class="btn btn-outline-dark">
        <i class="bi bi-printer me-1"></i> Print Voucher
    </button>
</div>

<div class="card shadow-sm border p-4 bg-white" id="voucher-printable">
    <!-- Header -->
    <div class="row border-bottom pb-3 mb-4">
        <div class="col-8">
            <h4 class="fw-bold text-primary mb-1">{{ $entry->company->name ?? auth()->user()->company->name }}</h4>
            <div class="text-muted small">{{ $entry->company->address ?? auth()->user()->company->address }}</div>
            <h5 class="fw-bold text-dark mt-2 mb-0">ACCOUNTING VOUCHER - {{ strtoupper($entry->voucher_type) }}</h5>
        </div>
        <div class="col-4 text-end">
            <span class="badge bg-light text-primary border fs-6">{{ $entry->voucher_no }}</span>
            <div class="small text-muted mt-2">Date: <strong>{{ \Carbon\Carbon::parse($entry->voucher_date)->format('d M Y') }}</strong></div>
            <div class="small text-muted">Posted By: {{ $entry->creator->name ?? 'System' }}</div>
            @if($entry->reference_number)
                <div class="small text-muted">Ref: {{ $entry->reference_number }} ({{ $entry->reference_type }})</div>
            @endif
        </div>
    </div>

    <!-- Voucher Narration -->
    <div class="card bg-light border-0 p-3 mb-4">
        <strong>General Narration:</strong>
        <div class="text-secondary">{{ $entry->narration ?: 'Double-entry accounting transaction.' }}</div>
    </div>

    <!-- Debit Credit Lines Table -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 5%;">#</th>
                    <th style="width: 45%;">Particulars / Ledger Account</th>
                    <th style="width: 20%;">Line Narration</th>
                    <th class="text-end" style="width: 15%;">Debit (Dr) ₹</th>
                    <th class="text-end" style="width: 15%;">Credit (Cr) ₹</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entry->lines as $idx => $line)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>
                            <div class="fw-bold">
                                <a href="{{ route('accounts.ledgers.statement', $line->ledger) }}" class="text-decoration-none text-dark">
                                    {{ $line->ledger->name }}
                                </a>
                            </div>
                            <small class="text-muted">{{ $line->ledger->accountGroup->name ?? 'Group' }} ({{ $line->ledger->accountGroup->nature ?? 'General' }})</small>
                        </td>
                        <td><small>{{ $line->narration ?: '-' }}</small></td>
                        <td class="text-end fw-bold text-primary">
                            {{ $line->debit > 0 ? '₹ ' . number_format($line->debit, 2) : '-' }}
                        </td>
                        <td class="text-end fw-bold text-danger">
                            {{ $line->credit > 0 ? '₹ ' . number_format($line->credit, 2) : '-' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-light fw-bold fs-6">
                <tr>
                    <td colspan="3" class="text-end">Voucher Totals:</td>
                    <td class="text-end text-primary">₹ {{ number_format($entry->total_debit, 2) }}</td>
                    <td class="text-end text-danger">₹ {{ number_format($entry->total_credit, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Signatures -->
    <div class="row mt-5 pt-4 text-center">
        <div class="col-4">
            <div class="border-top pt-2 mx-3">
                <small class="text-muted fw-bold">Prepared By</small>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top pt-2 mx-3">
                <small class="text-muted fw-bold">Verified By (Accountant)</small>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top pt-2 mx-3">
                <small class="text-muted fw-bold">Authorised Signatory</small>
            </div>
        </div>
    </div>
</div>
@endsection

