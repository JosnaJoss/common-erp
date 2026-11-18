@extends('layouts.app')

@section('title', 'Edit Purchase Invoice - ' . $entry->purchase_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Edit Purchase Bill Entry: <span class="text-primary">{{ $entry->purchase_number }}</span></h4>
        <p class="text-muted small mb-0">Modify supplier bill details, update items, rates, discounts, and recalculate inventory stock batches and journal entries safely.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('purchase.entries.show', $entry) }}" class="btn btn-outline-secondary">
            <i class="bi bi-eye me-1"></i> View Bill
        </a>
        <a href="{{ route('purchase.entries.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Bills
        </a>
    </div>
</div>

<form action="{{ route('purchase.entries.update', $entry) }}" method="POST" id="purchaseEntryEditForm">
    @csrf
    @method('PUT')

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
                                <option value="{{ $s->id }}" {{ old('supplier_id', $entry->supplier_id) == $s->id ? 'selected' : '' }}>
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
                                <option value="{{ $g->id }}" {{ old('godown_id', $entry->godown_id) == $g->id ? 'selected' : '' }}>{{ $g->name }} ({{ $g->code }})</option>
                            @endforeach
                        </select>
                        @error('godown_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Supplier Invoice Number <span class="text-danger">*</span></label>
                        <input type="text" name="invoice_number" class="form-control @error('invoice_number') is-invalid @enderror" value="{{ old('invoice_number', $entry->invoice_number) }}" required>
                        @error('invoice_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Invoice Date <span class="text-danger">*</span></label>
                        <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', $entry->invoice_date->format('Y-m-d')) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Payment Type <span class="text-danger">*</span></label>
                        <select name="payment_type" id="paymentTypeSelect" class="form-select" required>
                            <option value="Credit" {{ old('payment_type', $entry->payment_type) == 'Credit' ? 'selected' : '' }}>Credit (Bill Payable Later)</option>
                            <option value="Cash" {{ old('payment_type', $entry->payment_type) == 'Cash' ? 'selected' : '' }}>Cash</option>
                            <option value="Bank" {{ old('payment_type', $entry->payment_type) == 'Bank' ? 'selected' : '' }}>Bank Transfer</option>
                            <option value="UPI" {{ old('payment_type', $entry->payment_type) == 'UPI' ? 'selected' : '' }}>UPI / Digital</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks / Notes</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Notes, GRN reference, or transporter details">{{ old('remarks', $entry->remarks) }}</textarea>
                    </div>

                    <div class="alert alert-warning small py-2">
                        <i class="bi bi-info-circle me-1"></i> Previously paid amount: <strong>₹{{ number_format($entry->paid_amount, 2) }}</strong>. Balance will adjust automatically based on updated total.
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
                                    <th style="width: 24%;">Item</th>
                                    <th style="width: 13%;">Unit</th>
                                    <th style="width: 13%;">Batch #</th>
                                    <th style="width: 10%;">Qty</th>
                                    <th style="width: 11%;">Rate (₹)</th>
                                    <th style="width: 9%;">Disc (₹)</th>
                                    <th style="width: 7%;">GST %</th>
                                    <th class="text-end" style="width: 10%;">Total (₹)</th>
                                    <th class="text-center" style="width: 5%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($entry->items as $idx => $itemLine)
                                <tr class="bill-row">
                                    <td>
                                        <select name="items[{{ $idx }}][item_id]" class="form-select form-select-sm bill-item-select" required>
                                            <option value="">Select Item</option>
                                            @foreach($items as $it)
                                                <option value="{{ $it->id }}" data-rate="{{ $it->min_price ?? 0 }}" data-gst="{{ $it->gst_percentage }}" data-unit-id="{{ $it->unit_id }}" data-unit="{{ $it->unit->symbol ?? '' }}" {{ $itemLine->item_id == $it->id ? 'selected' : '' }}>
                                                    {{ $it->name }} ({{ $it->code }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted item-unit-badge">{{ $itemLine->item->unit->symbol ? 'Base: ' . $itemLine->item->unit->symbol : '' }}</small>
                                    </td>
                                    <td>
                                        <select name="items[{{ $idx }}][unit_id]" class="form-select form-select-sm bill-unit-select">
                                            <option value="">Item Base Unit</option>
                                            @foreach($units as $u)
                                                <option value="{{ $u->id }}" {{ ($itemLine->unit_id ?? $itemLine->item->unit_id) == $u->id ? 'selected' : '' }}>
                                                    {{ $u->name }} ({{ $u->symbol }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="items[{{ $idx }}][batch_number]" class="form-control form-control-sm" value="{{ $itemLine->batch_number }}">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0.01" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm bill-qty" value="{{ $itemLine->entered_quantity ?: $itemLine->quantity }}" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="items[{{ $idx }}][rate]" class="form-control form-control-sm bill-rate" value="{{ $itemLine->rate }}" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="items[{{ $idx }}][discount_amount]" class="form-control form-control-sm bill-disc" value="{{ $itemLine->discount_amount ?? 0 }}">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" max="100" name="items[{{ $idx }}][tax_percentage]" class="form-control form-control-sm bill-gst" value="{{ $itemLine->tax_percentage ?? 18 }}">
                                    </td>
                                    <td class="text-end fw-semibold bill-line-total">
                                        ₹ {{ number_format($itemLine->line_total, 2) }}
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-bill-row" {{ count($entry->items) <= 1 ? 'disabled' : '' }}>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Additional Charges & Landed Cost Allocation -->
                <div class="card-body border-top bg-light p-3">
                    <h6 class="fw-bold small text-secondary mb-3"><i class="bi bi-truck me-1"></i> Additional Procurement Charges & Landed Cost Allocation</h6>
                    <div class="row g-2 mb-2">
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Freight (₹)</label>
                            <input type="number" step="0.01" min="0" name="freight_charges" id="freightInput" class="form-control form-control-sm add-charge" value="{{ old('freight_charges', $entry->freight_charges ?? 0) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Packaging (₹)</label>
                            <input type="number" step="0.01" min="0" name="packaging_charges" id="packagingInput" class="form-control form-control-sm add-charge" value="{{ old('packaging_charges', $entry->packaging_charges ?? 0) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Loading (₹)</label>
                            <input type="number" step="0.01" min="0" name="loading_charges" id="loadingInput" class="form-control form-control-sm add-charge" value="{{ old('loading_charges', $entry->loading_charges ?? 0) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small mb-1">Other Charges (₹)</label>
                            <input type="number" step="0.01" min="0" name="other_charges" id="otherInput" class="form-control form-control-sm add-charge" value="{{ old('other_charges', $entry->other_charges ?? 0) }}">
                        </div>
                    </div>
                    <div class="row g-2">
                        <div class="col-md-4">
                            <label class="form-label small mb-1">Round-Off (₹)</label>
                            <input type="number" step="0.01" name="round_off" id="roundOffInput" class="form-control form-control-sm" value="{{ old('round_off', $entry->round_off ?? 0) }}">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small mb-1">Landed Cost Allocation Method</label>
                            <select name="charge_allocation_method" class="form-select form-select-sm">
                                <option value="by_value" {{ old('charge_allocation_method', $entry->charge_allocation_method ?? 'by_value') == 'by_value' ? 'selected' : '' }}>Allocate by Value (Proportional to item cost - Recommended)</option>
                                <option value="by_quantity" {{ old('charge_allocation_method', $entry->charge_allocation_method ?? 'by_value') == 'by_quantity' ? 'selected' : '' }}>Allocate by Quantity (Proportional to units)</option>
                                <option value="equal" {{ old('charge_allocation_method', $entry->charge_allocation_method ?? 'by_value') == 'equal' ? 'selected' : '' }}>Allocate Equally (Split evenly across items)</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light p-3 border-top">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold mb-1">Additional Bill Discount (₹)</label>
                            <input type="number" step="0.01" min="0" name="discount_amount" id="billDiscountInput" class="form-control form-control-sm" value="{{ old('discount_amount', max(0, $entry->discount_amount - $entry->items->sum('discount_amount'))) }}">
                            <small class="text-muted">Applied over and above line discounts.</small>
                        </div>
                        <div class="col-md-7 text-end">
                            <div class="small text-muted">Subtotal: <span class="fw-semibold text-dark" id="displaySubtotal">₹ {{ number_format($entry->subtotal, 2) }}</span> | Total Discounts: <span class="fw-semibold text-danger" id="displayDiscount">-₹ {{ number_format($entry->discount_amount, 2) }}</span></div>
                            <div class="small text-muted">Total GST Tax: <span class="fw-semibold text-dark" id="displayTax">₹ {{ number_format($entry->tax_amount, 2) }}</span> | Extra Charges: <span class="fw-semibold text-dark" id="displayExtraCharges">₹ {{ number_format(($entry->freight_charges ?? 0) + ($entry->packaging_charges ?? 0) + ($entry->loading_charges ?? 0) + ($entry->other_charges ?? 0) + ($entry->round_off ?? 0), 2) }}</span></div>
                            <div class="fs-5 text-muted mt-1">Updated Grand Total: <span class="fw-bold text-primary fs-4" id="billGrandTotal">₹ {{ number_format($entry->grand_total, 2) }}</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary px-4 py-2">
                    <i class="bi bi-check2-circle me-1"></i> Update Purchase Bill & Sync Stock/Ledger
                </button>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = {{ count($entry->items) }};
    const tableBody = document.querySelector('#billItemsTable tbody');
    const addRowBtn = document.getElementById('addBillItemBtn');
    const grandTotalSpan = document.getElementById('billGrandTotal');
    const displaySubtotal = document.getElementById('displaySubtotal');
    const displayDiscount = document.getElementById('displayDiscount');
    const displayTax = document.getElementById('displayTax');
    const displayExtraCharges = document.getElementById('displayExtraCharges');
    const billDiscountInput = document.getElementById('billDiscountInput');
    const freightInput = document.getElementById('freightInput');
    const packagingInput = document.getElementById('packagingInput');
    const loadingInput = document.getElementById('loadingInput');
    const otherInput = document.getElementById('otherInput');
    const roundOffInput = document.getElementById('roundOffInput');

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

        const freight = parseFloat(freightInput ? freightInput.value : 0) || 0;
        const packaging = parseFloat(packagingInput ? packagingInput.value : 0) || 0;
        const loading = parseFloat(loadingInput ? loadingInput.value : 0) || 0;
        const other = parseFloat(otherInput ? otherInput.value : 0) || 0;
        const roundOff = parseFloat(roundOffInput ? roundOffInput.value : 0) || 0;
        const totalExtra = freight + packaging + loading + other + roundOff;

        const grandTotal = Math.max(0, (subtotal - totalDiscount) + taxTotal + totalExtra);

        displaySubtotal.innerText = '₹ ' + subtotal.toFixed(2);
        displayDiscount.innerText = '-₹ ' + totalDiscount.toFixed(2);
        displayTax.innerText = '₹ ' + taxTotal.toFixed(2);
        if (displayExtraCharges) displayExtraCharges.innerText = '₹ ' + totalExtra.toFixed(2);
        grandTotalSpan.innerText = '₹ ' + grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    tableBody.addEventListener('change', function(e) {
        if (e.target.classList.contains('bill-item-select')) {
            const selectedOpt = e.target.options[e.target.selectedIndex];
            const rate = selectedOpt.dataset.rate || 0;
            const gst = selectedOpt.dataset.gst || 18;
            const unit = selectedOpt.dataset.unit || '';
            const unitId = selectedOpt.dataset.unitId || '';
            const row = e.target.closest('.bill-row');
            row.querySelector('.bill-rate').value = rate;
            row.querySelector('.bill-gst').value = gst;
            const unitSelect = row.querySelector('.bill-unit-select');
            if (unitSelect && unitId) {
                unitSelect.value = unitId;
            }
            const unitBadge = row.querySelector('.item-unit-badge');
            if (unitBadge) unitBadge.innerText = unit ? 'Base: ' + unit : '';
        }
        calculateTotal();
    });

    tableBody.addEventListener('input', calculateTotal);
    billDiscountInput.addEventListener('input', calculateTotal);
    if (freightInput) freightInput.addEventListener('input', calculateTotal);
    if (packagingInput) packagingInput.addEventListener('input', calculateTotal);
    if (loadingInput) loadingInput.addEventListener('input', calculateTotal);
    if (otherInput) otherInput.addEventListener('input', calculateTotal);
    if (roundOffInput) roundOffInput.addEventListener('input', calculateTotal);

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
