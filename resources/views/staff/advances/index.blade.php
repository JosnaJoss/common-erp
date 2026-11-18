@extends('layouts.app')

@section('title', 'Staff Salary Advances')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-cash-coin text-warning me-2"></i> Employee Salary Advances</h4>
        <p class="text-muted small mb-0">Record and disburse interim salary advances and automate recovery through monthly payroll processing.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('staff.payroll.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-wallet2 me-1"></i> Payroll Register
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newAdvanceModal">
            <i class="bi bi-plus-circle me-1"></i> Issue Salary Advance
        </button>
    </div>
</div>

<!-- Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-primary border-4">
            <div class="text-muted small text-uppercase fw-semibold">Total Advances Disbursed</div>
            <div class="fs-4 fw-bold text-dark mt-1">₹ {{ number_format($stats['total_disbursed'], 2) }}</div>
            <div class="small text-muted">Cumulative disbursements</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-success border-4">
            <div class="text-muted small text-uppercase fw-semibold">Total Recovered in Payroll</div>
            <div class="fs-4 fw-bold text-success mt-1">₹ {{ number_format($stats['total_recovered'], 2) }}</div>
            <div class="small text-muted">Deducted from salary</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-danger border-4">
            <div class="text-muted small text-uppercase fw-semibold">Outstanding to Recover</div>
            <div class="fs-4 fw-bold text-danger mt-1">₹ {{ number_format($stats['total_outstanding'], 2) }}</div>
            <div class="small text-muted">Active employee balance</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-info border-4">
            <div class="text-muted small text-uppercase fw-semibold">Active Advances</div>
            <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($stats['active_advances_count']) }}</div>
            <div class="small text-muted">Pending full recovery</div>
        </div>
    </div>
</div>

<!-- Advances Listing Table -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-list-check me-1"></i> Salary Advances Register</h6>
        <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newAdvanceModal">
            <i class="bi bi-plus-lg me-1"></i> New Advance
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase">
                <tr>
                    <th style="width: 14%;">Advance #</th>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 20%;">Employee</th>
                    <th class="text-end" style="width: 13%;">Disbursed (₹)</th>
                    <th class="text-end" style="width: 13%;">Recovered (₹)</th>
                    <th class="text-end" style="width: 13%;">Balance (₹)</th>
                    <th class="text-center" style="width: 8%;">Status</th>
                    <th style="width: 7%;">Voucher</th>
                </tr>
            </thead>
            <tbody>
                @forelse($advances as $adv)
                    <tr>
                        <td>
                            <strong class="text-primary">{{ $adv->advance_no }}</strong>
                        </td>
                        <td>{{ $adv->advance_date->format('d M Y') }}</td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $adv->staff->full_name }}</div>
                            <small class="text-muted">{{ $adv->staff->employee_id }} | {{ $adv->staff->department->name ?? 'Dept' }}</small>
                            <div class="small text-muted text-truncate" style="max-width: 180px;">Reason: {{ $adv->reason }}</div>
                        </td>
                        <td class="text-end fw-bold text-dark">
                            ₹ {{ number_format($adv->amount, 2) }}
                        </td>
                        <td class="text-end fw-semibold text-success">
                            ₹ {{ number_format($adv->recovered_amount, 2) }}
                        </td>
                        <td class="text-end fw-bold {{ $adv->balance_amount > 0 ? 'text-danger' : 'text-muted' }}">
                            ₹ {{ number_format($adv->balance_amount, 2) }}
                        </td>
                        <td class="text-center">
                            @if($adv->balance_amount <= 0)
                                <span class="badge bg-success">Recovered</span>
                            @else
                                <span class="badge bg-warning text-dark">Active</span>
                            @endif
                        </td>
                        <td>
                            @if($adv->journalEntry)
                                <a href="{{ route('accounts.vouchers.show', $adv->journalEntry) }}" class="badge bg-light text-primary border text-decoration-none" title="View Journal Entry">
                                    <i class="bi bi-journal-text me-1"></i> {{ $adv->journalEntry->voucher_no }}
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-cash-coin fs-1 d-block mb-2 text-secondary"></i>
                            No salary advances issued yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($advances->hasPages())
        <div class="card-footer bg-white py-2">
            {{ $advances->links() }}
        </div>
    @endif
</div>

<!-- Modal: New Advance -->
<div class="modal fade" id="newAdvanceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('staff.advances.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cash-coin me-2"></i> Issue Salary Advance</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Employee <span class="text-danger">*</span></label>
                        <select name="staff_id" class="form-select" required>
                            <option value="">-- Choose Employee --</option>
                            @foreach($staffMembers as $s)
                                <option value="{{ $s->id }}">
                                    {{ $s->full_name }} ({{ $s->employee_id }}) — Monthly Basic: ₹{{ number_format($s->basic_salary, 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Advance Date <span class="text-danger">*</span></label>
                            <input type="date" name="advance_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="1" name="amount" class="form-control" placeholder="e.g. 5000.00" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Disbursement Mode <span class="text-danger">*</span></label>
                            <select name="payment_mode" class="form-select" required>
                                <option value="Bank Transfer">Bank Transfer (NEFT/IMPS)</option>
                                <option value="Cash">Cash in Hand</option>
                                <option value="Cheque">Cheque</option>
                                <option value="UPI">UPI</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Disbursing Account <span class="text-danger">*</span></label>
                            <select name="ledger_id" class="form-select" required>
                                @foreach($bankLedgers as $bl)
                                    <option value="{{ $bl->id }}">{{ $bl->name }} (₹{{ number_format($bl->current_balance, 2) }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Cheque / UTR / Reference #</label>
                        <input type="text" name="reference_no" class="form-control form-control-sm" placeholder="e.g. UTR-9988123">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Reason / Purpose <span class="text-danger">*</span></label>
                        <input type="text" name="reason" class="form-control" placeholder="e.g. Medical emergency / Festival advance" required>
                    </div>

                    <div class="p-2 bg-light rounded border small text-muted">
                        On disbursement, a double-entry payment voucher will be posted (Dr Salary Advances Asset, Cr Bank/Cash). The advance will automatically be deducted during the next payroll calculation.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Disburse & Post Accounts</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

