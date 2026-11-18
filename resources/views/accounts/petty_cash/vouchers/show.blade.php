@extends('layouts.app')

@section('title', 'Petty Cash Voucher - ' . $voucher->voucher_no)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('accounts.petty_cash.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <h4 class="fw-bold mb-0">Petty Cash Voucher: {{ $voucher->voucher_no }}</h4>
        <span class="badge bg-success fs-6">PAID & POSTED</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('accounts.petty_cash.vouchers.print', $voucher) }}" target="_blank" class="btn btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Print Voucher
        </a>
        <a href="{{ route('accounts.petty_cash.vouchers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> New Voucher
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <div class="row border-bottom pb-3 mb-3">
                    <div class="col-6">
                        <span class="text-muted small">Petty Cash Account:</span>
                        <div class="fw-bold fs-5 text-primary">{{ $voucher->account->name }}</div>
                        <small class="text-muted">Custodian: {{ $voucher->account->custodian->user->name ?? $voucher->account->custodian->staff_code ?? 'Central' }}</small>
                    </div>
                    <div class="col-6 text-end">
                        <span class="text-muted small">Disbursement Date:</span>
                        <div class="fw-bold fs-6">{{ $voucher->voucher_date->format('d M Y') }}</div>
                        <div class="small text-muted">Created By: {{ $voucher->creator->name ?? 'Admin' }}</div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="text-muted small">Paid To (Payee):</label>
                        <div class="fw-bold fs-6">{{ $voucher->paid_to }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="text-muted small">Expense Category:</label>
                        <div><span class="badge bg-light text-primary border fs-6">{{ $voucher->expense_category }}</span></div>
                    </div>
                    <div class="col-12">
                        <label class="text-muted small">Description / Purpose:</label>
                        <div class="p-3 bg-light rounded border">{{ $voucher->remarks }}</div>
                    </div>
                </div>

                <div class="p-3 bg-light rounded border d-flex justify-content-between align-items-center">
                    <span class="fs-5 fw-bold text-dark">Total Amount Disbursed:</span>
                    <span class="fs-4 fw-bold text-danger">₹ {{ number_format($voucher->amount, 2) }}</span>
                </div>
            </div>
        </div>

        <!-- Double Entry Journal Entry Details -->
        @if($voucher->journalEntry)
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-journal-text me-1"></i> Posted Accounting Voucher ({{ $voucher->journalEntry->voucher_no }})</h6>
                    <a href="{{ route('accounts.vouchers.show', $voucher->journalEntry) }}" class="btn btn-sm btn-outline-primary">Full Voucher</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light small">
                            <tr>
                                <th>Ledger Account</th>
                                <th class="text-end">Debit (₹)</th>
                                <th class="text-end">Credit (₹)</th>
                                <th>Narration</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($voucher->journalEntry->lines as $line)
                                <tr>
                                    <td class="fw-semibold">{{ $line->ledger->name }}</td>
                                    <td class="text-end {{ $line->debit_amount > 0 ? 'text-primary fw-bold' : '' }}">
                                        {{ $line->debit_amount > 0 ? '₹ ' . number_format($line->debit_amount, 2) : '-' }}
                                    </td>
                                    <td class="text-end {{ $line->credit_amount > 0 ? 'text-danger fw-bold' : '' }}">
                                        {{ $line->credit_amount > 0 ? '₹ ' . number_format($line->credit_amount, 2) : '-' }}
                                    </td>
                                    <td class="small text-muted">{{ $line->narration }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <!-- Supporting Document Sidebar -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0"><i class="bi bi-paperclip me-1"></i> Supporting Receipt / Bill</h6>
            </div>
            <div class="card-body text-center p-4">
                @if($voucher->receipt_doc)
                    @php
                        $ext = pathinfo($voucher->receipt_doc, PATHINFO_EXTENSION);
                    @endphp
                    @if(in_array(strtolower($ext), ['jpg', 'jpeg', 'png']))
                        <img src="{{ asset('storage/' . $voucher->receipt_doc) }}" class="img-fluid rounded border mb-3" style="max-height: 280px;" alt="Receipt">
                    @else
                        <i class="bi bi-file-earmark-pdf fs-1 text-danger d-block mb-2"></i>
                        <div class="small fw-bold mb-3">{{ basename($voucher->receipt_doc) }}</div>
                    @endif
                    <a href="{{ asset('storage/' . $voucher->receipt_doc) }}" target="_blank" class="btn btn-outline-primary btn-sm w-100">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Open Original Document
                    </a>
                @else
                    <div class="py-4 text-muted">
                        <i class="bi bi-file-earmark-x fs-1 d-block mb-2 text-secondary"></i>
                        No supporting document uploaded for this voucher.
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
