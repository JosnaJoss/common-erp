@extends('layouts.app')

@section('title', 'Payroll Summary Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 d-print-none">
    <div>
        <h4 class="fw-bold mb-1">Monthly Payroll Summary Report</h4>
        <p class="text-muted small mb-0">Consolidated staff earnings, statutory PF & ESI deductions, and net wage disbursements</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark">
            <i class="bi bi-printer me-1"></i> Print Report
        </button>
        <a href="{{ route('staff.payroll.index') }}" class="btn btn-primary">
            <i class="bi bi-cash-coin me-1"></i> Payroll Processing
        </a>
    </div>
</div>

<!-- Month Filter Form -->
<div class="card shadow-sm border-0 mb-4 d-print-none">
    <div class="card-body">
        <form method="GET" action="{{ route('reports.staff.payroll_report') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-bold">Select Salary Month</label>
                <input type="month" name="month" class="form-control form-control-sm" value="{{ $month }}" onchange="this.form.submit()">
            </div>
            <div class="col-md-4 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-filter me-1"></i> Filter Month</button>
                <a href="{{ route('reports.staff.payroll_report', ['month' => now()->format('Y-m')]) }}" class="btn btn-sm btn-outline-secondary">Current Month</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border p-4 bg-white" id="payroll-report-printable">
    <!-- Header -->
    <div class="text-center border-bottom pb-3 mb-4">
        <h3 class="fw-bold text-primary mb-1">{{ auth()->user()->company->name ?? 'Common ERP Enterprise' }}</h3>
        <h5 class="fw-bold text-dark mb-0">MONTHLY PAYROLL REGISTER</h5>
        <div class="text-muted small">Month: <strong>{{ \Carbon\Carbon::parse($month . '-01')->format('F Y') }}</strong></div>
    </div>

    <!-- Summary KPI cards -->
    <div class="row g-3 mb-4 d-print-none">
        <div class="col-md-4">
            <div class="card bg-light border p-3">
                <small class="text-muted text-uppercase fw-bold">Total Gross Earnings</small>
                <h4 class="fw-bold mb-0 text-success">₹ {{ number_format($totalGross, 2) }}</h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-light border p-3">
                <small class="text-muted text-uppercase fw-bold">Statutory Deductions (PF+ESI)</small>
                <h4 class="fw-bold mb-0 text-danger">₹ {{ number_format($totalDeductions, 2) }}</h4>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-light border p-3">
                <small class="text-muted text-uppercase fw-bold">Total Net Pay Disbursed</small>
                <h4 class="fw-bold mb-0 text-primary">₹ {{ number_format($totalNet, 2) }}</h4>
            </div>
        </div>
    </div>

    <!-- Payroll Table -->
    <div class="table-responsive">
        <table class="table table-bordered align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Emp ID</th>
                    <th>Employee Name</th>
                    <th>Department</th>
                    <th class="text-end">Basic (₹)</th>
                    <th class="text-end">HRA (₹)</th>
                    <th class="text-end">OT (₹)</th>
                    <th class="text-end">Gross Pay (₹)</th>
                    <th class="text-end">PF (₹)</th>
                    <th class="text-end">ESI (₹)</th>
                    <th class="text-end">Total Ded (₹)</th>
                    <th class="text-end">Net Pay (₹)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($payrolls as $pr)
                    <tr>
                        <td><span class="badge bg-light text-dark border">{{ $pr->staff->employee_id }}</span></td>
                        <td>
                            <div class="fw-bold">{{ $pr->staff->full_name }}</div>
                            <small class="text-muted">{{ $pr->staff->designation ?? '-' }}</small>
                        </td>
                        <td>{{ $pr->staff->department->name ?? 'General' }}</td>
                        <td class="text-end">₹ {{ number_format($pr->basic_salary, 2) }}</td>
                        <td class="text-end">₹ {{ number_format($pr->hra, 2) }}</td>
                        <td class="text-end">₹ {{ number_format($pr->overtime_amount, 2) }}</td>
                        <td class="text-end fw-bold text-success">₹ {{ number_format($pr->gross_salary, 2) }}</td>
                        <td class="text-end text-danger">₹ {{ number_format($pr->pf_deduction, 2) }}</td>
                        <td class="text-end text-danger">₹ {{ number_format($pr->esi_deduction, 2) }}</td>
                        <td class="text-end fw-semibold text-danger">₹ {{ number_format($pr->total_deductions, 2) }}</td>
                        <td class="text-end fw-bold text-primary fs-6">₹ {{ number_format($pr->net_salary, 2) }}</td>
                        <td>
                            <span class="badge bg-{{ $pr->payroll_status === 'paid' ? 'success' : 'warning text-dark' }}">
                                {{ ucfirst($pr->payroll_status) }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="12" class="text-center py-4 text-muted">
                            No payroll records found for this month.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-light fw-bold fs-6">
                <tr>
                    <td colspan="6" class="text-end">Register Grand Totals:</td>
                    <td class="text-end text-success">₹ {{ number_format($totalGross, 2) }}</td>
                    <td colspan="2"></td>
                    <td class="text-end text-danger">₹ {{ number_format($totalDeductions, 2) }}</td>
                    <td class="text-end text-primary">₹ {{ number_format($totalNet, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Signatures -->
    <div class="row mt-5 pt-4 text-center">
        <div class="col-4">
            <div class="border-top pt-2 mx-3">
                <small class="text-muted fw-bold">HR Manager / Prepared By</small>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top pt-2 mx-3">
                <small class="text-muted fw-bold">Accounts Officer / Verified By</small>
            </div>
        </div>
        <div class="col-4">
            <div class="border-top pt-2 mx-3">
                <small class="text-muted fw-bold">Managing Director / Approved By</small>
            </div>
        </div>
    </div>
</div>
@endsection

