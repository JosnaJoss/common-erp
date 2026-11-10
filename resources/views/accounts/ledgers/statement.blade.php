@extends('layouts.app')

@section('title', 'Ledger Statement - ' . $ledger->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('accounts.ledgers.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Ledgers
        </a>
        <div>
            <h4 class="fw-bold mb-0">{{ $ledger->name }}</h4>
            <small class="text-muted">Group: {{ $ledger->accountGroup->name ?? 'General' }} ({{ $ledger->accountGroup->nature ?? 'Assets' }})</small>
        </div>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Print Statement
        </button>
    </div>
</div>

<!-- Date Filter Form -->
<div class="card shadow-sm border-0 mb-4 d-print-none">
    <div class="card-body">
        <form method="GET" action="{{ route('accounts.ledgers.statement', $ledger) }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold">From Date</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">To Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-calendar-range me-1"></i> Filter Period</button>
                <a href="{{ route('accounts.ledgers.statement', $ledger) }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Statement Print Card -->
<div class="card shadow-sm border p-4 bg-white" id="statement-printable">
    <!-- Header -->
    <div class="row border-bottom pb-3 mb-3">
        <div class="col-8">
            <h4 class="fw-bold text-primary mb-1">{{ auth()->user()->company->name ?? 'Common ERP Enterprise' }}</h4>
            <h5 class="fw-bold text-dark mb-0">STATEMENT OF ACCOUNT / GENERAL LEDGER</h5>
            <div class="text-muted small">Account: <strong>{{ $ledger->name }}</strong> ({{ $ledger->accountGroup->name ?? 'General' }})</div>
        </div>
        <div class="col-4 text-end">
            <div class="small text-muted">Statement Period:</div>
            <div class="fw-bold">{{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</div>
            <div class="mt-2">
                <span class="badge bg-light text-dark border fs-6">
                    Balance: ₹ {{ number_format($ledger->current_balance, 2) }} {{ strtoupper($ledger->balance_type) }}
                </span>
            </div>
        </div>
    </div>

    <!-- Ledger Lines Table -->
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 16%;">Voucher #</th>
                    <th style="width: 12%;">Type</th>
                    <th style="width: 30%;">Particulars / Narration</th>
                    <th class="text-end" style="width: 15%;">Debit (Dr) ₹</th>
                    <th class="text-end" style="width: 15%;">Credit (Cr) ₹</th>
                </tr>
            </thead>
            <tbody>
                <!-- Opening Balance Row -->
                <tr class="table-light text-muted">
                    <td>{{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}</td>
                    <td colspan="3"><em>Opening Balance b/f</em></td>
                    <td class="text-end fw-semibold">{{ $ledger->balance_type === 'debit' ? '₹ ' . number_format($ledger->opening_balance, 2) : '-' }}</td>
                    <td class="text-end fw-semibold">{{ $ledger->balance_type === 'credit' ? '₹ ' . number_format($ledger->opening_balance, 2) : '-' }}</td>
                </tr>

                @php
                    $periodDebit = 0;
                    $periodCredit = 0;
                @endphp

                @forelse($lines as $line)
                    @php
                        $periodDebit += $line->debit;
                        $periodCredit += $line->credit;
                    @endphp
                    <tr>
                        <td>{{ \Carbon\Carbon::parse($line->entry->voucher_date)->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('accounts.vouchers.show', $line->entry) }}" class="fw-bold text-decoration-none">
                                {{ $line->entry->voucher_no }}
                            </a>
                        </td>
                        <td><span class="badge bg-light text-dark border">{{ $line->entry->voucher_type }}</span></td>
                        <td>
                            <div>{{ $line->narration ?: $line->entry->narration }}</div>
                            @if($line->entry->reference_number)
                                <small class="text-muted">Ref: {{ $line->entry->reference_number }}</small>
                            @endif
                        </td>
                        <td class="text-end fw-semibold text-primary">
                            {{ $line->debit > 0 ? '₹ ' . number_format($line->debit, 2) : '-' }}
                        </td>
                        <td class="text-end fw-semibold text-danger">
                            {{ $line->credit > 0 ? '₹ ' . number_format($line->credit, 2) : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            No journal transactions recorded for this period.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-light fw-bold">
                <tr>
                    <td colspan="4" class="text-end">Period Totals:</td>
                    <td class="text-end text-primary">₹ {{ number_format($periodDebit, 2) }}</td>
                    <td class="text-end text-danger">₹ {{ number_format($periodCredit, 2) }}</td>
                </tr>
                <tr class="table-primary">
                    <td colspan="4" class="text-end fs-6">Closing Balance:</td>
                    <td colspan="2" class="text-end fs-6 text-primary">
                        ₹ {{ number_format($ledger->current_balance, 2) }} {{ strtoupper($ledger->balance_type) }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Signatures -->
    <div class="row mt-5 pt-4 text-center">
        <div class="col-6">
            <div class="border-top pt-2 mx-4">
                <small class="text-muted fw-bold">Auditor / Accountant</small>
            </div>
        </div>
        <div class="col-6">
            <div class="border-top pt-2 mx-4">
                <small class="text-muted fw-bold">Authorised Officer</small>
            </div>
        </div>
    </div>
</div>
@endsection

