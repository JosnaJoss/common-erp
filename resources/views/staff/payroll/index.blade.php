@extends('layouts.app')

@section('title', 'Payroll Register & Processing')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-wallet2 text-primary me-2"></i> Monthly Staff Payroll</h4>
        <p class="text-muted small mb-0">Salary computations, attendance LOP deductions, loan & advance recoveries, and balanced accounting disbursements.</p>
    </div>
    <div class="d-flex align-items-center gap-2">
        <a href="{{ route('staff.advances.index') }}" class="btn btn-outline-warning btn-sm">
            <i class="bi bi-cash-coin me-1"></i> Advances
        </a>
        <a href="{{ route('staff.loans.index') }}" class="btn btn-outline-info btn-sm">
            <i class="bi bi-bank me-1"></i> Loans
        </a>
        <form method="GET" class="d-flex align-items-center">
            <input type="month" name="month" class="form-control form-control-sm" value="{{ $salaryMonth }}" onchange="this.form.submit()">
        </form>
        <form action="{{ route('staff.payroll.generate') }}" method="POST">
            @csrf
            <input type="hidden" name="salary_month" value="{{ $salaryMonth }}">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-gear-wide-connected me-1"></i> Generate ({{ \Carbon\Carbon::parse($salaryMonth . '-01')->format('M Y') }})
            </button>
        </form>
        @if(($summary['pending_count'] ?? 0) > 0)
            <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#bulkDisburseModal">
                <i class="bi bi-check2-all me-1"></i> Bulk Disburse ({{ $summary['pending_count'] }})
            </button>
        @endif
    </div>
</div>

<!-- Summary Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-2">
        <div class="card border-0 shadow-sm p-3 border-start border-primary border-4">
            <div class="text-muted small text-uppercase fw-semibold">Gross Payroll</div>
            <div class="fs-5 fw-bold text-dark mt-1">₹ {{ number_format($summary['total_gross'], 2) }}</div>
            <div class="small text-muted">{{ count($payrolls) }} staff records</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm p-3 border-start border-danger border-4">
            <div class="text-muted small text-uppercase fw-semibold">LOP Deductions</div>
            <div class="fs-5 fw-bold text-danger mt-1">₹ {{ number_format($summary['total_lop'], 2) }}</div>
            <div class="small text-muted">Attendance loss of pay</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm p-3 border-start border-warning border-4">
            <div class="text-muted small text-uppercase fw-semibold">Advance Recovery</div>
            <div class="fs-5 fw-bold text-dark mt-1">₹ {{ number_format($summary['total_advances'], 2) }}</div>
            <div class="small text-muted">Deducted advances</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm p-3 border-start border-info border-4">
            <div class="text-muted small text-uppercase fw-semibold">Loan EMIs</div>
            <div class="fs-5 fw-bold text-dark mt-1">₹ {{ number_format($summary['total_loans'], 2) }}</div>
            <div class="small text-muted">Monthly installments</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm p-3 border-start border-success border-4">
            <div class="text-muted small text-uppercase fw-semibold">Net Payout</div>
            <div class="fs-5 fw-bold text-success mt-1">₹ {{ number_format($summary['total_net'], 2) }}</div>
            <div class="small text-muted">Total payable</div>
        </div>
    </div>
    <div class="col-md-2">
        <div class="card border-0 shadow-sm p-3 border-start border-secondary border-4">
            <div class="text-muted small text-uppercase fw-semibold">Status</div>
            <div class="fs-6 fw-bold text-dark mt-1">
                <span class="text-success">{{ $summary['paid_count'] }} Paid</span> /
                <span class="text-warning text-dark">{{ $summary['pending_count'] }} Pending</span>
            </div>
            <div class="small text-muted">{{ \Carbon\Carbon::parse($salaryMonth . '-01')->format('M Y') }}</div>
        </div>
    </div>
</div>

<form id="payrollTableForm" action="{{ route('staff.payroll.bulk_pay') }}" method="POST">
    @csrf
    <div class="card card-custom border-0 shadow-sm">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-list-columns-reverse me-1"></i> Salary Register — {{ \Carbon\Carbon::parse($salaryMonth . '-01')->format('F Y') }}</h6>
            </div>
            @if(($summary['pending_count'] ?? 0) > 0)
                <div>
                    <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#bulkDisburseModal">
                        <i class="bi bi-cash-stack me-1"></i> Disburse Selected
                    </button>
                </div>
            @endif
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-3 text-center" style="width: 3%;">
                                <input type="checkbox" id="selectAllCheckbox" class="form-check-input" onclick="toggleSelectAll(this)">
                            </th>
                            <th style="width: 17%;">Employee</th>
                            <th class="text-center" style="width: 10%;">Attendance</th>
                            <th class="text-end" style="width: 10%;">Basic + HRA</th>
                            <th class="text-end" style="width: 10%;">Gross Salary</th>
                            <th class="text-end" style="width: 10%;">LOP Ded.</th>
                            <th class="text-end" style="width: 12%;">Statutory / Adv / Loan</th>
                            <th class="text-end" style="width: 11%;">Net Salary</th>
                            <th class="text-center" style="width: 9%;">Status</th>
                            <th class="text-end pe-3" style="width: 8%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payrolls as $payroll)
                        <tr>
                            <td class="ps-3 text-center">
                                @if($payroll->payroll_status !== 'paid')
                                    <input type="checkbox" name="payroll_ids[]" value="{{ $payroll->id }}" class="form-check-input payroll-checkbox" onchange="updateSelectedCount()">
                                @else
                                    <i class="bi bi-check-circle-fill text-success" title="Disbursed"></i>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $payroll->staff->full_name }}</div>
                                <div class="small text-muted">{{ $payroll->staff->employee_id }} | {{ $payroll->staff->department->name ?? 'Dept' }}</div>
                            </td>
                            <td class="text-center small">
                                <span class="badge bg-light text-dark border">
                                    {{ $payroll->present_days ?? ($payroll->total_days - $payroll->absent_days) }} / {{ $payroll->total_days ?? 30 }}d
                                </span>
                                @if($payroll->absent_days > 0)
                                    <div class="text-danger small" style="font-size: 0.7rem;">{{ $payroll->absent_days }} LOP Days</div>
                                @endif
                            </td>
                            <td class="text-end small">
                                ₹ {{ number_format($payroll->basic_salary + $payroll->hra, 2) }}
                                @if($payroll->overtime_amount > 0)
                                    <div class="text-muted" style="font-size: 0.7rem;">+OT ₹{{ number_format($payroll->overtime_amount, 0) }}</div>
                                @endif
                            </td>
                            <td class="text-end fw-semibold text-dark">₹ {{ number_format($payroll->gross_salary, 2) }}</td>
                            <td class="text-end small {{ $payroll->lop_deduction > 0 ? 'text-danger fw-semibold' : 'text-muted' }}">
                                @if($payroll->lop_deduction > 0)
                                    -₹ {{ number_format($payroll->lop_deduction, 2) }}
                                @else
                                    -
                                @endif
                            </td>
                            <td class="text-end small text-muted">
                                @php
                                    $statutory = $payroll->pf_deduction + $payroll->esi_deduction;
                                @endphp
                                @if($statutory > 0)
                                    <div>PF/ESI: -₹{{ number_format($statutory, 0) }}</div>
                                @endif
                                @if($payroll->advance_deduction > 0)
                                    <div class="text-warning text-dark">Adv: -₹{{ number_format($payroll->advance_deduction, 0) }}</div>
                                @endif
                                @if($payroll->loan_deduction > 0)
                                    <div class="text-info text-dark">Loan: -₹{{ number_format($payroll->loan_deduction, 0) }}</div>
                                @endif
                                @if($statutory == 0 && $payroll->advance_deduction == 0 && $payroll->loan_deduction == 0)
                                    <span>₹0.00</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold text-success fs-6">₹ {{ number_format($payroll->net_salary, 2) }}</td>
                            <td class="text-center">
                                <span class="badge bg-{{ $payroll->payroll_status === 'paid' ? 'success' : 'warning text-dark' }}">
                                    {{ ucfirst($payroll->payroll_status) }}
                                </span>
                                @if($payroll->journalEntry)
                                    <div class="small">
                                        <a href="{{ route('accounts.vouchers.show', $payroll->journalEntry) }}" class="badge bg-light text-primary border text-decoration-none" style="font-size: 0.65rem;">
                                            {{ $payroll->journalEntry->voucher_no }}
                                        </a>
                                    </div>
                                @endif
                            </td>
                            <td class="text-end pe-3">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('staff.payroll.payslip', $payroll) }}" class="btn btn-outline-secondary" target="_blank" title="Print Payslip">
                                        <i class="bi bi-printer"></i>
                                    </a>
                                    @if($payroll->payroll_status !== 'paid')
                                        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#payModal_{{ $payroll->id }}" title="Disburse Single Salary">
                                            <i class="bi bi-cash-coin"></i>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="bi bi-wallet2 fs-1 d-block mb-2 text-secondary"></i>
                                No payroll generated for {{ \Carbon\Carbon::parse($salaryMonth . '-01')->format('F Y') }}. Click "Generate Payroll" above to compute.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal: Bulk Disburse -->
    <div class="modal fade" id="bulkDisburseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cash-stack me-2 text-success"></i> Bulk Salary Disbursement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="bi bi-info-circle me-1"></i> This will mark all selected staff payrolls as <strong>Paid</strong>, settle scheduled advances & loan installments, and generate balanced double-entry payroll payment journal entries.
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Disbursing Bank / Cash Account <span class="text-danger">*</span></label>
                        <select name="payment_ledger_id" class="form-select" required>
                            @foreach($bankLedgers as $l)
                                <option value="{{ $l->id }}">{{ $l->name }} (Balance: ₹{{ number_format($l->current_balance, 2) }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label fw-bold small">Payment Mode <span class="text-danger">*</span></label>
                            <select name="payment_mode" class="form-select" required>
                                <option value="Bank Transfer">Bank Transfer (NEFT/RTGS/IMPS)</option>
                                <option value="Cash">Cash</option>
                                <option value="Cheque">Bank Cheque</option>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label fw-bold small">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small">Transaction Reference / Batch ID</label>
                        <input type="text" name="transaction_reference" class="form-control" placeholder="e.g. BATCH-SAL-{{ date('Ymd') }}">
                    </div>

                    <div class="text-muted small">
                        If no individual checkboxes are checked, all eligible pending payrolls in this month will be disbursed.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4">Disburse & Post JVs</button>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Single Pay Modals -->
@foreach($payrolls as $payroll)
    @if($payroll->payroll_status !== 'paid')
        <div class="modal fade" id="payModal_{{ $payroll->id }}" tabindex="-1">
            <div class="modal-dialog">
                <form action="{{ route('staff.payroll.pay', $payroll) }}" method="POST" class="modal-content text-start">
                    @csrf
                    <div class="modal-header">
                        <h6 class="modal-title fw-bold">Disburse Salary: {{ $payroll->staff->full_name }}</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2 small mb-3">
                            Net Amount to Disburse: <strong class="fs-6">₹ {{ number_format($payroll->net_salary, 2) }}</strong>
                            <div class="small text-muted mt-1">
                                Gross: ₹{{ number_format($payroll->gross_salary, 2) }} |
                                LOP: -₹{{ number_format($payroll->lop_deduction, 2) }} |
                                Advance: -₹{{ number_format($payroll->advance_deduction, 2) }} |
                                Loan: -₹{{ number_format($payroll->loan_deduction, 2) }}
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Disbursement Bank / Cash Account <span class="text-danger">*</span></label>
                            <select name="payment_ledger_id" class="form-select" required>
                                @foreach($bankLedgers as $l)
                                    <option value="{{ $l->id }}">{{ $l->name }} (Bal: ₹{{ number_format($l->current_balance, 2) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Payment Mode <span class="text-danger">*</span></label>
                                <select name="payment_mode" class="form-select" required>
                                    <option value="Bank Transfer">Bank Transfer (NEFT/RTGS)</option>
                                    <option value="Cash">Cash in Hand</option>
                                    <option value="Cheque">Bank Cheque</option>
                                </select>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Payment Date <span class="text-danger">*</span></label>
                                <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Transaction Reference</label>
                            <input type="text" name="transaction_reference" class="form-control" placeholder="NEFT / UTR / Cheque number">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-success">Confirm Disbursement</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endforeach

<script>
function toggleSelectAll(master) {
    const checkboxes = document.querySelectorAll('.payroll-checkbox');
    checkboxes.forEach(cb => cb.checked = master.checked);
}

document.getElementById('payrollTableForm').addEventListener('submit', function(e) {
    const checked = document.querySelectorAll('.payroll-checkbox:checked');
    if (checked.length === 0) {
        // If none explicitly checked, select all pending checkboxes automatically
        document.querySelectorAll('.payroll-checkbox').forEach(cb => cb.checked = true);
    }
});
</script>
@endsection
