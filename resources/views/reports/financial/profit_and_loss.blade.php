@extends('layouts.app')

@section('title', 'Profit & Loss Statement')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div>
        <h4 class="fw-bold mb-1">Profit & Loss Statement</h4>
        <p class="text-muted small mb-0">Financial performance report comparing Operating Revenue against Operating & Direct Expenses</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Print P&L
        </button>
    </div>
</div>

<!-- Date Filter Form -->
<div class="card shadow-sm border-0 mb-4 d-print-none">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.financial.profit_and_loss') }}" class="row g-2 align-items-end">
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
                <a href="{{ route('reports.financial.profit_and_loss') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border p-4 bg-white" id="pnl-printable">
    <!-- Report Header -->
    <div class="text-center border-bottom pb-3 mb-4">
        <h3 class="fw-bold text-primary mb-1">{{ auth()->user()->company->name ?? 'Common ERP Enterprise' }}</h3>
        <h5 class="fw-bold text-dark mb-0">PROFIT & LOSS STATEMENT</h5>
        <div class="text-muted small">For the period from {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</div>
    </div>

    <!-- P&L Two-Column / Two-Section View -->
    <div class="row g-4 mb-4">
        <!-- Revenue Section -->
        <div class="col-md-6">
            <div class="card h-100 border">
                <div class="card-header bg-success bg-opacity-10 py-2">
                    <h6 class="mb-0 text-success fw-bold"><i class="bi bi-graph-up-arrow me-1"></i> Operating Revenue & Income</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Revenue Ledger</th>
                                <th class="text-end">Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($revenueRows as $r)
                                <tr>
                                    <td>{{ $r['ledger']->name }}</td>
                                    <td class="text-end fw-semibold">₹ {{ number_format($r['amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-3">No revenue recorded in this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-light fw-bold">
                            <tr>
                                <td>Total Revenue (A):</td>
                                <td class="text-end text-success fs-6">₹ {{ number_format($totalRevenue, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Expenses Section -->
        <div class="col-md-6">
            <div class="card h-100 border">
                <div class="card-header bg-warning bg-opacity-10 py-2">
                    <h6 class="mb-0 text-dark fw-bold"><i class="bi bi-receipt me-1"></i> Operating & Direct Expenses</h6>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Expense Ledger</th>
                                <th class="text-end">Amount (₹)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($expenseRows as $e)
                                <tr>
                                    <td>{{ $e['ledger']->name }}</td>
                                    <td class="text-end fw-semibold">₹ {{ number_format($e['amount'], 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="text-center text-muted py-3">No expenses recorded in this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-light fw-bold">
                            <tr>
                                <td>Total Expenses (B):</td>
                                <td class="text-end text-danger fs-6">₹ {{ number_format($totalExpense, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Net Profit / Loss Summary Card -->
    <div class="card bg-{{ $netProfit >= 0 ? 'success' : 'danger' }} bg-opacity-10 border border-{{ $netProfit >= 0 ? 'success' : 'danger' }} p-3 mb-4">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-1 {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                    <i class="bi bi-{{ $netProfit >= 0 ? 'trophy' : 'exclamation-octagon' }} me-2"></i>
                    {{ $netProfit >= 0 ? 'Net Operating Profit' : 'Net Operating Loss' }} (A - B)
                </h5>
                <small class="text-muted">Calculated as Total Operating Revenue minus Operating & Administrative Expenses</small>
            </div>
            <div class="text-end">
                <h3 class="fw-bold mb-0 {{ $netProfit >= 0 ? 'text-success' : 'text-danger' }}">
                    ₹ {{ number_format(abs($netProfit), 2) }}
                </h3>
            </div>
        </div>
    </div>

    <!-- Signatures -->
    <div class="row mt-5 pt-4 text-center">
        <div class="col-6">
            <div class="border-top pt-2 mx-4">
                <small class="text-muted fw-bold">Finance Manager / Comptroller</small>
            </div>
        </div>
        <div class="col-6">
            <div class="border-top pt-2 mx-4">
                <small class="text-muted fw-bold">Managing Director / CEO</small>
            </div>
        </div>
    </div>
</div>
@endsection

