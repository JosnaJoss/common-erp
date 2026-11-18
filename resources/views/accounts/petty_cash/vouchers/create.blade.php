@extends('layouts.app')

@section('title', 'Record Petty Cash Voucher')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('accounts.petty_cash.index') }}" class="btn btn-outline-secondary me-3">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <div>
                <h4 class="fw-bold mb-0">Record Petty Cash Voucher</h4>
                <p class="text-muted small mb-0">Disburse cash for routine daily operational expenses and post automatic journal entry.</p>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('accounts.petty_cash.vouchers.store') }}" enctype="multipart/form-data">
                    @csrf

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Petty Cash Account <span class="text-danger">*</span></label>
                            <select name="petty_cash_account_id" id="accountSelect" class="form-select @error('petty_cash_account_id') is-invalid @enderror" required>
                                <option value="">-- Select Cash Float --</option>
                                @foreach($accounts as $acc)
                                    <option value="{{ $acc->id }}" data-balance="{{ $acc->current_balance }}" {{ old('petty_cash_account_id', $selectedAccountId) == $acc->id ? 'selected' : '' }}>
                                        {{ $acc->name }} (Available: ₹{{ number_format($acc->current_balance, 2) }})
                                    </option>
                                @endforeach
                            </select>
                            <div class="small text-muted mt-1" id="availableCashDisplay"></div>
                            @error('petty_cash_account_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Expense Category <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-select @error('category_id') is-invalid @enderror" required>
                                <option value="">-- Select Category --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('category_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Voucher Date <span class="text-danger">*</span></label>
                            <input type="date" name="voucher_date" class="form-control @error('voucher_date') is-invalid @enderror" value="{{ old('voucher_date', date('Y-m-d')) }}" required>
                            @error('voucher_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Amount (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" name="amount" id="voucherAmount" class="form-control @error('amount') is-invalid @enderror" value="{{ old('amount') }}" placeholder="e.g. 350.00" required>
                            @error('amount')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Paid To (Payee Name) <span class="text-danger">*</span></label>
                            <input type="text" name="paid_to" class="form-control @error('paid_to') is-invalid @enderror" value="{{ old('paid_to') }}" placeholder="e.g. Ramesh Tea Stall / Courier Agent" required>
                            @error('paid_to')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Purpose / Remarks <span class="text-danger">*</span></label>
                        <textarea name="remarks" class="form-control @error('remarks') is-invalid @enderror" rows="2" placeholder="Describe the reason for expenditure..." required>{{ old('remarks') }}</textarea>
                        @error('remarks')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold">Supporting Bill / Cash Receipt (Optional)</label>
                        <input type="file" name="receipt_doc" class="form-control" accept=".jpg,.jpeg,.png,.pdf">
                        <small class="text-muted">Attach photo or PDF scan of bill/cash voucher.</small>
                    </div>

                    <div class="d-flex justify-content-between align-items-center border-top pt-3">
                        <a href="{{ route('accounts.petty_cash.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check2-circle me-1"></i> Disburse & Post Journal Voucher
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const accountSelect = document.getElementById('accountSelect');
    const display = document.getElementById('availableCashDisplay');

    function updateBalance() {
        const opt = accountSelect.options[accountSelect.selectedIndex];
        if (opt && opt.dataset.balance !== undefined) {
            display.innerText = 'Current Balance: ₹ ' + parseFloat(opt.dataset.balance).toFixed(2);
        } else {
            display.innerText = '';
        }
    }

    accountSelect.addEventListener('change', updateBalance);
    updateBalance();
});
</script>
@endsection
