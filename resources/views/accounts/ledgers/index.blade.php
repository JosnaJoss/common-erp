@extends('layouts.app')

@section('title', 'Ledger Master')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">General Ledgers</h4>
        <p class="text-muted small mb-0">Master chart of accounts ledgers: Bank accounts, Cash, Customers, Vendors, and Expenses</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('accounts.chart_of_accounts') }}" class="btn btn-outline-secondary">
            <i class="bi bi-diagram-3 me-1"></i> Chart of Accounts Tree
        </a>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newLedgerModal">
            <i class="bi bi-plus-lg me-1"></i> New Ledger
        </button>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('accounts.ledgers.index') }}" class="row g-2 align-items-end">
            <div class="col-md-5">
                <label class="form-label small fw-bold">Search Ledger Name / Code / Account #</label>
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search by name, bank account..." value="{{ request('search') }}">
            </div>
            <div class="col-md-4">
                <label class="form-label small fw-bold">Filter by Account Group</label>
                <select name="group_id" class="form-select form-select-sm">
                    <option value="">All Account Groups</option>
                    @foreach($groups as $grp)
                        <option value="{{ $grp->id }}" {{ request('group_id') == $grp->id ? 'selected' : '' }}>
                            {{ $grp->name }} ({{ $grp->nature }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-filter me-1"></i> Filter</button>
                <a href="{{ route('accounts.ledgers.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Ledgers Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Ledger Name</th>
                        <th>Account Group</th>
                        <th>Nature</th>
                        <th class="text-end">Opening Balance</th>
                        <th class="text-end">Current Balance</th>
                        <th>Type</th>
                        <th>Bank / Cash</th>
                        <th>Status</th>
                        <th class="text-center">Statement</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ledgers as $ledger)
                        <tr>
                            <td>
                                <a href="{{ route('accounts.ledgers.statement', $ledger) }}" class="fw-bold text-decoration-none text-dark">
                                    {{ $ledger->name }}
                                </a>
                                @if($ledger->code)
                                    <small class="text-muted d-block">Code: {{ $ledger->code }}</small>
                                @endif
                            </td>
                            <td>{{ $ledger->accountGroup->name ?? 'General' }}</td>
                            <td>
                                @php $nature = $ledger->accountGroup->nature ?? 'Assets'; @endphp
                                <span class="badge bg-{{ $nature === 'Assets' ? 'primary' : ($nature === 'Liabilities' ? 'danger' : ($nature === 'Revenue' ? 'success' : 'warning')) }}">
                                    {{ $nature }}
                                </span>
                            </td>
                            <td class="text-end text-muted">₹ {{ number_format($ledger->opening_balance, 2) }}</td>
                            <td class="text-end fw-bold {{ $ledger->balance_type === 'debit' ? 'text-primary' : 'text-danger' }} fs-6">
                                ₹ {{ number_format($ledger->current_balance, 2) }}
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ strtoupper($ledger->balance_type) }}</span></td>
                            <td>
                                @if($ledger->is_bank_or_cash)
                                    <span class="badge bg-info text-dark"><i class="bi bi-bank me-1"></i> {{ $ledger->bank_name ?? 'Bank/Cash' }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge bg-{{ $ledger->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($ledger->status) }}</span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('accounts.ledgers.statement', $ledger) }}" class="btn btn-sm btn-outline-primary" title="View Statement">
                                    <i class="bi bi-journal-text me-1"></i> Passbook
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-journal-x fs-2 d-block mb-2"></i> No ledgers found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($ledgers->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $ledgers->withQueryString()->links() }}
        </div>
    @endif
</div>

<!-- New Ledger Modal -->
<div class="modal fade" id="newLedgerModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('accounts.ledgers.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-plus-circle me-2 text-primary"></i> Create General Ledger</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Account Group <span class="text-danger">*</span></label>
                            <select name="account_group_id" class="form-select" required>
                                <option value="">Select Group</option>
                                @foreach($groups as $grp)
                                    <option value="{{ $grp->id }}">{{ $grp->name }} ({{ $grp->nature }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Ledger Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="e.g. ICICI Current Account" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Ledger Code</label>
                            <input type="text" name="code" class="form-control" placeholder="e.g. LEDG-001">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Opening Balance (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="opening_balance" class="form-control" value="0.00" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Balance Type <span class="text-danger">*</span></label>
                            <select name="balance_type" class="form-select" required>
                                <option value="debit" selected>Debit (Dr - Asset / Expense)</option>
                                <option value="credit">Credit (Cr - Liability / Income)</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" name="is_bank_or_cash" value="1" id="isBankCheck">
                                <label class="form-check-label fw-bold" for="isBankCheck">Is Bank or Cash Account?</label>
                            </div>
                        </div>

                        <div class="col-md-6 bank-field">
                            <label class="form-label">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control" placeholder="e.g. HDFC Bank">
                        </div>
                        <div class="col-md-6 bank-field">
                            <label class="form-label">Bank Account Number</label>
                            <input type="text" name="bank_account_no" class="form-control" placeholder="Account Number">
                        </div>
                        <div class="col-md-6 bank-field">
                            <label class="form-label">IFSC Code</label>
                            <input type="text" name="bank_ifsc" class="form-control" placeholder="IFSC Code">
                        </div>
                        <div class="col-md-6 bank-field">
                            <label class="form-label">Bank Branch Name</label>
                            <input type="text" name="bank_branch" class="form-control" placeholder="Branch Name">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Status <span class="text-danger">*</span></label>
                            <select name="status" class="form-select" required>
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-1"></i> Save Ledger</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

