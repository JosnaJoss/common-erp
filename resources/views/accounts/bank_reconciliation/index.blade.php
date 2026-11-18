@extends('layouts.app')

@section('title', 'Bank Reconciliation (BRS)')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-bank2 text-primary me-2"></i> Bank Reconciliation (BRS)</h4>
        <p class="text-muted small mb-0">Reconcile internal bank books with actual bank statements and generate formal BRS statements.</p>
    </div>
    <div>
        <a href="{{ route('accounts.bank_reconciliation.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle me-1"></i> New Reconciliation
        </a>
    </div>
</div>

<!-- Quick Metrics -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-primary border-4">
            <div class="text-muted small text-uppercase fw-semibold">Total Reconciliations</div>
            <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($stats['total']) }}</div>
            <div class="small text-muted">Historical BRS sessions</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-success border-4">
            <div class="text-muted small text-uppercase fw-semibold">Completed & Locked</div>
            <div class="fs-4 fw-bold text-success mt-1">{{ number_format($stats['completed']) }}</div>
            <div class="small text-muted">Audited & closed periods</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-warning border-4">
            <div class="text-muted small text-uppercase fw-semibold">In Progress / Draft</div>
            <div class="fs-4 fw-bold text-warning mt-1">{{ number_format($stats['draft']) }}</div>
            <div class="small text-muted">Pending matching & signoff</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-info border-4">
            <div class="text-muted small text-uppercase fw-semibold">Last Reconciled Date</div>
            <div class="fs-4 fw-bold text-dark mt-1">{{ $stats['latest_reconciled'] ? \Carbon\Carbon::parse($stats['latest_reconciled'])->format('d M Y') : 'None yet' }}</div>
            <div class="small text-muted">Latest cleared statement</div>
        </div>
    </div>
</div>

<!-- Filter Bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body p-3">
        <form method="GET" action="{{ route('accounts.bank_reconciliation.index') }}" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label small fw-semibold">Bank Account</label>
                <select name="ledger_id" class="form-select form-select-sm">
                    <option value="">All Bank Accounts</option>
                    @foreach($bankLedgers as $bl)
                        <option value="{{ $bl->id }}" {{ request('ledger_id') == $bl->id ? 'selected' : '' }}>
                            {{ $bl->name }} (A/C: {{ $bl->bank_account_no ?: 'N/A' }})
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold">Status</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="in_progress" {{ in_array(request('status'), ['in_progress', 'draft']) ? 'selected' : '' }}>In Progress</option>
                    <option value="reconciled" {{ in_array(request('status'), ['reconciled', 'completed']) ? 'selected' : '' }}>Reconciled & Locked</option>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-sm btn-primary w-100">
                    <i class="bi bi-funnel me-1"></i> Filter
                </button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('accounts.bank_reconciliation.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Listing Table -->
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase">
                <tr>
                    <th style="width: 15%;">Reconciliation #</th>
                    <th style="width: 20%;">Bank Account</th>
                    <th style="width: 12%;">Statement Date</th>
                    <th class="text-end" style="width: 13%;">Statement Bal (₹)</th>
                    <th class="text-end" style="width: 13%;">Book Bal (₹)</th>
                    <th class="text-end" style="width: 12%;">Difference (₹)</th>
                    <th class="text-center" style="width: 8%;">Status</th>
                    <th class="text-center" style="width: 7%;">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse($reconciliations as $rec)
                    <tr>
                        <td>
                            <a href="{{ route('accounts.bank_reconciliation.show', $rec) }}" class="fw-bold text-decoration-none">
                                {{ $rec->reconciliation_no }}
                            </a>
                            @if($rec->import_filename)
                                <div class="small text-muted"><i class="bi bi-paperclip me-1"></i> {{ Str::limit($rec->import_filename, 18) }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $rec->bankLedger->name }}</div>
                            <small class="text-muted">A/C: {{ $rec->bankLedger->bank_account_no ?? 'Main' }} | {{ $rec->bankLedger->bank_name ?? '' }}</small>
                        </td>
                        <td>{{ $rec->statement_date->format('d M Y') }}</td>
                        <td class="text-end fw-semibold">₹ {{ number_format($rec->statement_balance, 2) }}</td>
                        <td class="text-end">₹ {{ number_format($rec->book_balance, 2) }}</td>
                        <td class="text-end fw-bold {{ abs($rec->difference) > 0.01 ? 'text-danger' : 'text-success' }}">
                            ₹ {{ number_format($rec->difference, 2) }}
                        </td>
                        <td class="text-center">
                            @if($rec->status === 'completed' || $rec->status === 'reconciled')
                                <span class="badge bg-success"><i class="bi bi-lock-fill me-1"></i> Reconciled</span>
                            @else
                                <span class="badge bg-warning text-dark"><i class="bi bi-clock-history me-1"></i> In Progress</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('accounts.bank_reconciliation.show', $rec) }}" class="btn btn-outline-primary" title="Reconciliation Workspace">
                                    <i class="bi bi-sliders"></i>
                                </a>
                                <a href="{{ route('accounts.bank_reconciliation.report', $rec) }}" class="btn btn-outline-secondary" title="View BRS Statement">
                                    <i class="bi bi-file-earmark-text"></i>
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">
                            <i class="bi bi-bank fs-1 d-block mb-2 text-secondary"></i>
                            No bank reconciliations found. Click <strong>"New Reconciliation"</strong> to begin.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($reconciliations->hasPages())
        <div class="card-footer bg-white py-2">
            {{ $reconciliations->links() }}
        </div>
    @endif
</div>
@endsection
