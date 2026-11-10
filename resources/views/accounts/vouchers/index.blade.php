@extends('layouts.app')

@section('title', 'Journal Entries & Vouchers')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Journal Entries & Vouchers</h4>
        <p class="text-muted small mb-0">Double-entry accounting journal books: Receipts, Payments, Sales, Purchases, and Contras</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('accounts.chart_of_accounts') }}" class="btn btn-outline-secondary">
            <i class="bi bi-diagram-3 me-1"></i> Chart of Accounts
        </a>
        <a href="{{ route('accounts.vouchers.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i> New Journal Entry
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('accounts.vouchers.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Voucher Type</label>
                <select name="voucher_type" class="form-select form-select-sm">
                    <option value="">All Voucher Types</option>
                    <option value="Journal" {{ request('voucher_type') == 'Journal' ? 'selected' : '' }}>Journal</option>
                    <option value="Payment" {{ request('voucher_type') == 'Payment' ? 'selected' : '' }}>Payment</option>
                    <option value="Receipt" {{ request('voucher_type') == 'Receipt' ? 'selected' : '' }}>Receipt</option>
                    <option value="Contra" {{ request('voucher_type') == 'Contra' ? 'selected' : '' }}>Contra</option>
                    <option value="Sales" {{ request('voucher_type') == 'Sales' ? 'selected' : '' }}>Sales</option>
                    <option value="Purchase" {{ request('voucher_type') == 'Purchase' ? 'selected' : '' }}>Purchase</option>
                    <option value="Payroll" {{ request('voucher_type') == 'Payroll' ? 'selected' : '' }}>Payroll</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Start Date</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">End Date</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-filter me-1"></i> Filter</button>
                <a href="{{ route('accounts.vouchers.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Vouchers Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Voucher #</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Narration / Particulars</th>
                        <th class="text-end">Debit Total (₹)</th>
                        <th class="text-end">Credit Total (₹)</th>
                        <th>Balanced?</th>
                        <th>Created By</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($entries as $jv)
                        <tr>
                            <td><span class="badge bg-light text-primary border fw-bold">{{ $jv->voucher_no }}</span></td>
                            <td>{{ \Carbon\Carbon::parse($jv->voucher_date)->format('d M Y') }}</td>
                            <td><span class="badge bg-secondary">{{ $jv->voucher_type }}</span></td>
                            <td>
                                <div>{{ $jv->narration }}</div>
                                @if($jv->reference_number)
                                    <small class="text-muted">Ref: {{ $jv->reference_number }}</small>
                                @endif
                            </td>
                            <td class="text-end fw-bold text-primary">₹ {{ number_format($jv->total_debit, 2) }}</td>
                            <td class="text-end fw-bold text-danger">₹ {{ number_format($jv->total_credit, 2) }}</td>
                            <td>
                                @if(abs($jv->total_debit - $jv->total_credit) < 0.01)
                                    <span class="badge bg-success"><i class="bi bi-check2 me-1"></i> Balanced</span>
                                @else
                                    <span class="badge bg-danger"><i class="bi bi-exclamation-triangle me-1"></i> Unbalanced</span>
                                @endif
                            </td>
                            <td><small class="text-muted">{{ $jv->creator->name ?? 'System' }}</small></td>
                            <td class="text-center">
                                <a href="{{ route('accounts.vouchers.show', $jv) }}" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye me-1"></i> View
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-journal-x fs-2 d-block mb-2"></i> No journal entries found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($entries->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $entries->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection

