@extends('layouts.app')

@section('title', 'Generate Sales Invoice')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Generate Sales Invoice</h4>
        <p class="text-muted small mb-0">Issues official tax invoice, decrements stock batches, supports line/bill discounts and immediate counter payment collection</p>
    </div>
    <a href="{{ route('sales.invoices.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Invoices
    </a>
</div>

<form action="{{ route('sales.invoices.store') }}" method="POST" id="invoiceForm">
    @csrf

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
                                <option value="{{ $c->id }}" data-price-level="{{ $c->pricing_level ?? 'normal' }}" {{ (old('customer_id', $so?->customer_id) == $c->id) ? 'selected' : '' }}>
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
                                <option value="{{ $g->id }}" {{ old('godown_id') == $g->id ? 'selected' : '' }}>{{ $g->name }} ({{ $g->code }})</option>
                            @endforeach
                        </select>
                        @error('godown_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Against Sales Order (SO)</label>
                        <input type="hidden" name="sales_order_id" value="{{ $so?->id }}">
                        <input type="text" class="form-control" value="{{ $so ? $so->order_number . ' - ' . $so->customer->name : 'None (Direct Invoice)' }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Invoice Date <span class="text-danger">*</span></label>
                        <input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date', date('Y-m-d')) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Payment Mode / Settlement <span class="text-danger">*</span></label>
                        <select name="payment_mode" id="salesPaymentModeSelect" class="form-select" required>
                            <option value="Credit" {{ old('payment_mode', 'Credit') == 'Credit' ? 'selected' : '' }}>Credit Sale (Customer Payable Later)</option>
                            <option value="Cash" {{ old('payment_mode') == 'Cash' ? 'selected' : '' }}>Cash Immediate (Counter Sale)</option>
                            <option value="UPI" {{ old('payment_mode') == 'UPI' ? 'selected' : '' }}>UPI / Digital (Counter Sale)</option>
                            <option value="Bank Transfer" {{ old('payment_mode') == 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer (NEFT/RTGS)</option>
                            <option value="Cheque" {{ old('payment_mode') == 'Cheque' ? 'selected' : '' }}>Cheque Payment</option>
                        </select>
                    </div>

                    <!-- Immediate Payment Receipt Box -->
                    <div id="salesReceiptBlock" class="p-3 border rounded bg-light mb-3" style="display: none;">
                        <h6 class="fw-bold small text-success mb-2"><i class="bi bi-wallet2 me-1"></i> Immediate Payment Collection</h6>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Deposit To Ledger <span class="text-danger">*</span></label>
                            <select name="receipt_ledger_id" class="form-select form-select-sm">
                                <option value="">Select Cash or Bank Account</option>
                                @foreach($bankLedgers as $bl)
                                    <option value="{{ $bl->id }}" {{ old('receipt_ledger_id') == $bl->id ? 'selected' : '' }}>{{ $bl->name }} (₹{{ number_format($bl->current_balance, 2) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small fw-semibold">Amount Collected (₹)</label>
                            <input type="number" step="0.01" min="0" name="amount_collected" id="salesAmountCollected" class="form-control form-control-sm" value="{{ old('amount_collected') }}" placeholder="Full or partial payment">
                        </div>
                        <div>
                            <label class="form-label small fw-semibold">Transaction / UTR #</label>
                            <input type="text" name="transaction_reference" class="form-control form-control-sm" value="{{ old('transaction_reference') }}" placeholder="UPI ref, Cheque #, etc.">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Payment Terms & Due Date</label>
                        <div class="row g-2">
                            <div class="col-6">
                                <input type="text" name="payment_terms" class="form-control form-control-sm" value="{{ old('payment_terms', 'Net 30 Days') }}" placeholder="Terms">
                            </div>
                            <div class="col-6">
                                <input type="date" name="due_date" class="form-control form-control-sm" value="{{ old('due_date', now()->addDays(30)->toDateString()) }}">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks / Delivery Notes</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Vehicle number, LR number, or customer PO">{{ old('remarks') }}</textarea>
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
                                @if($so && $so->items->count())
                                    @foreach($so->items as $idx => $si)
                                        <tr class="inv-row">
                                            <td>
                                                <input type="hidden" name="items[{{ $idx }}][item_id]" value="{{ $si->item_id }}">
                                                <div class="fw-bold">{{ $si->item->name }}</div>
                                                <small class="text-muted">{{ $si->item->code }} | Base: {{ $si->item->unit->symbol ?? '' }}</small>
                                            </td>
                                            <td>
                                                <select name="items[{{ $idx }}][unit_id]" class="form-select form-select-sm inv-unit-select">
                                                    <option value="">Base Unit</option>
                                                    @foreach($units as $u)
                                                        <option value="{{ $u->id }}" {{ $si->item->unit_id == $u->id ? 'selected' : '' }}>{{ $u->name }} ({{ $u->symbol }})</option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $idx }}][batch_number]" class="form-control form-control-sm" value="DEFAULT">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0.01" name="items[{{ $idx }}][quantity]" class="form-control form-control-sm inv-qty" value="{{ $si->quantity }}" required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" name="items[{{ $idx }}][rate]" class="form-control form-control-sm inv-rate" value="{{ $si->selling_price }}" required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" name="items[{{ $idx }}][discount_amount]" class="form-control form-control-sm inv-disc" value="0.00">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" max="100" name="items[{{ $idx }}][tax_percentage]" class="form-control form-control-sm inv-gst" value="{{ $si->tax_percentage ?? 18 }}">
                                            </td>
                                            <td class="text-end fw-semibold inv-line-total">₹ 0.00</td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-inv-row">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr class="inv-row">
                                        <td>
                                            <select name="items[0][item_id]" class="form-select form-select-sm inv-item-select" required>
                                                <option value="">Select Item</option>
                                                @foreach($items as $it)
                                                    <option value="{{ $it->id }}" data-min="{{ $it->min_price }}" data-normal="{{ $it->normal_price }}" data-max="{{ $it->max_price }}" data-gst="{{ $it->gst_percentage }}" data-unit-id="{{ $it->unit_id }}" data-unit="{{ $it->unit->symbol ?? '' }}">
                                                        {{ $it->name }} ({{ $it->code }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted item-unit-badge"></small>
                                        </td>
                                        <td>
                                            <select name="items[0][unit_id]" class="form-select form-select-sm inv-unit-select">
                                                <option value="">Item Base Unit</option>
                                                @foreach($units as $u)
                                                    <option value="{{ $u->id }}">{{ $u->name }} ({{ $u->symbol }})</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="items[0][batch_number]" class="form-control form-control-sm" value="DEFAULT">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0.01" name="items[0][quantity]" class="form-control form-control-sm inv-qty" value="1" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" name="items[0][rate]" class="form-control form-control-sm inv-rate" value="0.00" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" name="items[0][discount_amount]" class="form-control form-control-sm inv-disc" value="0.00">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" max="100" name="items[0][tax_percentage]" class="form-control form-control-sm inv-gst" value="18">
                                        </td>
                                        <td class="text-end fw-semibold inv-line-total">₹ 0.00</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-inv-row" disabled>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-light p-3">
                    <div class="row align-items-center">
                        <div class="col-md-5">
                            <label class="form-label small fw-bold mb-1">Additional Bill Discount (₹)</label>
                            <input type="number" step="0.01" min="0" name="discount_amount" id="invDiscountInput" class="form-control form-control-sm" value="{{ old('discount_amount', 0) }}" placeholder="e.g. 100.00">
                            <small class="text-muted">Applied over and above item discounts.</small>
                        </div>
                        <div class="col-md-7 text-end">
                            <div class="small text-muted">Subtotal: <span class="fw-semibold text-dark" id="displaySubtotal">₹ 0.00</span> | Discounts: <span class="fw-semibold text-danger" id="displayDiscount">-₹ 0.00</span></div>
                            <div class="small text-muted">Total GST Tax: <span class="fw-semibold text-dark" id="displayTax">₹ 0.00</span></div>
                            <div class="fs-5 text-muted mt-1">Invoice Grand Total: <span class="fw-bold text-success fs-4" id="invGrandTotal">₹ 0.00</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary px-4 py-2">
                    <i class="bi bi-check2-circle me-1"></i> Issue Invoice, Dispatch Stock & Post Accounts
                </button>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = {{ $so && $so->items->count() ? $so->items->count() : 1 }};
    const tableBody = document.querySelector('#invItemsTable tbody');
    const addRowBtn = document.getElementById('addInvRowBtn');
    const grandTotalSpan = document.getElementById('invGrandTotal');
    const displaySubtotal = document.getElementById('displaySubtotal');
    const displayDiscount = document.getElementById('displayDiscount');
    const displayTax = document.getElementById('displayTax');
    const invDiscountInput = document.getElementById('invDiscountInput');
    const customerSelect = document.getElementById('invCustomerSelect');
    const salesPaymentModeSelect = document.getElementById('salesPaymentModeSelect');
    const salesReceiptBlock = document.getElementById('salesReceiptBlock');
    const salesAmountCollected = document.getElementById('salesAmountCollected');

    function toggleSalesPaymentBlock() {
        if (salesPaymentModeSelect.value !== 'Credit') {
            salesReceiptBlock.style.display = 'block';
        } else {
            salesReceiptBlock.style.display = 'none';
        }
    }
    salesPaymentModeSelect.addEventListener('change', toggleSalesPaymentBlock);
    toggleSalesPaymentBlock();

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

        if (salesPaymentModeSelect.value !== 'Credit' && (!salesAmountCollected.value || parseFloat(salesAmountCollected.value) === 0)) {
            salesAmountCollected.value = grandTotal.toFixed(2);
        }
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
            const unitId = selectedOpt.dataset.unitId || '';
            const row = e.target.closest('.inv-row');
            row.querySelector('.inv-rate').value = price;
            row.querySelector('.inv-gst').value = gst;
            const unitSelect = row.querySelector('.inv-unit-select');
            if (unitSelect && unitId) {
                unitSelect.value = unitId;
            }
            const unitBadge = row.querySelector('.item-unit-badge');
            if (unitBadge) unitBadge.innerText = unit ? 'Base: ' + unit : '';
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
