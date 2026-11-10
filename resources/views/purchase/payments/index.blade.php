@extends('layouts.app')

@section('title', 'Supplier Payments & Disbursements')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Supplier Payments & Disbursements</h4>
        <p class="text-muted small mb-0">Record bank transfers, cheques, and cash payments against supplier accounts and purchase bills</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newPaymentModal">
        <i class="bi bi-cash-stack me-1"></i> Make Payment
    </button>
</div>

<!-- Payments Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Payment #</th>
                        <th>Payment Date</th>
                        <th>Supplier</th>
                        <th>Payment Mode</th>
                        <th>Disbursed From Ledger</th>
                        <th class="text-end">Amount Paid</th>
                        <th>Transaction Ref</th>
                        <th>Accounting Voucher</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payments as $pay)
                        <tr>
                            <td><span class="badge bg-light text-primary border fw-bold">{{ $pay->payment_number }}</span></td>
                            <td>{{ \Carbon\Carbon::parse($pay->payment_date)->format('d M Y') }}</td>
                            <td>
                                <div class="fw-bold">{{ $pay->supplier->name ?? 'Supplier' }}</div>
                                <small class="text-muted">{{ $pay->supplier->code ?? '' }}</small>
                            </td>
                            <td><span class="badge bg-secondary">{{ $pay->payment_mode }}</span></td>
                            <td><i class="bi bi-bank me-1 text-muted"></i> {{ $pay->paymentLedger->name ?? 'Bank Account' }}</td>
                            <td class="text-end fw-bold text-success fs-6">₹ {{ number_format($pay->payment_amount, 2) }}</td>
                            <td><small class="text-muted">{{ $pay->transaction_reference ?? '-' }}</small></td>
                            <td>
                                @if($pay->journalEntry)
                                    <a href="{{ route('accounts.vouchers.show', $pay->journalEntry) }}" class="badge bg-light text-primary border text-decoration-none">
                                        <i class="bi bi-journal-text me-1"></i> {{ $pay->journalEntry->voucher_no }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-cash-stack fs-2 d-block mb-2"></i> No payments recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($payments->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $payments->links() }}
        </div>
    @endif
</div>

<!-- New Payment Modal -->
<div class="modal fade" id="newPaymentModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('purchase.payments.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-cash-stack me-2 text-primary"></i> Disburse Supplier Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Select Supplier <span class="text-danger">*</span></label>
                            <select name="supplier_id" class="form-select" required>
                                <option value="">Choose Supplier</option>
                                @foreach($suppliers as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }} (Balance Due: ₹ {{ number_format($s->current_balance, 2) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Disbursing Bank / Cash Ledger <span class="text-danger">*</span></label>
                            <select name="payment_ledger_id" class="form-select" required>
                                <option value="">Select Bank / Cash Account</option>
                                @foreach($bankLedgers as $bl)
                                    <option value="{{ $bl->id }}">{{ $bl->name }} (Bal: ₹ {{ number_format($bl->current_balance, 2) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Payment Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="1" name="payment_amount" class="form-control fw-bold" placeholder="0.00" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Payment Date <span class="text-danger">*</span></label>
                            <input type="date" name="payment_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Payment Mode <span class="text-danger">*</span></label>
                            <select name="payment_mode" class="form-select" required>
                                <option value="Bank Transfer" selected>Bank Transfer (NEFT/RTGS/IMPS)</option>
                                <option value="Cheque">Cheque</option>
                                <option value="Cash">Cash</option>
                                <option value="UPI">UPI</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Transaction Ref / Cheque No</label>
                            <input type="text" name="transaction_reference" class="form-control" placeholder="UTR or Cheque number">
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Remarks / Narration</label>
                            <textarea name="remarks" class="form-control" rows="2" placeholder="Payment remarks"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i> Post Payment Voucher</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

