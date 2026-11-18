@extends('layouts.app')

@section('title', 'Partner Capital Statement')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('accounts.capital.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Capital
        </a>
        <h4 class="fw-bold mb-0">Partner Capital Statement & Equity Summary</h4>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="bi bi-printer me-1"></i> Print Statement
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4 d-print-none">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('accounts.capital.statement') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Select Partner / Investor</label>
                <select name="investor_id" class="form-select form-select-sm" required>
                    @foreach($investors as $inv)
                        <option value="{{ $inv->id }}" {{ $selectedInvestor && $selectedInvestor->id == $inv->id ? 'selected' : '' }}>
                            {{ $inv->name }} ({{ $inv->investor_type }})
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
                    <i class="bi bi-funnel me-1"></i> View Statement
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border p-4 bg-white" id="capital-printable-document">
    <!-- Header -->
    <div class="row border-bottom pb-3 mb-4 align-items-center">
        <div class="col-8">
            <h3 class="fw-bold text-dark mb-1">{{ auth()->user()->company->name }}</h3>
            <p class="text-muted small mb-0">{{ auth()->user()->company->address }}</p>
            <div class="text-muted small"><strong>PARTNER CAPITAL ACCOUNT STATEMENT</strong></div>
        </div>
        <div class="col-4 text-end">
            <div class="fw-bold text-primary fs-5">{{ $selectedInvestor->name ?? 'All Partners' }}</div>
            <div class="small text-muted">{{ $selectedInvestor->investor_type ?? 'Partner' }} | Holding: <strong>{{ number_format($selectedInvestor->shareholding_percentage ?? 0, 1) }}%</strong></div>
            <div class="small text-muted">Statement Period: {{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('d M Y') }}</div>
        </div>
    </div>

    <!-- Summary Balances Strip -->
    <div class="row g-3 text-center mb-4">
        <div class="col-md-3">
            <div class="p-3 bg-light rounded border">
                <div class="text-muted small text-uppercase fw-semibold">Opening Capital</div>
                <div class="fs-5 fw-bold text-dark mt-1">₹ {{ number_format($openingBalance, 2) }}</div>
                <small class="text-muted">As of {{ \Carbon\Carbon::parse($fromDate)->format('d-m-Y') }}</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 bg-light rounded border border-start border-success border-3">
                <div class="text-muted small text-uppercase fw-semibold">+ Fresh Contributions</div>
                <div class="fs-5 fw-bold text-success mt-1">₹ {{ number_format($totalContributions, 2) }}</div>
                <small class="text-muted">Injected in period</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 bg-light rounded border border-start border-danger border-3">
                <div class="text-muted small text-uppercase fw-semibold">- Partner Drawings</div>
                <div class="fs-5 fw-bold text-danger mt-1">₹ {{ number_format($totalDrawings, 2) }}</div>
                <small class="text-muted">Withdrawn in period</small>
            </div>
        </div>
        <div class="col-md-3">
            <div class="p-3 bg-light rounded border border-start border-primary border-3">
                <div class="text-muted small text-uppercase fw-semibold">= Closing Capital Balance</div>
                <div class="fs-5 fw-bold text-primary mt-1">₹ {{ number_format($closingBalance, 2) }}</div>
                <small class="text-muted">As of {{ \Carbon\Carbon::parse($toDate)->format('d-m-Y') }}</small>
            </div>
        </div>
    </div>

    <!-- Itemized Ledger Transactions -->
    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-list-check me-1"></i> Capital Account Ledger Entries</h6>
    <div class="table-responsive mb-4">
        <table class="table table-sm table-bordered align-middle mb-0">
            <thead class="table-light small">
                <tr>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 15%;">Transaction #</th>
                    <th style="width: 35%;">Particulars / Mode</th>
                    <th class="text-end" style="width: 13%;">Debit / Drawings (₹)</th>
                    <th class="text-end" style="width: 13%;">Credit / Contrib (₹)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="table-light text-muted fst-italic">
                    <td>{{ \Carbon\Carbon::parse($fromDate)->format('d M Y') }}</td>
                    <td colspan="3"><strong>Brought Forward (Opening Balance)</strong></td>
                    <td class="text-end fw-semibold">₹ {{ number_format($openingBalance, 2) }}</td>
                </tr>
                @forelse($transactions as $t)
                    <tr>
                        <td>{{ $t->transaction_date->format('d M Y') }}</td>
                        <td><span class="badge bg-light text-dark border">{{ $t->investment_no }}</span></td>
                        <td>
                            <div class="small fw-semibold">{{ $t->remarks }}</div>
                            <small class="text-muted">Settled via: {{ $t->depositLedger->name }} ({{ $t->payment_mode }})</small>
                        </td>
                        <td class="text-end fw-semibold {{ $t->transaction_type === 'drawings' ? 'text-danger' : 'text-muted' }}">
                            {{ $t->transaction_type === 'drawings' ? '₹ ' . number_format($t->amount, 2) : '-' }}
                        </td>
                        <td class="text-end fw-semibold {{ $t->transaction_type === 'contribution' ? 'text-success' : 'text-muted' }}">
                            {{ $t->transaction_type === 'contribution' ? '₹ ' . number_format($t->amount, 2) : '-' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No transactions recorded for this partner in the selected date range.</td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr class="table-light fw-bold">
                    <td colspan="3" class="text-end">Total Capital Movements:</td>
                    <td class="text-end text-danger">₹ {{ number_format($totalDrawings, 2) }}</td>
                    <td class="text-end text-success">₹ {{ number_format($totalContributions, 2) }}</td>
                </tr>
                <tr class="table-primary fw-bold fs-6">
                    <td colspan="3" class="text-end">Closing Capital Balance:</td>
                    <td colspan="2" class="text-end text-primary">₹ {{ number_format($closingBalance, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Company-Wide Capital Summary Table -->
    <h6 class="fw-bold text-dark mb-2"><i class="bi bi-pie-chart me-1"></i> Company-Wide Partners & Shareholders Equity Summary</h6>
    <div class="table-responsive mb-4">
        <table class="table table-sm table-bordered bg-light mb-0">
            <thead class="table-white small">
                <tr>
                    <th>Partner / Investor Name</th>
                    <th>Type</th>
                    <th class="text-center">Holding %</th>
                    <th class="text-end">Total Contributed (₹)</th>
                    <th class="text-end">Total Drawings (₹)</th>
                    <th class="text-end">Closing Equity (₹)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($companyCapitalSummary as $cs)
                    <tr class="{{ $selectedInvestor && $selectedInvestor->name === $cs['name'] ? 'table-warning fw-bold' : '' }}">
                        <td>{{ $cs['name'] }}</td>
                        <td><small>{{ $cs['type'] }}</small></td>
                        <td class="text-center fw-semibold text-primary">{{ number_format($cs['share_pct'], 1) }}%</td>
                        <td class="text-end">₹ {{ number_format($cs['contributions'], 2) }}</td>
                        <td class="text-end text-danger">₹ {{ number_format($cs['drawings'], 2) }}</td>
                        <td class="text-end fw-bold">₹ {{ number_format($cs['closing_balance'], 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="table-primary fw-bold">
                    <td colspan="2">Total Company Equity Capital:</td>
                    <td class="text-center">100.0%</td>
                    <td class="text-end">₹ {{ number_format($companyCapitalSummary->sum('contributions'), 2) }}</td>
                    <td class="text-end text-danger">₹ {{ number_format($companyCapitalSummary->sum('drawings'), 2) }}</td>
                    <td class="text-end text-primary fs-6">₹ {{ number_format($totalEquity, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Signatures -->
    <div class="row pt-5 mt-4 text-center">
        <div class="col-4">
            <div class="border-top pt-2">
                <div class="fw-bold">{{ $selectedInvestor->name ?? 'Partner' }}</div>
                <small class="text-muted">Partner / Investor Signature</small>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top pt-2">
                <div class="fw-bold">Chartered Accountant / Auditor</div>
                <small class="text-muted">Audited & Verified</small>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top pt-2">
                <div class="fw-bold">Managing Partner / Director</div>
                <small class="text-muted">Authorized Signatory</small>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .d-print-none, .sidebar, header, footer { display: none !important; }
    body { background-color: #fff !important; font-size: 12px; }
    #capital-printable-document { border: none !important; box-shadow: none !important; padding: 0 !important; }
}
</style>
@endsection
