@extends('layouts.app')

@section('title', 'Staff Loan Details - ' . $loan->loan_no)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <a href="{{ route('staff.loans.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i>
            </a>
            <h4 class="fw-bold mb-0">Loan {{ $loan->loan_no }}</h4>
            @if($loan->status === 'closed' || $loan->outstanding_balance <= 0)
                <span class="badge bg-success ms-2">Closed / Repaid</span>
            @else
                <span class="badge bg-primary ms-2">Active</span>
            @endif
        </div>
        <p class="text-muted small mb-0 ms-5">Sanctioned on {{ $loan->loan_date->format('d M Y') }} for {{ $loan->staff->full_name }} ({{ $loan->staff->employee_id }})</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('staff.payroll.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-wallet2 me-1"></i> Payroll Register
        </a>
        <a href="{{ route('staff.loans.index') }}" class="btn btn-primary">
            <i class="bi bi-list-ul me-1"></i> All Loans
        </a>
    </div>
</div>

<!-- Overview Row -->
<div class="row g-4 mb-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-person-badge text-primary me-2"></i> Employee Details</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 40%;">Name:</td>
                        <td class="fw-bold text-dark">{{ $loan->staff->full_name }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Employee ID:</td>
                        <td class="fw-semibold">{{ $loan->staff->employee_id }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Department:</td>
                        <td>{{ $loan->staff->department->name ?? 'General' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Designation:</td>
                        <td>{{ $loan->staff->designation ?? '-' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Basic Salary:</td>
                        <td class="fw-bold text-dark">₹ {{ number_format($loan->staff->basic_salary, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-bank text-success me-2"></i> Loan Financial Terms</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 45%;">Sanctioned Date:</td>
                        <td class="fw-semibold">{{ $loan->loan_date->format('d M Y') }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Principal Amount:</td>
                        <td class="fw-bold text-dark fs-6">₹ {{ number_format($loan->principal_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Interest Rate:</td>
                        <td>{{ $loan->interest_rate > 0 ? $loan->interest_rate . '% p.a.' : 'Interest-Free (0%)' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tenure:</td>
                        <td><span class="badge bg-light text-dark border">{{ $loan->repayment_months }} Months</span></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Monthly EMI:</td>
                        <td class="fw-bold text-primary">₹ {{ number_format($loan->monthly_emi, 2) }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3 border-bottom">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-check2-circle text-info me-2"></i> Accounting & Balance</h6>
            </div>
            <div class="card-body">
                <table class="table table-sm table-borderless mb-0">
                    <tr>
                        <td class="text-muted" style="width: 45%;">Disbursed From:</td>
                        <td class="fw-semibold">{{ $loan->disbursedLedger->name ?? 'Bank Account' }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Disbursal JV:</td>
                        <td>
                            @if($loan->journalEntry)
                                <a href="{{ route('accounts.vouchers.show', $loan->journalEntry) }}" class="badge bg-light text-primary border text-decoration-none">
                                    <i class="bi bi-journal-check me-1"></i> {{ $loan->journalEntry->voucher_no }}
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td class="text-muted">Start Month:</td>
                        <td class="fw-semibold">{{ $loan->start_month }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Outstanding:</td>
                        <td class="fw-bold {{ $loan->outstanding_balance > 0 ? 'text-danger' : 'text-success' }} fs-6">
                            ₹ {{ number_format($loan->outstanding_balance, 2) }}
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Installment Schedule -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-calendar3 me-2"></i> Loan Repayment & Deduction Schedule</h6>
        <span class="badge bg-light text-dark border">{{ $loan->installments->count() }} Total Installments</span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase">
                <tr>
                    <th style="width: 10%;">Inst #</th>
                    <th style="width: 15%;">Due Month</th>
                    <th class="text-end" style="width: 18%;">Installment Amount (₹)</th>
                    <th class="text-center" style="width: 15%;">Deduction Status</th>
                    <th style="width: 22%;">Payroll Reference</th>
                    <th style="width: 20%;">Deducted At</th>
                </tr>
            </thead>
            <tbody>
                @foreach($loan->installments as $inst)
                    <tr>
                        <td class="fw-bold text-muted">#{{ $inst->installment_number }}</td>
                        <td>
                            <strong class="text-dark">{{ \Carbon\Carbon::createFromFormat('Y-m', $inst->due_month)->format('F Y') }}</strong>
                        </td>
                        <td class="text-end fw-bold text-dark">
                            ₹ {{ number_format($inst->amount, 2) }}
                        </td>
                        <td class="text-center">
                            @if($inst->status === 'deducted')
                                <span class="badge bg-success"><i class="bi bi-check-circle me-1"></i> Deducted</span>
                            @elseif($inst->status === 'waived')
                                <span class="badge bg-secondary">Waived</span>
                            @else
                                <span class="badge bg-warning text-dark"><i class="bi bi-clock me-1"></i> Pending</span>
                            @endif
                        </td>
                        <td>
                            @if($inst->payroll)
                                <a href="{{ route('staff.payroll.payslip', $inst->payroll) }}" class="badge bg-light text-primary border text-decoration-none">
                                    <i class="bi bi-receipt me-1"></i> {{ $inst->payroll->month }} Payroll
                                </a>
                            @else
                                <span class="text-muted small">Auto-deducted on payroll</span>
                            @endif
                        </td>
                        <td>
                            @if($inst->deducted_at)
                                <span class="text-muted small">{{ $inst->deducted_at->format('d M Y H:i') }}</span>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

