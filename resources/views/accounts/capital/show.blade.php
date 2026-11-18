@extends('layouts.app')

@section('title', 'Partner Profile - ' . $investor->name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center gap-3">
        <a href="{{ route('accounts.capital.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h4 class="fw-bold mb-0">{{ $investor->name }}</h4>
            <div class="small text-muted">
                {{ $investor->investor_type }} | Shareholding: <strong>{{ number_format($investor->shareholding_percentage, 1) }}%</strong> | PAN: {{ $investor->pan_number ?: 'N/A' }}
            </div>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('accounts.capital.statement', $investor) }}" class="btn btn-outline-primary">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Capital Statement
        </a>
        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editInvestorModal">
            <i class="bi bi-pencil me-1"></i> Edit Details
        </button>
    </div>
</div>

<!-- Financial Summary Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 border-start border-success border-4">
            <div class="text-muted small text-uppercase fw-semibold">Total Capital Contributed</div>
            <div class="fs-4 fw-bold text-success mt-1">₹ {{ number_format($investor->total_capital_contributed, 2) }}</div>
            <div class="small text-muted">Funds injected into business</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 border-start border-danger border-4">
            <div class="text-muted small text-uppercase fw-semibold">Total Personal Drawings</div>
            <div class="fs-4 fw-bold text-danger mt-1">₹ {{ number_format($investor->total_drawings, 2) }}</div>
            <div class="small text-muted">Withdrawals by partner</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 border-start border-primary border-4">
            <div class="text-muted small text-uppercase fw-semibold">Current Net Capital Balance</div>
            <div class="fs-4 fw-bold text-primary mt-1">₹ {{ number_format($investor->current_balance, 2) }}</div>
            <div class="small text-muted">Equity stake as of today</div>
        </div>
    </div>
</div>

<!-- Transaction History -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-bottom">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-clock-history me-1"></i> Transaction History (Contributions & Withdrawals)</h6>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase">
                <tr>
                    <th style="width: 15%;">Transaction #</th>
                    <th style="width: 15%;">Date</th>
                    <th class="text-center" style="width: 15%;">Type</th>
                    <th class="text-end" style="width: 18%;">Amount (₹)</th>
                    <th style="width: 22%;">Settlement Account</th>
                    <th style="width: 15%;">Voucher Ref</th>
                </tr>
            </thead>
            <tbody>
                @forelse($investor->investments as $inv)
                    <tr>
                        <td class="fw-bold">{{ $inv->investment_no }}</td>
                        <td>{{ $inv->transaction_date->format('d M Y') }}</td>
                        <td class="text-center">
                            @if($inv->transaction_type === 'contribution')
                                <span class="badge bg-success">Contribution</span>
                            @else
                                <span class="badge bg-danger">Drawings</span>
                            @endif
                        </td>
                        <td class="text-end fw-bold {{ $inv->transaction_type === 'contribution' ? 'text-success' : 'text-danger' }}">
                            ₹ {{ number_format($inv->amount, 2) }}
                        </td>
                        <td>
                            <div class="small fw-semibold">{{ $inv->depositLedger->name }}</div>
                            <small class="text-muted">Via {{ $inv->payment_mode }} {{ $inv->reference_no ? '(' . $inv->reference_no . ')' : '' }}</small>
                        </td>
                        <td>
                            @if($inv->journalEntry)
                                <a href="{{ route('accounts.vouchers.show', $inv->journalEntry) }}" class="badge bg-light text-primary border text-decoration-none">
                                    <i class="bi bi-journal-text me-1"></i> {{ $inv->journalEntry->voucher_no }}
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No capital transactions recorded for this partner yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editInvestorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('accounts.capital.investors.update', $investor) }}">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">Edit Partner Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" value="{{ $investor->name }}" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Type</label>
                            <select name="investor_type" class="form-select">
                                <option value="Partner" {{ $investor->investor_type === 'Partner' ? 'selected' : '' }}>Partner</option>
                                <option value="Director" {{ $investor->investor_type === 'Director' ? 'selected' : '' }}>Director</option>
                                <option value="Proprietor" {{ $investor->investor_type === 'Proprietor' ? 'selected' : '' }}>Proprietor</option>
                                <option value="Shareholder" {{ $investor->investor_type === 'Shareholder' ? 'selected' : '' }}>Shareholder</option>
                                <option value="External Investor" {{ $investor->investor_type === 'External Investor' ? 'selected' : '' }}>External Investor</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Shareholding %</label>
                            <input type="number" step="0.01" min="0" max="100" name="shareholding_percentage" class="form-control" value="{{ $investor->shareholding_percentage }}">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Phone</label>
                            <input type="text" name="phone" class="form-control form-control-sm" value="{{ $investor->phone }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email</label>
                            <input type="email" name="email" class="form-control form-control-sm" value="{{ $investor->email }}">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">PAN</label>
                            <input type="text" name="pan_number" class="form-control form-control-sm" value="{{ $investor->pan_number }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Status</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="active" {{ $investor->status === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ $investor->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Update Details</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
