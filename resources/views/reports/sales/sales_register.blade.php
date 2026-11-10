@extends('layouts.app')

@section('title', 'Sales Register Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div>
        <h4 class="fw-bold mb-1">Sales Register Report</h4>
        <p class="text-muted small mb-0">Periodic sales ledger register: Taxable turnover, Output GST liability, and realization</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Print Register
        </button>
    </div>
</div>

<!-- Date Filter Form -->
<div class="card shadow-sm border-0 mb-4 d-print-none">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.sales.sales_register') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold">From Date</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ $startDate }}">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">To Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ $endDate }}">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-calendar-range me-1"></i> Generate Register</button>
                <a href="{{ route('reports.sales.sales_register') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border p-4 bg-white" id="sales-register-printable">
    <!-- Header -->
    <div class="text-center border-bottom pb-3 mb-4">
        <h3 class="fw-bold text-primary mb-1">{{ auth()->user()->company->name ?? 'Common ERP Enterprise' }}</h3>
        <h5 class="fw-bold text-dark mb-0">SALES REGISTER</h5>
        <div class="text-muted small">Period from {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} to {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</div>
    </div>

    <!-- Summary KPI cards -->
    <div class="row g-3 mb-4 d-print-none">
        <div class="col-md-3">
            <div class="card bg-light border p-3">
                <small class="text-muted text-uppercase fw-bold">Taxable Sales</small>
                <h5 class="fw-bold mb-0 text-dark">₹ {{ number_format($totalSubtotal, 2) }}</h5>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light border p-3">
                <small class="text-muted text-uppercase fw-bold">Output GST</small>
                <h5 class="fw-bold mb-0 text-secondary">₹ {{ number_format($totalTax, 2) }}</h5>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light border p-3">
                <small class="text-muted text-uppercase fw-bold">Grand Total</small>
                <h5 class="fw-bold mb-0 text-primary">₹ {{ number_format($totalGrand, 2) }}</h5>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-light border p-3">
                <small class="text-muted text-uppercase fw-bold">Outstanding</small>
                <h5 class="fw-bold mb-0 text-danger">₹ {{ number_format($totalBalance, 2) }}</h5>
            </div>
        </div>
    </div>

    <!-- Register Table -->
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th>Customer Name</th>
                    <th>GSTIN</th>
                    <th class="text-end">Taxable Value (₹)</th>
                    <th class="text-end">GST Tax (₹)</th>
                    <th class="text-end">Invoice Total (₹)</th>
                    <th class="text-end">Paid (₹)</th>
                    <th class="text-end">Balance (₹)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $inv)
                    <tr>
                        <td>
                            <a href="{{ route('sales.invoices.show', $inv) }}" class="fw-bold text-decoration-none">
                                {{ $inv->invoice_number }}
                            </a>
                        </td>
                        <td>{{ \Carbon\Carbon::parse($inv->invoice_date)->format('d M Y') }}</td>
                        <td>
                            <div class="fw-semibold">{{ $inv->customer->name ?? 'Customer' }}</div>
                            <small class="text-muted">{{ $inv->customer->city ?? '' }}</small>
                        </td>
                        <td><small class="text-muted">{{ $inv->customer->gst_number ?? 'Consumer' }}</small></td>
                        <td class="text-end">₹ {{ number_format($inv->subtotal, 2) }}</td>
                        <td class="text-end">₹ {{ number_format($inv->tax_amount, 2) }}</td>
                        <td class="text-end fw-bold">₹ {{ number_format($inv->grand_total, 2) }}</td>
                        <td class="text-end text-success">₹ {{ number_format($inv->paid_amount, 2) }}</td>
                        <td class="text-end fw-bold {{ $inv->balance_amount > 0 ? 'text-danger' : 'text-muted' }}">
                            ₹ {{ number_format($inv->balance_amount, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            No sales invoices recorded in this date range.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-light fw-bold fs-6">
                <tr>
                    <td colspan="4" class="text-end">Register Totals:</td>
                    <td class="text-end">₹ {{ number_format($totalSubtotal, 2) }}</td>
                    <td class="text-end">₹ {{ number_format($totalTax, 2) }}</td>
                    <td class="text-end text-primary">₹ {{ number_format($totalGrand, 2) }}</td>
                    <td class="text-end text-success">₹ {{ number_format($totalPaid, 2) }}</td>
                    <td class="text-end text-danger">₹ {{ number_format($totalBalance, 2) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@endsection

