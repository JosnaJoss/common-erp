@extends('layouts.app')

@section('title', 'Payroll Processing')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Monthly Staff Payroll</h4>
        <p class="text-muted small mb-0">Salary computations, PF/ESI deductions, overtime payouts, and disbursement vouchers.</p>
    </div>
    <div class="d-flex gap-2">
        <form method="GET" class="d-flex align-items-center gap-2">
            <input type="month" name="month" class="form-control form-control-sm" value="{{ $salaryMonth }}" onchange="this.form.submit()">
        </form>
        <form action="{{ route('staff.payroll.generate') }}" method="POST">
            @csrf
            <input type="hidden" name="salary_month" value="{{ $salaryMonth }}">
            <button type="submit" class="btn btn-primary btn-sm">
                <i class="bi bi-gear-wide-connected me-1"></i> Generate Payroll for {{ \Carbon\Carbon::parse($salaryMonth . '-01')->format('M Y') }}
            </button>
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small">
                    <tr>
                        <th class="ps-4">Employee</th>
                        <th>Department</th>
                        <th class="text-end">Basic + HRA</th>
                        <th class="text-end">Allowances + OT</th>
                        <th class="text-end">Gross</th>
                        <th class="text-end">Deductions (PF/ESI)</th>
                        <th class="text-end">Net Salary</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payrolls as $payroll)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">{{ $payroll->staff->full_name }}</div>
                            <span class="badge bg-light text-primary border" style="font-size: 0.7rem;">{{ $payroll->staff->employee_id }}</span>
                        </td>
                        <td class="small text-muted">{{ $payroll->staff->department->name ?? 'N/A' }}</td>
                        <td class="text-end small">₹{{ number_format($payroll->basic_salary + $payroll->hra, 2) }}</td>
                        <td class="text-end small">₹{{ number_format($payroll->other_allowances + $payroll->overtime_amount, 2) }}</td>
                        <td class="text-end fw-semibold">₹{{ number_format($payroll->gross_salary, 2) }}</td>
                        <td class="text-end text-danger small">
                            -₹{{ number_format($payroll->total_deductions, 2) }}
                            @if($payroll->pf_deduction > 0)
                            <div style="font-size: 0.65rem;" class="text-muted">PF: ₹{{ number_format($payroll->pf_deduction, 0) }}</div>
                            @endif
                        </td>
                        <td class="text-end fw-bold text-success fs-6">₹{{ number_format($payroll->net_salary, 2) }}</td>
                        <td class="text-center">
                            <span class="badge bg-{{ $payroll->payroll_status === 'paid' ? 'success' : 'warning text-dark' }}">
                                {{ ucfirst($payroll->payroll_status) }}
                            </span>
                            @if($payroll->journalEntry)
                            <div class="small text-muted" style="font-size: 0.7rem;">{{ $payroll->journalEntry->voucher_no }}</div>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('staff.payroll.payslip', $payroll) }}" class="btn btn-sm btn-outline-secondary" target="_blank" title="Print Payslip">
                                <i class="bi bi-printer"></i>
                            </a>
                            @if($payroll->payroll_status !== 'paid')
                            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#payModal_{{ $payroll->id }}" title="Disburse & Post Accounts">
                                <i class="bi bi-cash-coin me-1"></i> Pay
                            </button>

                            <!-- Pay Modal -->
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
                                                Net Amount to Disburse: <strong class="fs-6">₹{{ number_format($payroll->net_salary, 2) }}</strong>
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
                                                    <label class="form-label small fw-semibold">Payment Mode</label>
                                                    <select name="payment_mode" class="form-select">
                                                        <option value="Bank Transfer">Bank Transfer (NEFT/RTGS)</option>
                                                        <option value="Cash">Cash in Hand</option>
                                                        <option value="Cheque">Bank Cheque</option>
                                                    </select>
                                                </div>
                                                <div class="col-6">
                                                    <label class="form-label small fw-semibold">Payment Date</label>
                                                    <input type="date" name="payment_date" class="form-control" value="{{ now()->toDateString() }}" required>
                                                </div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label small fw-semibold">Transaction Reference</label>
                                                <input type="text" name="transaction_reference" class="form-control" placeholder="NEFT / UTR number">
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
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            No payroll generated for {{ \Carbon\Carbon::parse($salaryMonth . '-01')->format('F Y') }}. Click "Generate Payroll" above to compute.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

