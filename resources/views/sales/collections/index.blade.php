@extends('layouts.app')

@section('title', 'Customer Collections & Receipts')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Customer Collections & Receipts</h4>
        <p class="text-muted small mb-0">Record bank deposits, cash receipts, and customer payments; posts double-entry journal vouchers</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newCollectionModal">
        <i class="bi bi-wallet2 me-1"></i> Record Customer Receipt
    </button>
</div>

<!-- Collections Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Receipt #</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Payment Mode</th>
                        <th>Deposited Into Ledger</th>
                        <th class="text-end">Amount Collected</th>
                        <th>Transaction Ref</th>
                        <th>Collector</th>
                        <th>Accounting Ref</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($collections as $col)
                        <tr>
                            <td><span class="badge bg-light text-primary border fw-bold">{{ $col->collection_number }}</span></td>
                            <td>{{ \Carbon\Carbon::parse($col->collection_date)->format('d M Y') }}</td>
                            <td>
                                <div class="fw-bold">{{ $col->customer->name ?? 'Customer' }}</div>
                                <small class="text-muted">{{ $col->customer->code ?? '' }}</small>
                            </td>
                            <td><span class="badge bg-secondary">{{ $col->payment_mode }}</span></td>
                            <td><i class="bi bi-bank me-1 text-muted"></i> {{ $col->receiptLedger->name ?? 'Bank Account' }}</td>
                            <td class="text-end fw-bold text-success fs-6">₹ {{ number_format($col->collection_amount, 2) }}</td>
                            <td><small class="text-muted">{{ $col->transaction_reference ?? '-' }}</small></td>
                            <td><small class="text-muted">{{ $col->collected_by ?? '-' }}</small></td>
                            <td>
                                @if($col->journalEntry)
                                    <a href="{{ route('accounts.vouchers.show', $col->journalEntry) }}" class="badge bg-light text-primary border text-decoration-none">
                                        <i class="bi bi-journal-text me-1"></i> {{ $col->journalEntry->voucher_no }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-wallet2 fs-2 d-block mb-2"></i> No customer collections recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($collections->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $collections->links() }}
        </div>
    @endif
</div>

<!-- New Collection Modal -->
<div class="modal fade" id="newCollectionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('sales.collections.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-wallet2 me-2 text-primary"></i> Record Customer Payment Receipt</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Select Customer <span class="text-danger">*</span></label>
                            <select name="customer_id" class="form-select" required>
                                <option value="">Choose Customer</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }} (Outstanding: ₹ {{ number_format($c->current_balance, 2) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Deposit Into Bank / Cash Ledger <span class="text-danger">*</span></label>
                            <select name="receipt_ledger_id" class="form-select" required>
                                <option value="">Select Bank / Cash Account</option>
                                @foreach($bankLedgers as $bl)
                                    <option value="{{ $bl->id }}">{{ $bl->name }} (Current Bal: ₹ {{ number_format($bl->current_balance, 2) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Amount Collected (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="1" name="collection_amount" class="form-control fw-bold" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Collection Date <span class="text-danger">*</span></label>
                            <input type="date" name="collection_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Payment Mode <span class="text-danger">*</span></label>
                            <select name="payment_mode" class="form-select" required>
                                <option value="Bank Transfer" selected>Bank Transfer (NEFT/RTGS/IMPS)</option>
                                <option value="UPI">UPI / QR Payment</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Cash">Cash</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Transaction Reference / UTR</label>
                            <input type="text" name="transaction_reference" class="form-control" placeholder="Cheque / UTR / Transaction ID">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Collected By (Staff)</label>
                            <input type="text" name="collected_by" class="form-control" value="{{ auth()->user()->name }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Remarks</label>
                            <input type="text" name="remarks" class="form-control" placeholder="Optional notes">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i> Post Receipt & Update Ledger</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

