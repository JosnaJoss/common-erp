@extends('layouts.app')

@section('title', 'BRS Workspace - ' . $bankReconciliation->reconciliation_no)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('accounts.bank_reconciliation.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h4 class="fw-bold mb-0">{{ $bankReconciliation->reconciliation_no }} - {{ $bankReconciliation->bankLedger->name }}</h4>
            <div class="small text-muted">
                A/C: {{ $bankReconciliation->bankLedger->bank_account_no ?: 'Main' }} |
                Statement Date: <strong>{{ $bankReconciliation->statement_date->format('d M Y') }}</strong>
            </div>
        </div>
        @if(in_array($bankReconciliation->status, ['completed', 'reconciled']))
            <span class="badge bg-success fs-6"><i class="bi bi-lock-fill me-1"></i> Reconciled & Locked</span>
        @else
            <span class="badge bg-warning text-dark fs-6"><i class="bi bi-clock-history me-1"></i> In Progress</span>
        @endif
    </div>

    <div class="d-flex gap-2">
        @if(!in_array($bankReconciliation->status, ['completed', 'reconciled']))
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importStatementModal">
                <i class="bi bi-file-earmark-arrow-up me-1"></i> Import Statement CSV
            </button>
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#recordAdjustmentModal">
                <i class="bi bi-plus-circle me-1"></i> Record Bank Adjustment
            </button>
            <form method="POST" action="{{ route('accounts.bank_reconciliation.complete', $bankReconciliation) }}" onsubmit="return confirm('Are you sure you want to finalize and lock this bank reconciliation?');">
                @csrf
                <button type="submit" class="btn btn-success" {{ abs($summary['difference']) > 0.01 ? 'disabled title="Difference must be zero to finalize"' : '' }}>
                    <i class="bi bi-check2-circle me-1"></i> Complete & Finalize
                </button>
            </form>
        @endif
        <a href="{{ route('accounts.bank_reconciliation.report', $bankReconciliation) }}" class="btn btn-dark">
            <i class="bi bi-file-earmark-text me-1"></i> BRS Report
        </a>
    </div>
</div>

<!-- Financial Reconciliation Summary Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <div class="row text-center g-3">
            <div class="col-md-2 border-end">
                <div class="text-muted small text-uppercase fw-semibold">Statement Balance</div>
                <div class="fs-5 fw-bold text-dark mt-1">₹ {{ number_format($summary['statement_balance'], 2) }}</div>
                <small class="text-muted">Target Bank Ending</small>
            </div>
            <div class="col-md-2 border-end">
                <div class="text-muted small text-uppercase fw-semibold">Ledger Book Balance</div>
                <div class="fs-5 fw-bold text-dark mt-1">₹ {{ number_format($summary['book_balance'], 2) }}</div>
                <small class="text-muted">As of statement date</small>
            </div>
            <div class="col-md-3 border-end">
                <div class="text-muted small text-uppercase fw-semibold">+ Unpresented Cheques</div>
                <div class="fs-5 fw-bold text-primary mt-1">₹ {{ number_format($summary['unpresented_cheques'], 2) }}</div>
                <small class="text-muted">Issued but not in bank</small>
            </div>
            <div class="col-md-3 border-end">
                <div class="text-muted small text-uppercase fw-semibold">- Uncleared Deposits</div>
                <div class="fs-5 fw-bold text-danger mt-1">₹ {{ number_format($summary['uncleared_deposits'], 2) }}</div>
                <small class="text-muted">Deposited but not in bank</small>
            </div>
            <div class="col-md-2">
                <div class="text-muted small text-uppercase fw-semibold">Variance / Difference</div>
                <div class="fs-5 fw-bold mt-1 {{ $summary['is_balanced'] ? 'text-success' : 'text-danger' }}">
                    ₹ {{ number_format($summary['difference'], 2) }}
                </div>
                <small class="{{ $summary['is_balanced'] ? 'text-success' : 'text-danger' }}">
                    {{ $summary['is_balanced'] ? '✓ Reconciled (Balanced)' : 'Out of Balance' }}
                </small>
            </div>
        </div>
    </div>
</div>

<!-- D4. Auto-Matching Suggestions Banner -->
@if(count($suggestions) > 0 && !in_array($bankReconciliation->status, ['completed', 'reconciled']))
    <div class="card border-primary border-2 shadow-sm mb-4 bg-light">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="fw-bold text-primary mb-0">
                    <i class="bi bi-magic me-1"></i> Auto-Matching Suggestions Detected ({{ count($suggestions) }} candidates)
                </h6>
                <small class="text-muted">Matched by exact amount & date proximity</small>
            </div>
            <div class="table-responsive">
                <table class="table table-sm table-bordered bg-white align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th>Confidence</th>
                            <th>Imported Statement Entry</th>
                            <th>ERP Book Transaction</th>
                            <th>Match Reason</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($suggestions as $s)
                            <tr>
                                <td><span class="badge bg-{{ $s['confidence'] === 'High' ? 'success' : 'info text-dark' }}">{{ $s['confidence'] }}</span></td>
                                <td>
                                    <strong>{{ \Carbon\Carbon::parse($s['statement_item']->transaction_date)->format('d M') }}</strong>:
                                    {{ Str::limit($s['statement_item']->description, 28) }}
                                    <span class="fw-bold text-{{ $s['statement_item']->credit_amount > 0 ? 'success' : 'danger' }}">
                                        (₹{{ number_format(max($s['statement_item']->credit_amount, $s['statement_item']->debit_amount), 2) }})
                                    </span>
                                </td>
                                <td>
                                    <strong>{{ \Carbon\Carbon::parse($s['book_item']->transaction_date)->format('d M') }}</strong>:
                                    {{ Str::limit($s['book_item']->description, 28) }}
                                    <span class="badge bg-light text-dark border">{{ $s['book_item']->cheque_reference }}</span>
                                </td>
                                <td><small class="text-muted">{{ $s['reason'] }}</small></td>
                                <td class="text-center">
                                    <form method="POST" action="{{ route('accounts.bank_reconciliation.match', $bankReconciliation) }}">
                                        @csrf
                                        <input type="hidden" name="book_item_id" value="{{ $s['book_item']->id }}">
                                        <input type="hidden" name="statement_item_id" value="{{ $s['statement_item']->id }}">
                                        <button type="submit" class="btn btn-sm btn-outline-success py-0 px-2">
                                            <i class="bi bi-check-lg me-1"></i> Accept Match
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

<!-- Two-Panel Reconciliation Workspace -->
<div class="row g-3">
    <!-- Left Panel: ERP Book Entries -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-journal-bookmark me-1"></i> ERP Ledger Transactions</h6>
                    <small class="text-muted">{{ count($bookItems) }} entries recorded in company books</small>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
                    <table class="table table-hover table-sm align-middle mb-0" id="bookItemsTable">
                        <thead class="table-light small sticky-top">
                            <tr>
                                <th style="width: 14%;">Date</th>
                                <th style="width: 20%;">Ref / Chq</th>
                                <th style="width: 26%;">Description</th>
                                <th class="text-end" style="width: 16%;">Debit (₹)</th>
                                <th class="text-end" style="width: 16%;">Credit (₹)</th>
                                <th class="text-center" style="width: 8%;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($bookItems as $bk)
                                <tr class="{{ $bk->is_cleared ? 'table-light text-muted' : '' }}">
                                    <td>{{ $bk->transaction_date->format('d M') }}</td>
                                    <td>
                                        <span class="badge bg-light text-dark border">{{ $bk->cheque_reference }}</span>
                                    </td>
                                    <td>
                                        <div class="small fw-semibold text-truncate" style="max-width: 180px;" title="{{ $bk->description }}">
                                            {{ $bk->description }}
                                        </div>
                                    </td>
                                    <td class="text-end fw-semibold {{ $bk->debit_amount > 0 ? 'text-success' : 'text-muted' }}">
                                        {{ $bk->debit_amount > 0 ? '₹ ' . number_format($bk->debit_amount, 2) : '-' }}
                                    </td>
                                    <td class="text-end fw-semibold {{ $bk->credit_amount > 0 ? 'text-danger' : 'text-muted' }}">
                                        {{ $bk->credit_amount > 0 ? '₹ ' . number_format($bk->credit_amount, 2) : '-' }}
                                    </td>
                                    <td class="text-center">
                                        @if($bk->is_cleared)
                                            @if($bk->matched_item_id)
                                                <form method="POST" action="{{ route('accounts.bank_reconciliation.unmatch', $bankReconciliation) }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="item_id" value="{{ $bk->id }}">
                                                    <button type="submit" class="btn btn-sm btn-link p-0 text-success" title="Matched to statement. Click to unmatch.">
                                                        <i class="bi bi-link-45deg fs-5"></i>
                                                    </button>
                                                </form>
                                            @else
                                                <form method="POST" action="{{ route('accounts.bank_reconciliation.clear', [$bankReconciliation, $bk]) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-link p-0 text-success" title="Cleared manually. Click to mark uncleared.">
                                                        <i class="bi bi-check-circle-fill fs-6"></i>
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            @if(!in_array($bankReconciliation->status, ['completed', 'reconciled']))
                                                <form method="POST" action="{{ route('accounts.bank_reconciliation.clear', [$bankReconciliation, $bk]) }}" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary py-0 px-1" title="Mark Cleared">
                                                        Clear
                                                    </button>
                                                </form>
                                            @else
                                                <span class="badge bg-secondary">Uncleared</span>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">No ERP transactions found for this bank ledger up to statement date.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Panel: Imported Bank Statement Entries -->
    <div class="col-lg-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-receipt-cutoff me-1"></i> Imported Bank Statement Entries</h6>
                    <small class="text-muted">{{ count($statementItems) }} statement lines imported</small>
                </div>
                @if(count($statementItems) == 0 && !in_array($bankReconciliation->status, ['completed', 'reconciled']))
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#importStatementModal">
                        <i class="bi bi-upload me-1"></i> Upload CSV
                    </button>
                @endif
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 550px; overflow-y: auto;">
                    <table class="table table-hover table-sm align-middle mb-0">
                        <thead class="table-light small sticky-top">
                            <tr>
                                <th style="width: 14%;">Date</th>
                                <th style="width: 20%;">Ref #</th>
                                <th style="width: 26%;">Description</th>
                                <th class="text-end" style="width: 16%;">Withdrawal (₹)</th>
                                <th class="text-end" style="width: 16%;">Deposit (₹)</th>
                                <th class="text-center" style="width: 8%;">Match</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($statementItems as $st)
                                <tr class="{{ $st->is_cleared ? 'table-light text-muted' : '' }}">
                                    <td>{{ $st->transaction_date->format('d M') }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $st->cheque_reference }}</span></td>
                                    <td>
                                        <div class="small fw-semibold text-truncate" style="max-width: 180px;" title="{{ $st->description }}">
                                            {{ $st->description }}
                                        </div>
                                    </td>
                                    <td class="text-end fw-semibold {{ $st->debit_amount > 0 ? 'text-danger' : 'text-muted' }}">
                                        {{ $st->debit_amount > 0 ? '₹ ' . number_format($st->debit_amount, 2) : '-' }}
                                    </td>
                                    <td class="text-end fw-semibold {{ $st->credit_amount > 0 ? 'text-success' : 'text-muted' }}">
                                        {{ $st->credit_amount > 0 ? '₹ ' . number_format($st->credit_amount, 2) : '-' }}
                                    </td>
                                    <td class="text-center">
                                        @if($st->is_cleared)
                                            <form method="POST" action="{{ route('accounts.bank_reconciliation.unmatch', $bankReconciliation) }}" class="d-inline">
                                                @csrf
                                                <input type="hidden" name="item_id" value="{{ $st->id }}">
                                                <button type="submit" class="btn btn-sm btn-link p-0 text-success" title="Matched. Click to unmatch.">
                                                    <i class="bi bi-link-45deg fs-5"></i>
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" class="btn btn-sm btn-outline-primary py-0 px-1 manual-match-btn" data-stmt-id="{{ $st->id }}" data-stmt-desc="{{ $st->description }}" data-stmt-amount="{{ max($st->debit_amount, $st->credit_amount) }}">
                                                Match
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <i class="bi bi-cloud-arrow-up fs-2 d-block mb-2 text-secondary"></i>
                                        No bank statement imported yet.<br>
                                        <button type="button" class="btn btn-sm btn-primary mt-2" data-bs-toggle="modal" data-bs-target="#importStatementModal">
                                            <i class="bi bi-file-earmark-arrow-up me-1"></i> Import Statement CSV
                                        </button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal 1: Import Statement CSV -->
<div class="modal fade" id="importStatementModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('accounts.bank_reconciliation.import', $bankReconciliation) }}" enctype="multipart/form-data">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-arrow-up me-2"></i> Import Bank Statement CSV</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select CSV File <span class="text-danger">*</span></label>
                        <input type="file" name="statement_csv" class="form-control" accept=".csv,.txt" required>
                    </div>
                    <div class="p-3 bg-light rounded border">
                        <h6 class="fw-bold small text-primary mb-1">Standard CSV Columns Supported:</h6>
                        <ul class="small text-muted mb-0 ps-3">
                            <li><strong>Date</strong> (e.g. 2026-11-15 or 15/11/2026)</li>
                            <li><strong>Narration / Description</strong></li>
                            <li><strong>Cheque / Reference Number</strong></li>
                            <li><strong>Withdrawal / Debit Amount</strong></li>
                            <li><strong>Deposit / Credit Amount</strong></li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Upload & Parse</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Record Direct Adjustment -->
<div class="modal fade" id="recordAdjustmentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('accounts.bank_reconciliation.adjustment', $bankReconciliation) }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2"></i> Record Direct Bank Adjustment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Adjustment Type <span class="text-danger">*</span></label>
                        <select name="adjustment_type" class="form-select" required>
                            <option value="bank_charges">Bank Charges / Processing Fee (Dr Expense, Cr Bank)</option>
                            <option value="interest_received">Interest Received (Dr Bank, Cr Income)</option>
                            <option value="direct_debit">Direct Debit by Bank / Auto-Debit (Dr Expense, Cr Bank)</option>
                            <option value="direct_deposit">Direct Customer Deposit / Remittance (Dr Bank, Cr Income)</option>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="e.g. 250.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="adjustment_date" class="form-control" value="{{ $bankReconciliation->statement_date->format('Y-m-d') }}" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Narration / Remarks <span class="text-danger">*</span></label>
                        <input type="text" name="narration" class="form-control" placeholder="e.g. Monthly Bank Service Charges" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Post Voucher & Reconcile</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 3: Manual Match Picker -->
<div class="modal fade" id="manualMatchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('accounts.bank_reconciliation.match', $bankReconciliation) }}">
                @csrf
                <input type="hidden" name="statement_item_id" id="manualStmtId">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-link-45deg me-2"></i> Match Statement Item to ERP Entry</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="p-2 mb-3 bg-light rounded border">
                        <small class="text-muted d-block">Selected Statement Item:</small>
                        <strong id="manualStmtDesc"></strong>
                        <div class="text-primary fw-bold" id="manualStmtAmount"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Choose Corresponding ERP Book Entry <span class="text-danger">*</span></label>
                        <select name="book_item_id" class="form-select" required>
                            <option value="">-- Select ERP Transaction --</option>
                            @foreach($bookItems->where('is_cleared', false) as $bk)
                                <option value="{{ $bk->id }}">
                                    {{ $bk->transaction_date->format('d M') }} | Ref: {{ $bk->cheque_reference }} | {{ Str::limit($bk->description, 25) }} | ₹{{ number_format(max($bk->debit_amount, $bk->credit_amount), 2) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success px-4">Confirm Match</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.manual-match-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.getElementById('manualStmtId').value = this.dataset.stmtId;
            document.getElementById('manualStmtDesc').innerText = this.dataset.stmtDesc;
            document.getElementById('manualStmtAmount').innerText = 'Amount: ₹' + parseFloat(this.dataset.stmtAmount).toFixed(2);
            const modal = new bootstrap.Modal(document.getElementById('manualMatchModal'));
            modal.show();
        });
    });
});
</script>
@endsection
