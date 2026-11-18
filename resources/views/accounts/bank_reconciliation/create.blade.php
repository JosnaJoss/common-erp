@extends('layouts.app')

@section('title', 'Start Bank Reconciliation')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="d-flex align-items-center mb-4">
            <a href="{{ route('accounts.bank_reconciliation.index') }}" class="btn btn-outline-secondary me-3">
                <i class="bi bi-arrow-left"></i> Back
            </a>
            <div>
                <h4 class="fw-bold mb-0">Start New Bank Reconciliation (BRS)</h4>
                <p class="text-muted small mb-0">Setup bank statement period and initialize un-reconciled book transactions.</p>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <form method="POST" action="{{ route('accounts.bank_reconciliation.store') }}">
                    @csrf

                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Bank Ledger <span class="text-danger">*</span></label>
                        <select name="ledger_id" class="form-select @error('ledger_id') is-invalid @enderror" required>
                            <option value="">-- Choose Bank Account --</option>
                            @foreach($bankLedgers as $bl)
                                <option value="{{ $bl->id }}" {{ old('ledger_id') == $bl->id ? 'selected' : '' }}>
                                    {{ $bl->name }} (A/C: {{ $bl->bank_account_no ?: 'Main' }}) — Current Ledger Balance: ₹ {{ number_format($bl->current_balance, 2) }}
                                </option>
                            @endforeach
                        </select>
                        @error('ledger_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Bank Statement Closing Date <span class="text-danger">*</span></label>
                            <input type="date" name="statement_date" class="form-control @error('statement_date') is-invalid @enderror" value="{{ old('statement_date', date('Y-m-d')) }}" required>
                            <small class="text-muted">All ERP entries on or before this date will be loaded.</small>
                            @error('statement_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Statement Ending Balance (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="statement_balance" class="form-control @error('statement_balance') is-invalid @enderror" value="{{ old('statement_balance', '0.00') }}" placeholder="e.g. 150000.00" required>
                            <small class="text-muted">Closing balance as shown on your physical/PDF bank statement.</small>
                            @error('statement_balance')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold">Notes / Reference</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Optional notes for this monthly reconciliation cycle...">{{ old('notes') }}</textarea>
                    </div>

                    <div class="d-flex justify-content-between align-items-center border-top pt-3">
                        <a href="{{ route('accounts.bank_reconciliation.index') }}" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-arrow-right-circle me-1"></i> Initialize Reconciliation Workspace
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
