@extends('layouts.app')

@section('title', 'Generate Sales Invoice')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Generate Sales Invoice</h4>
        <p class="text-muted small mb-0">Issues official tax invoice, decrements stock from godown, and debits customer account</p>
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
            <div class="card shadow-sm border-0 h-100">
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
                        <label class="form-label fw-bold">Payment Terms</label>
                        <input type="text" name="payment_terms" class="form-control" value="{{ old('payment_terms', 'Net 30 Days') }}" placeholder="e.g. Immediate / Net 30">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Due Date</label>
                        <input type="date" name="due_date" class="form-control" value="{{ old('due_date', now()->addDays(30)->toDateString()) }}">
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
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 32%;">Item</th>
                                    <th style="width: 18%;">Batch #</th>
                                    <th style="width: 14%;">Qty</th>
                                    <th style="width: 16%;">Rate (₹)</th>
                                    <th style="width: 10%;">GST %</th>
                                    <th class="text-center" style="width: 10%;">Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($so && $so->items->count())
                                    @foreach($so->items as $idx => $si)
                                        <tr class="inv-row">
                                            <td>
                                                <input type="hidden" name="items[{{ $idx }}][item_id]" value="{{ $si->item_id }}">
                                                <div class="fw-bold">{{ $si->item->name }}</div>
                                                <small class="text-muted">{{ $si->item->code }}</small>
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
                                                <input type="number" step="0.01" min="0" max="100" name="items[{{ $idx }}][tax_percentage]" class="form-control form-control-sm inv-gst" value="{{ $si->tax_percentage ?? 18 }}">
                                            </td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-inv-row" disabled>
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr class="inv-row">
                                        <td>
                                            <select name="items[0][item_id]" class="form-select inv-item-select" required>
                                                <option value="">Select Item</option>
                                                @foreach($items as $it)
                                                    <option value="{{ $it->id }}" data-min="{{ $it->min_price }}" data-normal="{{ $it->normal_price }}" data-max="{{ $it->max_price }}" data-gst="{{ $it->gst_percentage }}">
                                                        {{ $it->name }} ({{ $it->code }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="items[0][batch_number]" class="form-control" value="DEFAULT">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0.01" name="items[0][quantity]" class="form-control inv-qty" value="1" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" name="items[0][rate]" class="form-control inv-rate" value="0.00" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" max="100" name="items[0][tax_percentage]" class="form-control inv-gst" value="18">
                                        </td>
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
                        <div class="col-md-6">
                            <span class="badge bg-danger bg-opacity-25 text-danger p-2">
                                <i class="bi bi-shield-lock me-1"></i> Outward stock will be safely deducted upon invoice generation.
                            </span>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="fs-6 text-muted">Invoice Grand Total: <span class="fw-bold text-success fs-5" id="invGrandTotal">₹ 0.00</span></div>
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
    const customerSelect = document.getElementById('invCustomerSelect');

    function getCustomerPriceLevel() {
        const opt = customerSelect.options[customerSelect.selectedIndex];
        return opt ? (opt.dataset.priceLevel || 'normal') : 'normal';
    }

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.inv-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.inv-qty').value) || 0;
            const rate = parseFloat(row.querySelector('.inv-rate').value) || 0;
            const gst = parseFloat(row.querySelector('.inv-gst').value) || 0;
            const sub = qty * rate;
            const tax = sub * (gst / 100);
            total += (sub + tax);
        });
        grandTotalSpan.innerText = '₹ ' + total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    tableBody.addEventListener('change', function(e) {
        if (e.target.classList.contains('inv-item-select')) {
            const selectedOpt = e.target.options[e.target.selectedIndex];
            const level = getCustomerPriceLevel();
            let price = selectedOpt.dataset.normal || 0;
            if (level === 'minimum') price = selectedOpt.dataset.min || price;
            if (level === 'maximum') price = selectedOpt.dataset.max || price;

            const gst = selectedOpt.dataset.gst || 18;
            const row = e.target.closest('.inv-row');
            row.querySelector('.inv-rate').value = price;
            row.querySelector('.inv-gst').value = gst;
        }
        calculateTotal();
    });

    tableBody.addEventListener('input', calculateTotal);

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
            if (input.classList.contains('inv-item-select')) input.selectedIndex = 0;
        });
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

