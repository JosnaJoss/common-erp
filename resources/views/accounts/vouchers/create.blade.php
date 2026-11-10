@extends('layouts.app')

@section('title', 'New Journal Voucher Entry')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Create Journal Voucher (JV)</h4>
        <p class="text-muted small mb-0">Record double-entry financial voucher. Total Debits must equal Total Credits.</p>
    </div>
    <a href="{{ route('accounts.vouchers.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Vouchers
    </a>
</div>

<form action="{{ route('accounts.vouchers.store') }}" method="POST" id="voucherForm">
    @csrf

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-info-circle me-1"></i> Voucher Header</h6>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label fw-bold">Voucher Type <span class="text-danger">*</span></label>
                    <select name="voucher_type" class="form-select @error('voucher_type') is-invalid @enderror" required>
                        <option value="Journal" selected>Journal (Adjustment / Transfer)</option>
                        <option value="Payment">Payment</option>
                        <option value="Receipt">Receipt</option>
                        <option value="Contra">Contra (Bank / Cash Transfer)</option>
                        <option value="Expense">Expense</option>
                        <option value="Income">Income</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">Voucher Date <span class="text-danger">*</span></label>
                    <input type="date" name="voucher_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label fw-bold">General Narration / Remarks</label>
                    <input type="text" name="narration" class="form-control" placeholder="e.g. Monthly rent provision / adjustment">
                </div>
            </div>
        </div>
    </div>

    <!-- Multi-Line Entries -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-list-check me-1"></i> Debit & Credit Accounts (Lines)</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addVoucherLineBtn">
                <i class="bi bi-plus-lg me-1"></i> Add Account Line
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered align-middle mb-0" id="voucherLinesTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 40%;">Ledger Account</th>
                            <th style="width: 20%;" class="text-end">Debit (Dr) ₹</th>
                            <th style="width: 20%;" class="text-end">Credit (Cr) ₹</th>
                            <th style="width: 15%;">Line Narration</th>
                            <th class="text-center" style="width: 5%;">Remove</th>
                        </tr>
                    </thead>
                    <tbody>
                        <!-- Line 1: Default Debit -->
                        <tr class="voucher-row">
                            <td>
                                <select name="lines[0][ledger_id]" class="form-select" required>
                                    <option value="">Select Ledger Account</option>
                                    @foreach($ledgers as $l)
                                        <option value="{{ $l->id }}">{{ $l->name }} ({{ $l->accountGroup->name ?? 'General' }})</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="lines[0][debit]" class="form-control text-end v-debit" value="0.00">
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="lines[0][credit]" class="form-control text-end v-credit" value="0.00">
                            </td>
                            <td>
                                <input type="text" name="lines[0][narration]" class="form-control form-control-sm" placeholder="Narration">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-voucher-row" disabled>
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>

                        <!-- Line 2: Default Credit -->
                        <tr class="voucher-row">
                            <td>
                                <select name="lines[1][ledger_id]" class="form-select" required>
                                    <option value="">Select Ledger Account</option>
                                    @foreach($ledgers as $l)
                                        <option value="{{ $l->id }}">{{ $l->name }} ({{ $l->accountGroup->name ?? 'General' }})</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="lines[1][debit]" class="form-control text-end v-debit" value="0.00">
                            </td>
                            <td>
                                <input type="number" step="0.01" min="0" name="lines[1][credit]" class="form-control text-end v-credit" value="0.00">
                            </td>
                            <td>
                                <input type="text" name="lines[1][narration]" class="form-control form-control-sm" placeholder="Narration">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-voucher-row">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="bg-light fw-bold">
                        <tr>
                            <td class="text-end">Total:</td>
                            <td class="text-end text-primary" id="totalDebitSpan">₹ 0.00</td>
                            <td class="text-end text-danger" id="totalCreditSpan">₹ 0.00</td>
                            <td colspan="2" class="text-center" id="balanceStatusSpan">
                                <span class="badge bg-secondary">Enter amounts</span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <div class="text-end">
        <button type="submit" class="btn btn-primary px-4 py-2" id="submitVoucherBtn">
            <i class="bi bi-check2-circle me-1"></i> Post Balanced Voucher
        </button>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = 2;
    const tableBody = document.querySelector('#voucherLinesTable tbody');
    const addRowBtn = document.getElementById('addVoucherLineBtn');
    const totalDebitSpan = document.getElementById('totalDebitSpan');
    const totalCreditSpan = document.getElementById('totalCreditSpan');
    const balanceStatusSpan = document.getElementById('balanceStatusSpan');
    const submitBtn = document.getElementById('submitVoucherBtn');

    function checkBalances() {
        let totalDebit = 0;
        let totalCredit = 0;

        document.querySelectorAll('.voucher-row').forEach(row => {
            const dr = parseFloat(row.querySelector('.v-debit').value) || 0;
            const cr = parseFloat(row.querySelector('.v-credit').value) || 0;
            totalDebit += dr;
            totalCredit += cr;
        });

        totalDebitSpan.innerText = '₹ ' + totalDebit.toFixed(2);
        totalCreditSpan.innerText = '₹ ' + totalCredit.toFixed(2);

        const diff = Math.abs(totalDebit - totalCredit);
        if (totalDebit > 0 && diff < 0.009) {
            balanceStatusSpan.innerHTML = '<span class="badge bg-success"><i class="bi bi-check2 me-1"></i> Balanced</span>';
            submitBtn.removeAttribute('disabled');
        } else if (totalDebit > 0 || totalCredit > 0) {
            balanceStatusSpan.innerHTML = '<span class="badge bg-danger"><i class="bi bi-exclamation-circle me-1"></i> Difference: ₹ ' + diff.toFixed(2) + '</span>';
            submitBtn.setAttribute('disabled', 'true');
        } else {
            balanceStatusSpan.innerHTML = '<span class="badge bg-secondary">Enter amounts</span>';
        }
    }

    tableBody.addEventListener('input', checkBalances);

    addRowBtn.addEventListener('click', function() {
        const firstRow = document.querySelector('.voucher-row');
        const newRow = firstRow.cloneNode(true);
        newRow.querySelectorAll('input, select').forEach(input => {
            const name = input.getAttribute('name');
            if (name) {
                input.setAttribute('name', name.replace(/\[\d+\]/, '[' + rowIndex + ']'));
            }
            if (input.classList.contains('v-debit')) input.value = '0.00';
            if (input.classList.contains('v-credit')) input.value = '0.00';
            if (input.tagName === 'SELECT') input.selectedIndex = 0;
        });
        newRow.querySelector('.remove-voucher-row').removeAttribute('disabled');
        tableBody.appendChild(newRow);
        rowIndex++;
        checkBalances();
    });

    tableBody.addEventListener('click', function(e) {
        if (e.target.closest('.remove-voucher-row') && document.querySelectorAll('.voucher-row').length > 2) {
            e.target.closest('.voucher-row').remove();
            checkBalances();
        }
    });

    checkBalances();
});
</script>
@endsection

