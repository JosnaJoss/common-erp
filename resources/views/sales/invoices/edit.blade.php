@extends('layouts.app')

@section('title', 'Edit Sales Invoice - ' . $invoice->invoice_number)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Edit Sales Invoice: <span class="text-primary">{{ $invoice->invoice_number }}</span></h4>
        <p class="text-muted small mb-0">Modify invoice details, items, quantities, rates, discounts, and safely sync inventory stock batches and journal entries.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('sales.invoices.show', $invoice) }}" class="btn btn-outline-secondary">
            <i class="bi bi-eye me-1"></i> View Invoice
        </a>
        <a href="{{ route('sales.invoices.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-1"></i> Back to Invoices
        </a>
    </div>
</div>

<form action="{{ route('sales.invoices.update', $invoice) }}" method="POST" id="invoiceEditForm">
    @csrf
    @method('PUT')

    <div class="row g-4">
        <!-- Invoice Header -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-file-earmark-ruled me-1"></i> Invoice Meta</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" id="invCustomerSelect" class="form-select @error('customer_id') is-invalid @enderror" required>
                            <option value="">Choose Customer</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" data-price-level="{{ $c->pricing_level ?? 'normal' }}" {{ (old('customer_id', $invoice->customer_id) == $c->id) ? 'selected' : '' }}>
                                    {{ $c->name }} ({{ $c->city ?? 'Local' }}) - Level: {{ ucfirst($c->pricing_level ?? 'normal') }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Dispatch Godown / Warehouse <span class="text-danger">*</span></label>
                        <select name="godown_id" class="form-select @error('godown_id') is-invalid @enderror" required>
                            <option value="">Choose Warehouse</option>
                            @foreach($godowns as $g)
                                <option value="{{ $g->id }}" {{ (old('godown_id', $invoice->godown_id) == $g->id) ? 'selected' : '' }}>{{ $g->name }} ({{ $g->code }})</option>
                            @endforeach
                        </select>
                        @error('godown_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Invoice Date <span class="text-danger">*</span></label>
                        <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', $invoice->invoice_date->format('Y-m-d')) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Payment Terms & Due Date</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="text" name="payment_terms" class="form-control form-control-sm" value="{{ old('payment_terms', $invoice->payment_terms) }}" placeholder="Terms">
                            </div>
                            <div class="col-6">
                                <input type="date" name="due_date" class="form-control form-control-sm" value="{{ old('due_date', $invoice->due_date ? $invoice->due_date->format('Y-m-d') : '') }}">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks / Delivery Notes</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Vehicle number, LR number, or customer PO">{{ old('remarks', $invoice->remarks) }}</textarea>
                    </div>

                    <div class="alert alert-warning small py-2">
                        <i class="bi bi-info-circle me-1"></i> Previously collected amount: <strong>₹{{ number_format($invoice->paid_amount, 2) }}</strong>. Balance due will adjust automatically.
                    </div>
                </div>
            </div>
        </div>

        <!-- Invoice Line Items -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-box-seam me-1"></i> Billed Items & Outward Batches</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addInvRowBtn">
                        <i class="bi bi-plus-lg me-1"></i> Add Line
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0" id="invItemsTable">
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
                                @foreach($invoice->items as $idx => $line)
                                    <tr class="inv-row">
                                        <td>
                                            <select name="items[{{ $idx }}][item_id]" class="form-select form-select-sm inv-item-select" required>
                                                <option value="">Select Item</option>
                                                @foreach($items as $it)
                                                    <option value="{{ $it->id }}" data-min="{{ $it->min_price }}" data-normal="{{ $it->normal_price }}" data-max="{{ $it->max_price }}" data-gst="{{ $it->gst_percentage }}" data-unit="{{ $it->unit->symbol ?? '' }}" {{ $line->item_id == $it->id ? 'selected' : '' }}>
                                                        {{ $it->name }} ({{ $it->code }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted item-unit-badge">{{ $line->item->unit->symbol ? 'Unit: ' . $line->item->unit->symbol : '' }}</small>
                                        </td>
                                        <td>
                                            <input type="text" name="items[{{ $idx }}][batch_number]" class="form-control form-control-sm" value="{{ $line->batch_number }}">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0.01" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm inv-qty" value="{{ $line->quantity }}" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" name="items[{{ $idx }}][rate]" class="form-control form-control-sm inv-rate" value="{{ $line->rate }}" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" name="items[{{ $idx }}][discount_amount]" class="form-control form-control-sm inv-disc" value="{{ $line->discount_amount ?? 0 }}">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" max="100" name="items[{{ $idx }}][tax_percentage]" class="form-control form-control-sm inv-gst" value="{{ $line->tax_percentage ?? 18 }}">
                                        </td>
                                        <td class="text-end fw-semibold inv-line-total">₹ {{ number_format($line->line_total, 2) }}</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-inv-row" {{ count($invoice->items) <= 1 ? 'disabled' : '' }}>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light p-3">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold mb-1">Additional Bill Discount (₹)</label>
                            <input type="number" step="0.01" min="0" name="discount_amount" id="invDiscountInput" class="form-control form-control-sm" value="{{ old('discount_amount', max(0, $invoice->discount_amount - $invoice->items->sum('discount_amount'))) }}">
                            <small class="text-muted">Applied over and above item discounts.</small>
                        </div>
                        <div class="col-md-7 text-end">
                            <div class="small text-muted">Subtotal: <span class="fw-semibold text-dark" id="displaySubtotal">₹ {{ number_format($invoice->subtotal, 2) }}</span> | Discounts: <span class="fw-semibold text-danger" id="displayDiscount">-₹ {{ number_format($invoice->discount_amount, 2) }}</span></div>
                            <div class="small text-muted">Total GST Tax: <span class="fw-semibold text-dark" id="displayTax">₹ {{ number_format($invoice->tax_amount, 2) }}</span></div>
                            <div class="fs-5 text-muted mt-1">Updated Grand Total: <span class="fw-bold text-success fs-4" id="invGrandTotal">₹ {{ number_format($invoice->grand_total, 2) }}</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary px-4 py-2">
                    <i class="bi bi-check2-circle me-1"></i> Update Sales Invoice & Sync Stock/Accounts
                </button>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = {{ count($invoice->items) }};
    const tableBody = document.querySelector('#invItemsTable tbody');
    const addRowBtn = document.getElementById('addInvRowBtn');
    const grandTotalSpan = document.getElementById('invGrandTotal');
    const displaySubtotal = document.getElementById('displaySubtotal');
    const displayDiscount = document.getElementById('displayDiscount');
    const displayTax = document.getElementById('displayTax');
    const invDiscountInput = document.getElementById('invDiscountInput');
    const customerSelect = document.getElementById('invCustomerSelect');

    function getCustomerPriceLevel() {
        const opt = customerSelect.options[customerSelect.selectedIndex];
        return opt ? (opt.dataset.priceLevel || 'normal') : 'normal';
    }

    function calculateTotal() {
        let subtotal = 0;
        let itemDiscountTotal = 0;
        let taxTotal = 0;

        document.querySelectorAll('.inv-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.inv-qty').value) || 0;
            const rate = parseFloat(row.querySelector('.inv-rate').value) || 0;
            const disc = parseFloat(row.querySelector('.inv-disc').value) || 0;
            const gst = parseFloat(row.querySelector('.inv-gst').value) || 0;

            const lineGross = qty * rate;
            const lineTaxable = Math.max(0, lineGross - disc);
            const lineTax = lineTaxable * (gst / 100);
            const lineTotal = lineTaxable + lineTax;

            row.querySelector('.inv-line-total').innerText = '₹ ' + lineTotal.toFixed(2);

            subtotal += lineGross;
            itemDiscountTotal += disc;
            taxTotal += lineTax;
        });

        const billDiscount = parseFloat(invDiscountInput.value) || 0;
        const totalDiscount = itemDiscountTotal + billDiscount;
        const grandTotal = Math.max(0, (subtotal - totalDiscount) + taxTotal);

        displaySubtotal.innerText = '₹ ' + subtotal.toFixed(2);
        displayDiscount.innerText = '-₹ ' + totalDiscount.toFixed(2);
        displayTax.innerText = '₹ ' + taxTotal.toFixed(2);
        grandTotalSpan.innerText = '₹ ' + grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    tableBody.addEventListener('change', function(e) {
        if (e.target.classList.contains('inv-item-select')) {
            const selectedOpt = e.target.options[e.target.selectedIndex];
            const level = getCustomerPriceLevel();
            let price = selectedOpt.dataset.normal || 0;
            if (level === 'minimum') price = selectedOpt.dataset.min || price;
            if (level === 'maximum') price = selectedOpt.dataset.max || price;

            const gst = selectedOpt.dataset.gst || 18;
            const unit = selectedOpt.dataset.unit || '';
            const row = e.target.closest('.inv-row');
            row.querySelector('.inv-rate').value = price;
            row.querySelector('.inv-gst').value = gst;
            const unitBadge = row.querySelector('.item-unit-badge');
            if (unitBadge) unitBadge.innerText = unit ? 'Unit: ' + unit : '';
        }
        calculateTotal();
    });

    tableBody.addEventListener('input', calculateTotal);
    invDiscountInput.addEventListener('input', calculateTotal);

    addRowBtn.addEventListener('click', function() {
        const firstRow = document.querySelector('.inv-row');
        const newRow = firstRow.cloneNode(true);
        newRow.querySelectorAll('input, select').forEach(input => {
            const name = input.getAttribute('name');
            if (name) {
                input.setAttribute('name', name.replace(/\[\d+\]/, '[' + rowIndex + ']'));
            }
            if (input.classList.contains('inv-qty')) input.value = 1;
            if (input.classList.contains('inv-rate')) input.value = '0.00';
            if (input.classList.contains('inv-disc')) input.value = '0.00';
            if (input.classList.contains('inv-item-select')) input.selectedIndex = 0;
        });
        const unitBadge = newRow.querySelector('.item-unit-badge');
        if (unitBadge) unitBadge.innerText = '';
        newRow.querySelector('.remove-inv-row').removeAttribute('disabled');
        tableBody.appendChild(newRow);
        rowIndex++;
        calculateTotal();
    });

    tableBody.addEventListener('click', function(e) {
        if (e.target.closest('.remove-inv-row') && document.querySelectorAll('.inv-row').length > 1) {
            e.target.closest('.inv-row').remove();
            calculateTotal();
        }
    });

    calculateTotal();
});
</script>
@endsection
