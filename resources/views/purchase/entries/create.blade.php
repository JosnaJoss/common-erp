@extends('layouts.app')

@section('title', 'Record Purchase Invoice Entry')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">New Purchase Bill Entry</h4>
        <p class="text-muted small mb-0">Record vendor bill: updates inventory stock batches, supports line/bill discounts and immediate payment settlement</p>
    </div>
    <a href="{{ route('purchase.entries.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Bills
    </a>
</div>

<form action="{{ route('purchase.entries.store') }}" method="POST" id="purchaseEntryForm">
    @csrf

    <div class="row g-4">
        <!-- Header Info -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-file-earmark-text me-1"></i> Vendor Bill Details</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
                            <option value="">Choose Supplier</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" {{ old('supplier_id') == $s->id ? 'selected' : '' }}>
                                    {{ $s->name }} ({{ $s->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Inward Godown / Warehouse <span class="text-danger">*</span></label>
                        <select name="godown_id" class="form-select @error('godown_id') is-invalid @enderror" required>
                            <option value="">Choose Warehouse</option>
                            @foreach($godowns as $g)
                                <option value="{{ $g->id }}" {{ old('godown_id') == $g->id ? 'selected' : '' }}>{{ $g->name }} ({{ $g->code }})</option>
                            @endforeach
                        </select>
                        @error('godown_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Supplier Invoice Number <span class="text-danger">*</span></label>
                        <input type="text" name="invoice_number" class="form-control @error('invoice_number') is-invalid @enderror" value="{{ old('invoice_number') }}" placeholder="e.g. INV-SUPP-9842" required>
                        @error('invoice_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Invoice Date <span class="text-danger">*</span></label>
                        <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', date('Y-m-d')) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Payment Type <span class="text-danger">*</span></label>
                        <select name="payment_type" id="paymentTypeSelect" class="form-select" required>
                            <option value="Credit" {{ old('payment_type', 'Credit') == 'Credit' ? 'selected' : '' }}>Credit (Bill Payable Later)</option>
                            <option value="Cash" {{ old('payment_type') == 'Cash' ? 'selected' : '' }}>Cash (Immediate Settlement)</option>
                            <option value="Bank" {{ old('payment_type') == 'Bank' ? 'selected' : '' }}>Bank Transfer (Immediate)</option>
                            <option value="UPI" {{ old('payment_type') == 'UPI' ? 'selected' : '' }}>UPI / Digital (Immediate)</option>
                        </select>
                    </div>

                    <!-- Immediate Payment Options -->
                    <div id="immediatePaymentBlock" class="p-3 border rounded bg-light mb-3" style="display: none;">
                        <h6 class="fw-bold small text-success mb-2"><i class="bi bi-wallet2 me-1"></i> Immediate Payment Settlement</h6>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Disbursement Ledger <span class="text-danger">*</span></label>
                            <select name="payment_ledger_id" class="form-select form-select-sm">
                                <option value="">Select Bank / Cash Account</option>
                                @foreach($bankLedgers as $bl)
                                    <option value="{{ $bl->id }}" {{ old('payment_ledger_id') == $bl->id ? 'selected' : '' }}>{{ $bl->name }} (₹{{ number_format($bl->current_balance, 2) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Amount Paid (₹)</label>
                            <input type="number" step="0.01" min="0" name="amount_paid" id="amountPaidInput" class="form-control form-control-sm" value="{{ old('amount_paid') }}" placeholder="Full or partial amount">
                        </div>
                        <div>
                            <label class="form-label small fw-semibold">Ref / Cheque / UTR #</label>
                            <input type="text" name="transaction_reference" class="form-control form-control-sm" value="{{ old('transaction_reference') }}" placeholder="Transaction reference">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks / Notes</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Notes, GRN reference, or transporter details">{{ old('remarks') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items & Pricing -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-box-seam me-1"></i> Billed Items, Batches & Discounts</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addBillItemBtn">
                        <i class="bi bi-plus-lg me-1"></i> Add Line
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0" id="billItemsTable">
                            <thead class="table-light small">
                                <tr>
                                    <th style="width: 28%;">Item & Unit</th>
                                    <th style="width: 14%;">Batch #</th>
                                    <th style="width: 12%;">Qty</th>
                                    <th style="width: 14%;">Rate (₹)</th>
                                    <th style="width: 12%;">Disc (₹)</th>
                                    <th style="width: 10%;">GST %</th>
                                    <th class="text-end" style="width: 14%;">Total (₹)</th>
                                    <th class="text-center" style="width: 6%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="bill-row">
                                    <td>
                                        <select name="items[0][item_id]" class="form-select form-select-sm bill-item-select" required>
                                            <option value="">Select Item</option>
                                            @foreach($items as $it)
                                                <option value="{{ $it->id }}" data-rate="{{ $it->min_price ?? 0 }}" data-gst="{{ $it->gst_percentage }}" data-unit="{{ $it->unit->symbol ?? '' }}">
                                                    {{ $it->name }} ({{ $it->code }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted item-unit-badge"></small>
                                    </td>
                                    <td>
                                        <input type="text" name="items[0][batch_number]" class="form-control form-control-sm" value="BATCH-{{ date('Ymd') }}">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0.01" name="items[0][quantity]" class="form-control form-control-sm bill-qty" value="1" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="items[0][rate]" class="form-control form-control-sm bill-rate" value="0.00" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="items[0][discount_amount]" class="form-control form-control-sm bill-disc" value="0.00">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" max="100" name="items[0][tax_percentage]" class="form-control form-control-sm bill-gst" value="18">
                                    </td>
                                    <td class="text-end fw-semibold bill-line-total">
                                        ₹ 0.00
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-bill-row" disabled>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light p-3">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold mb-1">Additional Bill Discount (₹)</label>
                            <input type="number" step="0.01" min="0" name="discount_amount" id="billDiscountInput" class="form-control form-control-sm" value="{{ old('discount_amount', 0) }}" placeholder="e.g. 50.00">
                            <small class="text-muted">Applied over and above item discounts.</small>
                        </div>
                        <div class="col-md-7 text-end">
                            <div class="small text-muted">Subtotal: <span class="fw-semibold text-dark" id="displaySubtotal">₹ 0.00</span> | Total Discounts: <span class="fw-semibold text-danger" id="displayDiscount">-₹ 0.00</span></div>
                            <div class="small text-muted">Total GST Tax: <span class="fw-semibold text-dark" id="displayTax">₹ 0.00</span></div>
                            <div class="fs-5 text-muted mt-1">Grand Total: <span class="fw-bold text-primary fs-4" id="billGrandTotal">₹ 0.00</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary px-4 py-2">
                    <i class="bi bi-check2-circle me-1"></i> Post Purchase Invoice & Update Books
                </button>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = 1;
    const tableBody = document.querySelector('#billItemsTable tbody');
    const addRowBtn = document.getElementById('addBillItemBtn');
    const grandTotalSpan = document.getElementById('billGrandTotal');
    const displaySubtotal = document.getElementById('displaySubtotal');
    const displayDiscount = document.getElementById('displayDiscount');
    const displayTax = document.getElementById('displayTax');
    const billDiscountInput = document.getElementById('billDiscountInput');
    const paymentTypeSelect = document.getElementById('paymentTypeSelect');
    const immediatePaymentBlock = document.getElementById('immediatePaymentBlock');
    const amountPaidInput = document.getElementById('amountPaidInput');

    function togglePaymentBlock() {
        if (paymentTypeSelect.value !== 'Credit') {
            immediatePaymentBlock.style.display = 'block';
        } else {
            immediatePaymentBlock.style.display = 'none';
        }
    }
    paymentTypeSelect.addEventListener('change', togglePaymentBlock);
    togglePaymentBlock();

    function calculateTotal() {
        let subtotal = 0;
        let itemDiscountTotal = 0;
        let taxTotal = 0;

        document.querySelectorAll('.bill-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.bill-qty').value) || 0;
            const rate = parseFloat(row.querySelector('.bill-rate').value) || 0;
            const disc = parseFloat(row.querySelector('.bill-disc').value) || 0;
            const gst = parseFloat(row.querySelector('.bill-gst').value) || 0;

            const lineGross = qty * rate;
            const lineTaxable = Math.max(0, lineGross - disc);
            const lineTax = lineTaxable * (gst / 100);
            const lineTotal = lineTaxable + lineTax;

            row.querySelector('.bill-line-total').innerText = '₹ ' + lineTotal.toFixed(2);

            subtotal += lineGross;
            itemDiscountTotal += disc;
            taxTotal += lineTax;
        });

        const billDiscount = parseFloat(billDiscountInput.value) || 0;
        const totalDiscount = itemDiscountTotal + billDiscount;
        const grandTotal = Math.max(0, (subtotal - totalDiscount) + taxTotal);

        displaySubtotal.innerText = '₹ ' + subtotal.toFixed(2);
        displayDiscount.innerText = '-₹ ' + totalDiscount.toFixed(2);
        displayTax.innerText = '₹ ' + taxTotal.toFixed(2);
        grandTotalSpan.innerText = '₹ ' + grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        if (paymentTypeSelect.value !== 'Credit' && (!amountPaidInput.value || parseFloat(amountPaidInput.value) === 0)) {
            amountPaidInput.value = grandTotal.toFixed(2);
        }
    }

    tableBody.addEventListener('change', function(e) {
        if (e.target.classList.contains('bill-item-select')) {
            const selectedOpt = e.target.options[e.target.selectedIndex];
            const rate = selectedOpt.dataset.rate || 0;
            const gst = selectedOpt.dataset.gst || 18;
            const unit = selectedOpt.dataset.unit || '';
            const row = e.target.closest('.bill-row');
            row.querySelector('.bill-rate').value = rate;
            row.querySelector('.bill-gst').value = gst;
            const unitBadge = row.querySelector('.item-unit-badge');
            if (unitBadge) unitBadge.innerText = unit ? 'Unit: ' + unit : '';
        }
        calculateTotal();
    });

    tableBody.addEventListener('input', calculateTotal);
    billDiscountInput.addEventListener('input', calculateTotal);

    addRowBtn.addEventListener('click', function() {
        const firstRow = document.querySelector('.bill-row');
        const newRow = firstRow.cloneNode(true);
        newRow.querySelectorAll('input, select').forEach(input => {
            const name = input.getAttribute('name');
            if (name) {
                input.setAttribute('name', name.replace(/\[\d+\]/, '[' + rowIndex + ']'));
            }
            if (input.classList.contains('bill-qty')) input.value = 1;
            if (input.classList.contains('bill-rate')) input.value = '0.00';
            if (input.classList.contains('bill-disc')) input.value = '0.00';
            if (input.classList.contains('bill-item-select')) input.selectedIndex = 0;
        });
        const unitBadge = newRow.querySelector('.item-unit-badge');
        if (unitBadge) unitBadge.innerText = '';
        newRow.querySelector('.remove-bill-row').removeAttribute('disabled');
        tableBody.appendChild(newRow);
        rowIndex++;
        calculateTotal();
    });

    tableBody.addEventListener('click', function(e) {
        if (e.target.closest('.remove-bill-row') && document.querySelectorAll('.bill-row').length > 1) {
            e.target.closest('.bill-row').remove();
            calculateTotal();
        }
    });

    calculateTotal();
});
</script>
@endsection
