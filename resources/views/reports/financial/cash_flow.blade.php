@extends('layouts.app')

@section('title', 'Cash Flow Statement')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div>
        <h4 class="fw-bold mb-1">Cash Flow Statement</h4>
        <p class="text-muted small mb-0">Statement of Cash Flows (Indirect Method): Operating, Investing, and Financing Activities</p>
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
        <form method="GET" action="{{ route('reports.financial.cash_flow') }}" class="row g-2 align-items-end">
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
                <a href="{{ route('reports.financial.cash_flow') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border p-4 bg-white" id="cash-flow-printable">
    <!-- Header -->
    <div class="text-center border-bottom pb-3 mb-4">
        <h3 class="fw-bold text-primary mb-1">{{ auth()->user()->company->name ?? 'Common ERP Enterprise' }}</h3>
        <h5 class="fw-bold text-dark mb-0">STATEMENT OF CASH FLOWS</h5>
        <div class="text-muted small">For the period from {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</div>
    </div>

    <!-- Cash Flow Table -->
    <div class="table-responsive mb-4">
        <table class="table table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Cash Flow Activity & Itemized Particulars</th>
                    <th class="text-end" style="width: 250px;">Amount (₹)</th>
                </tr>
            </thead>
            <tbody>
                <!-- 1. Operating Activities -->
                <tr class="table-primary fw-bold">
                    <td colspan="2"><i class="bi bi-gear-wide-connected me-1"></i> 1. CASH FLOWS FROM OPERATING ACTIVITIES</td>
                </tr>
                <tr>
                    <td class="ps-4">Net Profit / (Loss) Before Taxation (from P&L)</td>
                    <td class="text-end fw-semibold {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                        ₹ {{ number_format($netProfit, 2) }}
                    </td>
                </tr>
                <tr>
                    <td class="ps-4 text-muted"><em>Working Capital Adjustments:</em></td>
                    <td class="text-end text-muted">-</td>
                </tr>
                <tr>
                    <td class="ps-5">(Increase) / Decrease in Sundry Debtors (Receivables)</td>
                    <td class="text-end fw-semibold">₹ {{ number_format(-$debtorsBalance, 2) }}</td>
                </tr>
                <tr>
                    <td class="ps-5">(Increase) / Decrease in Inventories / Stock on Hand</td>
                    <td class="text-end fw-semibold">₹ {{ number_format(-$inventoryBalance, 2) }}</td>
                </tr>
                <tr>
                    <td class="ps-5">Increase / (Decrease) in Sundry Creditors (Payables)</td>
                    <td class="text-end fw-semibold">₹ {{ number_format($creditorsBalance, 2) }}</td>
                </tr>
                <tr>
                    <td class="ps-5">Increase / (Decrease) in Duties & Taxes Payable</td>
                    <td class="text-end fw-semibold">₹ {{ number_format($taxesBalance, 2) }}</td>
                </tr>
                <tr class="table-light fw-bold">
                    <td class="ps-4 text-primary">Net Cash Generated from / (Used in) Operating Activities (A):</td>
                    <td class="text-end text-primary fs-6">₹ {{ number_format($operatingCashFlow, 2) }}</td>
                </tr>

                <!-- 2. Investing Activities -->
                <tr class="table-warning fw-bold">
                    <td colspan="2"><i class="bi bi-buildings me-1"></i> 2. CASH FLOWS FROM INVESTING ACTIVITIES</td>
                </tr>
                <tr>
                    <td class="ps-4">Purchase / Disposal of Fixed Assets & Capital Work</td>
                    <td class="text-end fw-semibold">₹ {{ number_format($investingCashFlow, 2) }}</td>
                </tr>
                <tr class="table-light fw-bold">
                    <td class="ps-4 text-warning text-dark">Net Cash Generated from / (Used in) Investing Activities (B):</td>
                    <td class="text-end text-dark fs-6">₹ {{ number_format($investingCashFlow, 2) }}</td>
                </tr>

                <!-- 3. Financing Activities -->
                <tr class="table-info fw-bold">
                    <td colspan="2"><i class="bi bi-cash-stack me-1"></i> 3. CASH FLOWS FROM FINANCING ACTIVITIES</td>
                </tr>
                <tr>
                    <td class="ps-4">Capital Contributions, Shareholder Equity & Partner Additions</td>
                    <td class="text-end fw-semibold">₹ {{ number_format($financingCashFlow, 2) }}</td>
                </tr>
                <tr class="table-light fw-bold">
                    <td class="ps-4 text-info text-dark">Net Cash Generated from / (Used in) Financing Activities (C):</td>
                    <td class="text-end text-dark fs-6">₹ {{ number_format($financingCashFlow, 2) }}</td>
                </tr>

                <!-- Net Summary -->
                <tr class="table-secondary fw-bold fs-6">
                    <td>NET INCREASE / (DECREASE) IN CASH & CASH EQUIVALENTS (A + B + C):</td>
                    <td class="text-end {{ $netCashChange >= 0 ? 'text-success' : 'text-danger' }}">
                        ₹ {{ number_format($netCashChange, 2) }}
                    </td>
                </tr>
                <tr>
                    <td class="ps-3 fw-semibold">Cash & Cash Equivalents at Beginning of Period:</td>
                    <td class="text-end fw-semibold">₹ {{ number_format($openingCashBalance, 2) }}</td>
                </tr>
                <tr class="table-success fw-bold fs-6">
                    <td class="text-success"><i class="bi bi-check-all me-1"></i> CASH & CASH EQUIVALENTS AT END OF PERIOD:</td>
                    <td class="text-end text-success fs-5">₹ {{ number_format($closingCashBalance, 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Cash & Bank Ledger Breakdown -->
    <div class="card border mb-4">
        <div class="card-header bg-light py-2">
            <h6 class="mb-0 fw-bold"><i class="bi bi-wallet2 me-1"></i> Cash & Bank Accounts Reconciliation Breakdown</h6>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Account Name</th>
                        <th>Account Type</th>
                        <th class="text-end">Closing Balance (₹)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cashBankRows as $cb)
                        <tr>
                            <td>{{ $cb['ledger']->name }}</td>
                            <td><span class="badge bg-secondary">{{ $cb['ledger']->is_bank_or_cash ? 'Bank/Cash' : 'Liquid' }}</span></td>
                            <td class="text-end fw-semibold">₹ {{ number_format($cb['balance'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-2">No bank or cash ledgers active.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <td colspan="2">Total Reconciled Cash & Bank:</td>
                        <td class="text-end text-primary fs-6">₹ {{ number_format($closingCashBalance, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <!-- Signatures -->
    <div class="row mt-5 pt-4 text-center">
        <div class="col-6">
            <div class="border-top pt-2 mx-4">
                <small class="text-muted fw-bold">Chief Financial Officer (CFO)</small>
            </div>
        </div>
        <div class="col-6">
            <div class="border-top pt-2 mx-4">
                <small class="text-muted fw-bold">Statutory Auditor / Chartered Accountant</small>
            </div>
        </div>
    </div>
</div>
@endsection
