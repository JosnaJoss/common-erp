@extends('layouts.app')

@section('title', 'Capital & Investor Management')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-person-badge text-primary me-2"></i> Capital & Investor Management</h4>
        <p class="text-muted small mb-0">Manage partner equity, capital contributions, owner drawings, shareholding ratios, and capital statements.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('accounts.capital.statement') }}" class="btn btn-outline-secondary">
            <i class="bi bi-file-earmark-spreadsheet me-1"></i> Capital Statements
        </a>
        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#newInvestorModal">
            <i class="bi bi-person-plus me-1"></i> New Investor / Partner
        </button>
        <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#newTransactionModal">
            <i class="bi bi-cash-coin me-1"></i> Record Contribution / Drawings
        </button>
    </div>
</div>

<!-- Quick Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-primary border-4">
            <div class="text-muted small text-uppercase fw-semibold">Total Capital Introduced</div>
            <div class="fs-4 fw-bold text-dark mt-1">₹ {{ number_format($stats['total_capital'], 2) }}</div>
            <div class="small text-muted">Cumulative contributions</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-danger border-4">
            <div class="text-muted small text-uppercase fw-semibold">Total Owner Drawings</div>
            <div class="fs-4 fw-bold text-danger mt-1">₹ {{ number_format($stats['total_drawings'], 2) }}</div>
            <div class="small text-muted">Cumulative withdrawals</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-success border-4">
            <div class="text-muted small text-uppercase fw-semibold">Net Retained Equity</div>
            <div class="fs-4 fw-bold text-success mt-1">₹ {{ number_format($stats['net_capital'], 2) }}</div>
            <div class="small text-muted">Current partner capital balance</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm p-3 border-start border-info border-4">
            <div class="text-muted small text-uppercase fw-semibold">Active Partners / Investors</div>
            <div class="fs-4 fw-bold text-dark mt-1">{{ number_format($stats['investor_count']) }}</div>
            <div class="small text-muted">Equity stakeholders</div>
        </div>
    </div>
</div>

<!-- Investors Listing -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-people me-1"></i> Partners & Investors Directory</h6>
        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#newInvestorModal">
            <i class="bi bi-plus-lg me-1"></i> Add Partner
        </button>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase">
                <tr>
                    <th style="width: 20%;">Partner / Investor Name</th>
                    <th style="width: 14%;">Designation / Type</th>
                    <th style="width: 16%;">Contact / PAN</th>
                    <th class="text-center" style="width: 10%;">Shareholding</th>
                    <th class="text-end" style="width: 14%;">Contributed (₹)</th>
                    <th class="text-end" style="width: 12%;">Drawings (₹)</th>
                    <th class="text-end" style="width: 14%;">Net Equity (₹)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($investors as $inv)
                    <tr>
                        <td>
                            <a href="{{ route('accounts.capital.investors.show', $inv) }}" class="fw-bold text-decoration-none">
                                {{ $inv->name }}
                            </a>
                            @if($inv->ledger)
                                <div class="small text-muted">Ledger: {{ $inv->ledger->name }}</div>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $inv->investor_type }}</span>
                        </td>
                        <td>
                            <div class="small">{{ $inv->phone ?: '-' }}</div>
                            <small class="text-muted">PAN: {{ $inv->pan_number ?: 'N/A' }}</small>
                        </td>
                        <td class="text-center fw-bold text-primary">
                            {{ number_format($inv->shareholding_percentage, 1) }}%
                        </td>
                        <td class="text-end fw-semibold text-success">
                            ₹ {{ number_format($inv->total_capital_contributed, 2) }}
                        </td>
                        <td class="text-end fw-semibold text-danger">
                            ₹ {{ number_format($inv->total_drawings, 2) }}
                        </td>
                        <td class="text-end fw-bold fs-6 text-dark">
                            ₹ {{ number_format($inv->current_balance, 2) }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-5 text-muted">
                            <i class="bi bi-people fs-1 d-block mb-2 text-secondary"></i>
                            No partners or investors added yet. Click <strong>"New Investor / Partner"</strong> to add one.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Recent Transactions Listing -->
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-arrow-left-right me-1"></i> Capital Contributions & Drawings Ledger</h6>
        <div class="d-flex gap-2">
            <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#newTransactionModal">
                <i class="bi bi-plus-lg me-1"></i> New Entry
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light small text-uppercase">
                <tr>
                    <th style="width: 14%;">Transaction #</th>
                    <th style="width: 12%;">Date</th>
                    <th style="width: 18%;">Partner / Investor</th>
                    <th class="text-center" style="width: 12%;">Type</th>
                    <th class="text-end" style="width: 14%;">Amount (₹)</th>
                    <th style="width: 18%;">Disbursed In / From</th>
                    <th style="width: 12%;">Voucher Ref</th>
                </tr>
            </thead>
            <tbody>
                @forelse($transactions as $t)
                    <tr>
                        <td class="fw-bold">{{ $t->investment_no }}</td>
                        <td>{{ $t->transaction_date->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('accounts.capital.investors.show', $t->investor) }}" class="fw-semibold text-decoration-none text-dark">
                                {{ $t->investor->name }}
                            </a>
                            <div class="small text-muted text-truncate" style="max-width: 160px;">{{ $t->remarks }}</div>
                        </td>
                        <td class="text-center">
                            @if($t->transaction_type === 'contribution')
                                <span class="badge bg-success"><i class="bi bi-arrow-down-left me-1"></i> Contribution</span>
                            @else
                                <span class="badge bg-danger"><i class="bi bi-arrow-up-right me-1"></i> Drawings</span>
                            @endif
                        </td>
                        <td class="text-end fw-bold {{ $t->transaction_type === 'contribution' ? 'text-success' : 'text-danger' }}">
                            ₹ {{ number_format($t->amount, 2) }}
                        </td>
                        <td>
                            <span class="small fw-semibold text-dark">{{ $t->depositLedger->name }}</span>
                            <small class="d-block text-muted">Via {{ $t->payment_mode }} {{ $t->reference_no ? '(' . $t->reference_no . ')' : '' }}</small>
                        </td>
                        <td>
                            @if($t->journalEntry)
                                <a href="{{ route('accounts.vouchers.show', $t->journalEntry) }}" class="badge bg-light text-primary border text-decoration-none">
                                    <i class="bi bi-journal-text me-1"></i> {{ $t->journalEntry->voucher_no }}
                                </a>
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No capital contributions or drawings transactions recorded yet.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($transactions->hasPages())
        <div class="card-footer bg-white py-2">
            {{ $transactions->links() }}
        </div>
    @endif
</div>

<!-- Modal 1: New Investor / Partner -->
<div class="modal fade" id="newInvestorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('accounts.capital.investors.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2"></i> Register Investor / Partner</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Full Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="e.g. John Doe / Rajesh Kumar" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Investor Type <span class="text-danger">*</span></label>
                            <select name="investor_type" class="form-select" required>
                                <option value="Partner">Partner (LLP / Partnership)</option>
                                <option value="Director">Director / Promoter</option>
                                <option value="Proprietor">Proprietor / Owner</option>
                                <option value="Shareholder">Shareholder / Investor</option>
                                <option value="External Investor">External Investor</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Shareholding %</label>
                            <input type="number" step="0.01" min="0" max="100" name="shareholding_percentage" class="form-control" placeholder="e.g. 50.00">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Phone Number</label>
                            <input type="text" name="phone" class="form-control form-control-sm" placeholder="e.g. +91 9876543210">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email Address</label>
                            <input type="email" name="email" class="form-control form-control-sm" placeholder="e.g. partner@example.com">
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">PAN Number</label>
                            <input type="text" name="pan_number" class="form-control form-control-sm" placeholder="e.g. ABCDE1234F">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Initial Opening Capital (₹)</label>
                            <input type="number" step="0.01" min="0" name="opening_balance" class="form-control form-control-sm" value="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Residential / Office Address</label>
                        <textarea name="address" class="form-control form-control-sm" rows="2" placeholder="Address..."></textarea>
                    </div>
                    <div class="p-2 bg-light rounded border small text-muted">
                        An accounting Capital Account ledger will be automatically created under Capital / Liabilities for double-entry tracking.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Register Partner</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal 2: Record Contribution / Drawings Transaction -->
<div class="modal fade" id="newTransactionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="{{ route('accounts.capital.transactions.store') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cash-coin me-2"></i> Record Capital Contribution / Drawings</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Select Partner / Investor <span class="text-danger">*</span></label>
                            <select name="investor_id" class="form-select" required>
                                <option value="">-- Choose Partner --</option>
                                @foreach($investors as $inv)
                                    <option value="{{ $inv->id }}">
                                        {{ $inv->name }} (Balance: ₹{{ number_format($inv->current_balance, 2) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Transaction Type <span class="text-danger">*</span></label>
                            <select name="transaction_type" class="form-select" required>
                                <option value="contribution">Capital Contribution (Funds Inward)</option>
                                <option value="drawings">Owner Drawings (Personal Withdrawal)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" class="form-control" placeholder="e.g. 50000.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Date <span class="text-danger">*</span></label>
                            <input type="date" name="transaction_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Payment Mode <span class="text-danger">*</span></label>
                            <select name="payment_mode" class="form-select" required>
                                <option value="Bank Transfer">Bank Transfer (NEFT/RTGS/IMPS)</option>
                                <option value="Cheque">Cheque</option>
                                <option value="UPI">UPI</option>
                                <option value="Cash">Cash in Hand</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Company Bank / Cash Ledger <span class="text-danger">*</span></label>
                            <select name="deposit_ledger_id" class="form-select" required>
                                @foreach($bankLedgers as $bl)
                                    <option value="{{ $bl->id }}">{{ $bl->name }} (₹{{ number_format($bl->current_balance, 2) }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Cheque / UTR / Reference #</label>
                        <input type="text" name="reference_no" class="form-control form-control-sm" placeholder="e.g. UTR-88120391 or CHQ-00123">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Narration / Purpose <span class="text-danger">*</span></label>
                        <input type="text" name="remarks" class="form-control" placeholder="e.g. Additional equity contribution for business expansion" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary px-4">Post Transaction & Accounts</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
