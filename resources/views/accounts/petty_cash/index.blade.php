@extends('layouts.app')

@section('title', 'Petty Cash Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-cash-stack text-success me-2"></i> Petty Cash Management</h4>
        <p class="text-muted small mb-0">Manage departmental cash floats, disburse daily vouchers, track live balances, and automate replenishments.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('accounts.petty_cash.daybook') }}" class="btn btn-outline-secondary">
            <i class="bi bi-book me-1"></i> Cash Register / Daybook
        </a>
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#newAccountModal">
            <i class="bi bi-wallet2 me-1"></i> New Cash Account
        </button>
        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#replenishModal">
            <i class="bi bi-arrow-repeat me-1"></i> Replenish Float
        </button>
        <a href="{{ route('accounts.petty_cash.vouchers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> Record Voucher
        </a>
    </div>
</div>

<!-- Quick Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-primary border-4">
            <div class="text-muted small text-uppercase fw-semibold">Total Float Limit</div>
            <div class="fs-4 fw-bold text-dark mt-1">₹ {{ number_format($stats['total_float'], 2) }}</div>
            <div class="small text-muted">Across {{ count($accounts) }} active accounts</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-success border-4">
            <div class="text-muted small text-uppercase fw-semibold">Current Cash in Hand</div>
            <div class="fs-4 fw-bold text-success mt-1">₹ {{ number_format($stats['total_current_cash'], 2) }}</div>
            <div class="small text-muted">Live unspent float balance</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-info border-4">
            <div class="text-muted small text-uppercase fw-semibold">Spent This Month</div>
            <div class="fs-4 fw-bold text-dark mt-1">₹ {{ number_format($stats['month_spent'], 2) }}</div>
            <div class="small text-muted">{{ now()->format('F Y') }} disbursements</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-{{ $stats['low_balance_count'] > 0 ? 'danger' : 'secondary' }} border-4">
            <div class="text-muted small text-uppercase fw-semibold">Low Balance Alerts</div>
            <div class="fs-4 fw-bold {{ $stats['low_balance_count'] > 0 ? 'text-danger' : 'text-dark' }} mt-1">{{ $stats['low_balance_count'] }}</div>
            <div class="small text-muted">Floats &lt; 20% limit</div>
        </div>
    </div>
</div>

<!-- Petty Cash Accounts Grid -->
<div class="mb-4">
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h6 class="fw-bold text-dark mb-0"><i class="bi bi-grid me-1"></i> Departmental Cash Accounts</h6>
        <button class="btn btn-sm btn-link text-decoration-none" data-bs-toggle="modal" data-bs-target="#newCategoryModal">
            <i class="bi bi-tags me-1"></i> Manage Expense Categories ({{ count($categories) }})
        </button>
    </div>
    <div class="row g-3">
        @forelse($accounts as $acc)
            @php
                $pct = $acc->float_limit > 0 ? min(100, round(($acc->current_balance / $acc->float_limit) * 100)) : 0;
                $barColor = $pct > 50 ? 'bg-success' : ($pct > 20 ? 'bg-warning' : 'bg-danger');
            @endphp
            <div class="col-md-4">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h6 class="fw-bold mb-0 text-primary">{{ $acc->name }}</h6>
                                <small class="text-muted">{{ $acc->code }} | Branch: {{ $acc->branch->name ?? 'Head Office' }}</small>
                            </div>
                            <span class="badge bg-light text-dark border">{{ $pct }}% left</span>
                        </div>
                        <div class="mb-2">
                            <span class="text-muted small">Custodian:</span>
                            <strong class="small">{{ $acc->custodian->user->name ?? $acc->custodian->staff_code ?? 'Central Cashier' }}</strong>
                        </div>
                        <div class="d-flex justify-content-between align-items-end mb-1">
                            <div>
                                <div class="text-muted small">Available Cash:</div>
                                <div class="fs-5 fw-bold {{ $pct <= 20 ? 'text-danger' : 'text-success' }}">
                                    ₹ {{ number_format($acc->current_balance, 2) }}
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="text-muted small">Float Limit:</div>
                                <div class="small fw-semibold text-dark">₹ {{ number_format($acc->float_limit, 2) }}</div>
                            </div>
                        </div>
                        <div class="progress mb-3" style="height: 6px;">
                            <div class="progress-bar {{ $barColor }}" style="width: {{ $pct }}%;"></div>
                        </div>
                        <div class="d-flex gap-2">
                            <a href="{{ route('accounts.petty_cash.vouchers.create', ['account_id' => $acc->id]) }}" class="btn btn-sm btn-outline-primary flex-fill">
                                <i class="bi bi-dash-circle me-1"></i> Pay Voucher
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-success flex-fill replenish-trigger" data-acc-id="{{ $acc->id }}" data-acc-name="{{ $acc->name }}" data-acc-balance="{{ $acc->current_balance }}" data-acc-limit="{{ $acc->float_limit }}">
                                <i class="bi bi-arrow-repeat me-1"></i> Top-up
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="card border-0 shadow-sm p-4 text-center text-muted">
                    <i class="bi bi-wallet2 fs-1 d-block mb-2 text-secondary"></i>
                    No petty cash accounts configured yet.<br>
                    <button type="button" class="btn btn-sm btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#newAccountModal">
                        <i class="bi bi-plus-circle me-1"></i> Create First Cash Float
                    </button>
                </div>
            </div>
        @endforelse
    </div>
</div>

<!-- Recent Vouchers Listing -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-receipt me-1"></i> Recent Petty Cash Expense Vouchers</h6>
        <div class="d-flex gap-2">
            <form method="GET" action="{{ route('accounts.petty_cash.index') }}" class="d-flex gap-2">
                <select name="account_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">All Accounts</option>
                    @foreach($accounts as $a)
                        <option value="{{ $a->id }}" {{ request('account_id') == $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                    @endforeach
                </select>
            </form>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase">
                <tr>
                    <th style="width: 14%;">Voucher #</th>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 16%;">Account</th>
                    <th style="width: 16%;">Category</th>
                    <th style="width: 16%;">Paid To</th>
                    <th class="text-end" style="width: 12%;">Amount (₹)</th>
                    <th class="text-center" style="width: 7%;">Receipt</th>
                    <th class="text-center" style="width: 7%;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($vouchers as $v)
                    <tr>
                        <td>
                            <a href="{{ route('accounts.petty_cash.vouchers.show', $v) }}" class="fw-bold text-decoration-none">
                                {{ $v->voucher_no }}
                            </a>
                            @if($v->journalEntry)
                                <div class="small">
                                    <a href="{{ route('accounts.vouchers.show', $v->journalEntry) }}" class="text-muted text-decoration-none">
                                        <i class="bi bi-journal-text me-1"></i>{{ $v->journalEntry->voucher_no }}
                                    </a>
                                </div>
                            @endif
                        </td>
                        <td>{{ $v->voucher_date->format('d M Y') }}</td>
                        <td>
                            <span class="fw-semibold text-dark">{{ $v->account->name }}</span>
                        </td>
                        <td>
                            <span class="badge bg-light text-primary border">{{ $v->expense_category }}</span>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $v->paid_to }}</div>
                            <small class="text-muted text-truncate d-block" style="max-width: 160px;">{{ $v->remarks }}</small>
                        </td>
                        <td class="text-end fw-bold text-danger">
                            ₹ {{ number_format($v->amount, 2) }}
                        </td>
                        <td class="text-center">
                            @if($v->receipt_doc)
                                <a href="{{ asset('storage/' . $v->receipt_doc) }}" target="_blank" class="badge bg-light text-success border text-decoration-none" title="View attached bill">
                                    <i class="bi bi-paperclip"></i> Bill
                                </a>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('accounts.petty_cash.vouchers.show', $v) }}" class="btn btn-outline-primary" title="View Details">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('accounts.petty_cash.vouchers.print', $v) }}" target="_blank" class="btn btn-outline-dark" title="Print Receipt Voucher">
                                    <i class="bi bi-printer"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-receipt fs-1 d-block mb-2 text-secondary"></i>
                            No expense vouchers recorded yet. Click <strong>"Record Voucher"</strong> to enter an expense.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($vouchers->hasPages())
        <div class="card-footer bg-white py-2">
            {{ $vouchers->links() }}
        </div>
    @endif
</div>

<!-- Modal 1: New Account -->
<div class="modal fade" id="newAccountModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('accounts.petty_cash.accounts.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-wallet2 me-2"></i> New Petty Cash Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Account Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Head Office Admin Cash / Factory Cashier" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Branch</label>
                            <select name="branch_id" class="form-select form-select-sm">
                                <option value="">Head Office / All Branches</option>
                                @foreach($branches as $b)
                                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Custodian Staff</label>
                            <select name="custodian_staff_id" class="form-select form-select-sm">
                                <option value="">Select Staff</option>
                                @foreach($staffMembers as $s)
                                    <option value="{{ $s->id }}">{{ $s->user->name ?? $s->first_name }} ({{ $s->staff_code }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Float Limit (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" name="float_limit" class="form-control" placeholder="e.g. 10000.00" required>
                            <small class="text-muted">Maximum sanctioned float.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Initial Cash in Hand (₹)</label>
                            <input type="number" step="0.01" min="0" name="opening_balance" class="form-control" value="0.00">
                            <small class="text-muted">Starting cash balance.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Create Account</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: New Category -->
<div class="modal fade" id="newCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('accounts.petty_cash.categories.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-tag me-2"></i> New Expense Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Category Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. Client Refreshments, Emergency Tools" required>
                    </div>
                    <div class="p-2 bg-light rounded border small text-muted">
                        An accounting expense ledger will be automatically created and mapped to this category under Administrative & Indirect Expenses.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Replenish Float -->
<div class="modal fade" id="replenishModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('accounts.petty_cash.replenish.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-arrow-repeat me-2"></i> Replenish Petty Cash Float</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Petty Cash Account <span class="text-danger">*</span></label>
                        <select name="petty_cash_account_id" id="replenishAccountId" class="form-select" required>
                            @foreach($accounts as $acc)
                                <option value="{{ $acc->id }}">
                                    {{ $acc->name }} (Available: ₹{{ number_format($acc->current_balance, 2) }} / Limit: ₹{{ number_format($acc->float_limit, 2) }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Amount to Top-Up (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="replenishAmount" class="form-control" placeholder="e.g. 5000.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="replenishment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Disbursed From (Bank / Main Cash) <span class="text-danger">*</span></label>
                        <select name="bank_ledger_id" class="form-select" required>
                            @foreach($bankLedgers as $bl)
                                <option value="{{ $bl->id }}">{{ $bl->name }} (Balance: ₹{{ number_format($bl->current_balance, 2) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Cheque / UTR / Reference #</label>
                        <input type="text" name="transaction_reference" class="form-control form-control-sm" placeholder="e.g. CHQ-991201 or Self Cash Withdrawal">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Notes</label>
                        <textarea name="notes" class="form-control form-control-sm" rows="2" placeholder="Float replenishment purpose..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4">Disburse & Post Contra Voucher</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.replenish-trigger').forEach(btn => {
        btn.addEventListener('click', function() {
            const accId = this.dataset.accId;
            const balance = parseFloat(this.dataset.accBalance) || 0;
            const limit = parseFloat(this.dataset.accLimit) || 0;
            const needed = Math.max(0, limit - balance);

            const select = document.getElementById('replenishAccountId');
            if (select) select.value = accId;

            const amtInput = document.getElementById('replenishAmount');
            if (amtInput) amtInput.value = needed > 0 ? needed.toFixed(2) : '1000.00';

            const modal = new bootstrap.Modal(document.getElementById('replenishModal'));
            modal.show();
        });
    });
});
</script>
@endsection
