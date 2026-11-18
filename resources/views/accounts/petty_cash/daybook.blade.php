@extends('layouts.app')

@section('title', 'Petty Cash Register / Daybook')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('accounts.petty_cash.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Accounts
        </a>
        <h4 class="fw-bold mb-0">Petty Cash Register & Daybook</h4>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer me-1"></i> Print Daybook
        </button>
    </div>
</div>

<!-- Filters Bar -->
<div class="card border-0 shadow-sm mb-4 d-print-none">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('accounts.petty_cash.daybook') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Cash Account</label>
                <select name="account_id" class="form-select form-select-sm" required>
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ $selectedAccount && $selectedAccount->id == $acc->id ? 'selected' : '' }}>
                            {{ $acc->name }} (Float: ₹{{ number_format($acc->float_limit, 2) }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">From Date</label>
                <input type="date" name="from_date" class="form-control form-control-sm" value="{{ $fromDate }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">To Date</label>
                <input type="date" name="to_date" class="form-control form-control-sm" value="{{ $toDate }}">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">
                    <i class="bi bi-funnel me-1"></i> View Register
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border p-4 bg-white" id="daybook-printable-document">
    <!-- Header -->
    <div class="row border-bottom pb-3 mb-4 align-items-center">
        <div class="col-8">
            <h3 class="fw-bold text-dark mb-1">{{ auth()->user()->company->name }}</h3>
            <p class="text-muted small mb-0">{{ auth()->user()->company->address }}</p>
            <div class="text-muted small"><strong>PETTY CASH DAYBOOK & EXPENSE REGISTER</strong></div>
        </div>
        <div class="col-4 text-end">
            <div class="fw-bold text-primary fs-5">{{ $selectedAccount->name ?? 'All Accounts' }}</div>
            <div class="small text-muted">Period: {{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}</div>
            <div class="small text-muted">Custodian: {{ $selectedAccount->custodian->user->name ?? $selectedAccount->custodian->staff_code ?? 'Central' }}</div>
        </div>
    </div>

    <!-- Summary Balances Strip -->
    <div class="row g-3 text-center mb-4">
        <div class="col-md-3">
            <div class="p-3 bg-light rounded border">
                <div class="text-muted small text-uppercase fw-semibold">Opening Balance</div>
                <div class="fs-5 fw-bold text-dark mt-1">₹ {{ number_format($openingBalance, 2) }}</div>
                <small class="text-muted">As of {{ \Carbon\Carbon::parse($fromDate)->format('d-m-Y') }}</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 bg-light rounded border border-start border-success border-3">
                <div class="text-muted small text-uppercase fw-semibold">+ Float Replenishments</div>
                <div class="fs-5 fw-bold text-success mt-1">₹ {{ number_format($totalReceipts, 2) }}</div>
                <small class="text-muted">{{ count($replenishments) }} top-up transaction(s)</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 bg-light rounded border border-start border-danger border-3">
                <div class="text-muted small text-uppercase fw-semibold">- Expense Payments</div>
                <div class="fs-5 fw-bold text-danger mt-1">₹ {{ number_format($totalPayments, 2) }}</div>
                <small class="text-muted">{{ count($vouchers) }} voucher(s) disbursed</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 bg-light rounded border border-start border-primary border-3">
                <div class="text-muted small text-uppercase fw-semibold">= Closing Cash in Hand</div>
                <div class="fs-5 fw-bold text-primary mt-1">₹ {{ number_format($closingBalance, 2) }}</div>
                <small class="text-muted">As of {{ \Carbon\Carbon::parse($toDate)->format('d-m-Y') }}</small>
            </div>
        </div>
    </div>

    <!-- Category Expense Breakdown -->
    @if(count($categoryBreakdown) > 0)
    <div class="mb-4">
        <h6 class="fw-bold text-dark mb-2"><i class="bi bi-pie-chart me-1"></i> Category-Wise Expense Distribution</h6>
        <div class="table-responsive">
            <table class="table table-sm table-bordered bg-light mb-0">
                <thead class="table-white small">
                    <tr>
                        <th>Expense Category</th>
                        <th class="text-center">Voucher Count</th>
                        <th class="text-end">Amount (INR)</th>
                        <th class="text-end">% of Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($categoryBreakdown as $cb)
                        @php $share = $totalPayments > 0 ? round(($cb['total'] / $totalPayments) * 100, 1) : 0; @endphp
                        <tr>
                            <td class="fw-semibold">{{ $cb['category'] }}</td>
                            <td class="text-center">{{ $cb['count'] }}</td>
                            <td class="text-end fw-semibold">₹ {{ number_format($cb['total'], 2) }}</td>
                            <td class="text-end text-muted">{{ $share }}%</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Chronological Register -->
    @php
        // Merge vouchers and replenishments chronologically
        $events = collect();
        foreach ($replenishments as $r) {
            $events->push([
                'date' => $r->replenishment_date,
                'type' => 'Replenishment',
                'ref_no' => $r->replenishment_no,
                'party_narration' => "Top-up from {$r->bankLedger->name}" . ($r->transaction_reference ? " ({$r->transaction_reference})" : ''),
                'category' => 'Bank Transfer / Contra',
                'receipt' => (float)$r->amount,
                'payment' => 0,
            ]);
        }
        foreach ($vouchers as $v) {
            $events->push([
                'date' => $v->voucher_date,
                'type' => 'Voucher',
                'ref_no' => $v->voucher_no,
                'party_narration' => "Paid to: {$v->paid_to} - {$v->remarks}",
                'category' => $v->expense_category,
                'receipt' => 0,
                'payment' => (float)$v->amount,
            ]);
        }
        $sortedEvents = $events->sortBy('date');
        $runningBal = $openingBalance;
    @endphp

    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-journal-text me-1"></i> Chronological Daybook Transactions</h6>
    <div class="table-responsive mb-4">
        <table class="table table-sm table-bordered align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th style="width: 10%;">Date</th>
                    <th style="width: 14%;">Ref / Voucher #</th>
                    <th style="width: 18%;">Category / Head</th>
                    <th style="width: 32%;">Particulars / Narration</th>
                    <th class="text-end" style="width: 13%;">Receipt (₹)</th>
                    <th class="text-end" style="width: 13%;">Payment (₹)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="table-light text-muted fst-italic">
                    <td>{{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }}</td>
                    <td colspan="3"><strong>Brought Forward (Opening Cash Balance)</strong></td>
                    <td class="text-end fw-semibold">₹ {{ number_format($openingBalance, 2) }}</td>
                    <td class="text-end">-</td>
                </tr>
                @forelse($sortedEvents as $ev)
                    <tr>
                        <td>{{ $ev['date']->format('d M Y') }}</td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $ev['ref_no'] }}</span>
                        </td>
                        <td><small class="fw-semibold">{{ $ev['category'] }}</small></td>
                        <td>
                            <div class="small">{{ $ev['party_narration'] }}</div>
                        </td>
                        <td class="text-end fw-semibold {{ $ev['receipt'] > 0 ? 'text-success' : 'text-muted' }}">
                            {{ $ev['receipt'] > 0 ? '₹ ' . number_format($ev['receipt'], 2) : '-' }}
                        </td>
                        <td class="text-end fw-semibold {{ $ev['payment'] > 0 ? 'text-danger' : 'text-muted' }}">
                            {{ $ev['payment'] > 0 ? '₹ ' . number_format($ev['payment'], 2) : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No transactions found in this period.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="table-light fw-bold">
                    <td colspan="4" class="text-end">Period Totals:</td>
                    <td class="text-end text-success">₹ {{ number_format($totalReceipts, 2) }}</td>
                    <td class="text-end text-danger">₹ {{ number_format($totalPayments, 2) }}</td>
                </tr>
                <tr class="table-primary fw-bold fs-6">
                    <td colspan="4" class="text-end">Closing Cash in Hand Balance:</td>
                    <td colspan="2" class="text-end text-primary">₹ {{ number_format($closingBalance, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Signatures -->
    <div class="row pt-5 mt-4 text-center">
        <div class="col-4">
            <div class="border-top pt-2">
                <div class="fw-bold">{{ $selectedAccount->custodian->user->name ?? $selectedAccount->custodian->staff_code ?? 'Petty Cashier' }}</div>
                <small class="text-muted">Cashier In-Charge</small>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top pt-2">
                <div class="fw-bold">Internal Audit</div>
                <small class="text-muted">Checked & Reconciled</small>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top pt-2">
                <div class="fw-bold">Accounts Manager</div>
                <small class="text-muted">Approved By</small>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .d-print-none, .sidebar, header, footer { display: none !important; }
    body { background-color: #fff !important; font-size: 12px; }
    #daybook-printable-document { border: none !important; box-shadow: none !important; padding: 0 !important; }
}
</style>
@endsection
