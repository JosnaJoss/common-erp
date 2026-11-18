@extends('layouts.app')

@section('title', 'Staff Loans & Advances')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-bank text-primary me-2"></i> Employee Loans Management</h4>
        <p class="text-muted small mb-0">Sanction long-term staff loans, track automated monthly EMI deductions via payroll, and manage loan balances.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('staff.advances.index') }}" class="btn btn-outline-warning">
            <i class="bi bi-cash-coin me-1"></i> Salary Advances
        </a>
        <a href="{{ route('staff.payroll.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-wallet2 me-1"></i> Payroll Register
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newLoanModal">
            <i class="bi bi-plus-circle me-1"></i> Sanction Staff Loan
        </button>
    </div>
</div>

<!-- Metrics Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-primary border-4">
            <div class="text-muted small text-uppercase fw-semibold">Total Loans Disbursed</div>
            <div class="fs-4 fw-bold text-dark mt-1">₹ {{ number_format($stats['total_disbursed'], 2) }}</div>
            <div class="small text-muted">Cumulative sanctioned principal</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-success border-4">
            <div class="text-muted small text-uppercase fw-semibold">Total Repaid / Recovered</div>
            <div class="fs-4 fw-bold text-success mt-1">₹ {{ number_format($stats['total_repaid'], 2) }}</div>
            <div class="small text-muted">Collected through EMI deductions</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-danger border-4">
            <div class="text-muted small text-uppercase fw-semibold">Outstanding Loan Balance</div>
            <div class="fs-4 fw-bold text-danger mt-1">₹ {{ number_format($stats['total_outstanding'], 2) }}</div>
            <div class="small text-muted">Active employee debt asset</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-info border-4">
            <div class="text-muted small text-uppercase fw-semibold">Active Staff Loans</div>
            <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($stats['active_loans_count']) }}</div>
            <div class="small text-muted">Loans currently in repayment</div>
        </div>
    </div>
</div>

<!-- Loans Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-journal-text me-1"></i> Staff Loans Register</h6>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newLoanModal">
            <i class="bi bi-plus-lg me-1"></i> Sanction Loan
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase">
                <tr>
                    <th style="width: 13%;">Loan #</th>
                    <th style="width: 10%;">Date</th>
                    <th style="width: 20%;">Employee</th>
                    <th class="text-end" style="width: 12%;">Principal (₹)</th>
                    <th class="text-center" style="width: 13%;">Term & EMI (₹)</th>
                    <th class="text-end" style="width: 12%;">Outstanding (₹)</th>
                    <th class="text-center" style="width: 10%;">Status</th>
                    <th class="text-end" style="width: 10%;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($loans as $loan)
                    <tr>
                        <td>
                            <a href="{{ route('staff.loans.show', $loan) }}" class="fw-bold text-primary text-decoration-none">
                                {{ $loan->loan_no }}
                            </a>
                        </td>
                        <td>{{ $loan->loan_date->format('d M Y') }}</td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $loan->staff->full_name }}</div>
                            <small class="text-muted">{{ $loan->staff->employee_id }} | {{ $loan->staff->department->name ?? 'Dept' }}</small>
                        </td>
                        <td class="text-end fw-bold text-dark">
                            ₹ {{ number_format($loan->principal_amount, 2) }}
                            @if($loan->interest_rate > 0)
                                <div class="small text-muted font-monospace">@ {{ $loan->interest_rate }}% p.a.</div>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="badge bg-light text-dark border">{{ $loan->repayment_months }} Months</span>
                            <div class="fw-semibold text-dark mt-1">₹ {{ number_format($loan->monthly_emi, 2) }} / mo</div>
                        </td>
                        <td class="text-end fw-bold {{ $loan->outstanding_balance > 0 ? 'text-danger' : 'text-success' }}">
                            ₹ {{ number_format($loan->outstanding_balance, 2) }}
                        </td>
                        <td class="text-center">
                            @if($loan->status === 'closed' || $loan->outstanding_balance <= 0)
                                <span class="badge bg-success">Closed</span>
                            @else
                                <span class="badge bg-primary">Active</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="{{ route('staff.loans.show', $loan) }}" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-calendar3 me-1"></i> Schedule
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-bank fs-1 d-block mb-2 text-secondary"></i>
                            No staff loans recorded yet. Click "Sanction Staff Loan" to create one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($loans->hasPages())
        <div class="card-footer bg-white py-2">
            {{ $loans->links() }}
        </div>
    @endif
</div>

<!-- Modal: Sanction Staff Loan -->
<div class="modal fade" id="newLoanModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="{{ route('staff.loans.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-bank me-2 text-primary"></i> Sanction Employee Loan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Select Employee <span class="text-danger">*</span></label>
                            <select name="staff_id" class="form-select" required>
                                <option value="">-- Choose Employee --</option>
                                @foreach($staffMembers as $s)
                                    <option value="{{ $s->id }}">
                                        {{ $s->full_name }} ({{ $s->employee_id }}) — Basic: ₹{{ number_format($s->basic_salary, 2) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Loan Sanction Date <span class="text-danger">*</span></label>
                            <input type="date" name="loan_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Principal Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="100" id="principal_input" name="principal_amount" class="form-control" placeholder="e.g. 50000.00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Repayment Months <span class="text-danger">*</span></label>
                            <input type="number" min="1" max="120" id="months_input" name="repayment_months" value="10" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Interest Rate (% p.a.)</label>
                            <input type="number" step="0.01" min="0" max="100" id="interest_input" name="interest_rate" value="0" class="form-control" placeholder="0 for interest-free">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">First Deduction Month <span class="text-danger">*</span></label>
                            <input type="month" name="start_month" class="form-control" value="{{ date('Y-m') }}" required>
                            <small class="text-muted">Payroll month when EMI deductions start</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Disbursing Bank / Cash Account <span class="text-danger">*</span></label>
                            <select name="disbursed_ledger_id" class="form-select" required>
                                @foreach($bankLedgers as $bl)
                                    <option value="{{ $bl->id }}">{{ $bl->name }} (₹{{ number_format($bl->current_balance, 2) }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="alert alert-info py-2 mb-0 small">
                        <div class="fw-bold mb-1"><i class="bi bi-info-circle me-1"></i> Loan & Accounting Workflow:</div>
                        <ul class="mb-0 ps-3">
                            <li>A payment journal entry is automatically posted: <strong>Debit Staff Loan Asset</strong> and <strong>Credit Bank/Cash</strong>.</li>
                            <li>A monthly installment schedule will be created automatically.</li>
                            <li>Monthly EMIs will be automatically deducted from net payroll during monthly salary generation.</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Sanction Loan & Post Accounts</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

