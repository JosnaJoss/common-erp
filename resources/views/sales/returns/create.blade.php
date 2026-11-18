@extends('layouts.app')

@section('title', 'Record Sales Return / Credit Note')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Create Sales Return (Credit Note)</h4>
        <p class="text-muted small mb-0">Record items returned by customer, inspect whether usable to restock, and post accounting credit note.</p>
    </div>
    <a href="{{ route('sales.returns.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Returns
    </a>
</div>

<form action="{{ route('sales.returns.store') }}" method="POST" id="salesReturnForm">
    @csrf

    <div class="row g-4">
        <!-- Header Info -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-success"><i class="bi bi-arrow-return-left me-1"></i> Credit Note Header</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" class="form-select @error('customer_id') is-invalid @enderror" required>
                            <option value="">Choose Customer</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" {{ (old('customer_id', $invoice?->customer_id) == $c->id) ? 'selected' : '' }}>
                                    {{ $c->name }} ({{ $c->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Receive Into Godown <span class="text-danger">*</span></label>
                        <select name="godown_id" class="form-select @error('godown_id') is-invalid @enderror" required>
                            <option value="">Choose Godown</option>
                            @foreach($godowns as $g)
                                <option value="{{ $g->id }}" {{ (old('godown_id', $invoice?->godown_id) == $g->id) ? 'selected' : '' }}>{{ $g->name }} ({{ $g->code }})</option>
                            @endforeach
                        </select>
                        @error('godown_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Against Sales Invoice (Optional)</label>
                        <input type="hidden" name="sales_invoice_id" value="{{ $invoice?->id }}">
                        <input type="text" class="form-control" value="{{ $invoice ? $invoice->invoice_number . ' - ' . $invoice->customer->name : 'Direct Customer Return' }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Return Date <span class="text-danger">*</span></label>
                        <input type="date" name="return_date" class="form-control" value="{{ old('return_date', date('Y-m-d')) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Stock Quality Condition <span class="text-danger">*</span></label>
                        <select name="stock_condition" class="form-select" required>
                            <option value="accept_to_stock" {{ old('stock_condition') == 'accept_to_stock' ? 'selected' : '' }}>Accept Back to Active Stock (Restock in Godown)</option>
                            <option value="damaged_scrap" {{ old('stock_condition') == 'damaged_scrap' ? 'selected' : '' }}>Damaged / Scrap (Do not restock in godown)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Return Reason / Fault Description <span class="text-danger">*</span></label>
                        <textarea name="return_reason" class="form-control @error('return_reason') is-invalid @enderror" rows="3" placeholder="Reason for customer return (e.g. wrong size, damaged fabric, defective batch)" required>{{ old('return_reason') }}</textarea>
                        @error('return_reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-success"><i class="bi bi-box-seam me-1"></i> Returned Items & Rates</h6>
                    <button type="button" class="btn btn-sm btn-outline-success" id="addSalesRetItemBtn">
                        <i class="bi bi-plus-lg me-1"></i> Add Line
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0" id="salesReturnItemsTable">
                            <thead class="table-light small">
                                <tr>
                                    <th style="width: 32%;">Item</th>
                                    <th style="width: 18%;">Batch #</th>
                                    <th style="width: 14%;">Qty</th>
                                    <th style="width: 16%;">Rate (₹)</th>
                                    <th style="width: 10%;">GST %</th>
                                    <th class="text-end" style="width: 10%;">Total</th>
                                    <th class="text-center" style="width: 6%;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @if($invoice && $invoice->items->count())
                                    @foreach($invoice->items as $idx => $ii)
                                        <tr class="sales-ret-row">
                                            <td>
                                                <select name="items[{{ $idx }}][item_id]" class="form-select form-select-sm sret-item-select" required>
                                                    @foreach($items as $it)
                                                        <option value="{{ $it->id }}" data-rate="{{ $it->normal_price ?? 0 }}" data-gst="{{ $it->gst_percentage }}" {{ $ii->item_id == $it->id ? 'selected' : '' }}>
                                                            {{ $it->name }} ({{ $it->code }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $idx }}][batch_number]" class="form-control form-control-sm" value="{{ $ii->batch_number }}">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0.01" name="items[{{ $idx }}][return_quantity]" class="form-control form-control-sm sret-qty" value="{{ $ii->quantity }}" required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" name="items[{{ $idx }}][rate]" class="form-control form-control-sm sret-rate" value="{{ $ii->rate }}" required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" max="100" name="items[{{ $idx }}][tax_percentage]" class="form-control form-control-sm sret-gst" value="{{ $ii->tax_percentage ?? 18 }}">
                                            </td>
                                            <td class="text-end fw-semibold sret-line-total">₹ 0.00</td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-sret-row">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr class="sales-ret-row">
                                        <td>
                                            <select name="items[0][item_id]" class="form-select form-select-sm sret-item-select" required>
                                                <option value="">Select Item</option>
                                                @foreach($items as $it)
                                                    <option value="{{ $it->id }}" data-rate="{{ $it->normal_price ?? 0 }}" data-gst="{{ $it->gst_percentage }}">
                                                        {{ $it->name }} ({{ $it->code }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="items[0][batch_number]" class="form-control form-control-sm" value="DEFAULT">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0.01" name="items[0][return_quantity]" class="form-control form-control-sm sret-qty" value="1" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" name="items[0][rate]" class="form-control form-control-sm sret-rate" value="0.00" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" max="100" name="items[0][tax_percentage]" class="form-control form-control-sm sret-gst" value="18">
                                        </td>
                                        <td class="text-end fw-semibold sret-line-total">₹ 0.00</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-sret-row" disabled>
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
                            <span class="badge bg-success bg-opacity-25 text-success p-2">
                                <i class="bi bi-shield-check me-1"></i> Customer balance will be credited and credit note journal entry posted.
                            </span>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="small text-muted">Subtotal: <span class="fw-semibold text-dark" id="displaySubtotal">₹ 0.00</span> | Tax: <span class="fw-semibold text-dark" id="displayTax">₹ 0.00</span></div>
                            <div class="fs-5 text-muted mt-1">Credit Note Total: <span class="fw-bold text-success fs-4" id="sretGrandTotal">₹ 0.00</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-success px-4 py-2">
                    <i class="bi bi-check2-circle me-1"></i> Issue Credit Note & Restock Goods
                </button>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = {{ $invoice && $invoice->items->count() ? $invoice->items->count() : 1 }};
    const tableBody = document.querySelector('#salesReturnItemsTable tbody');
    const addRowBtn = document.getElementById('addSalesRetItemBtn');
    const grandTotalSpan = document.getElementById('sretGrandTotal');
    const displaySubtotal = document.getElementById('displaySubtotal');
    const displayTax = document.getElementById('displayTax');

    function calculateTotal() {
        let subtotal = 0;
        let taxTotal = 0;

        document.querySelectorAll('.sales-ret-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.sret-qty').value) || 0;
            const rate = parseFloat(row.querySelector('.sret-rate').value) || 0;
            const gst = parseFloat(row.querySelector('.sret-gst').value) || 0;

            const lineGross = qty * rate;
            const lineTax = lineGross * (gst / 100);
            const lineTotal = lineGross + lineTax;

            row.querySelector('.sret-line-total').innerText = '₹ ' + lineTotal.toFixed(2);

            subtotal += lineGross;
            taxTotal += lineTax;
        });

        const grandTotal = subtotal + taxTotal;
        displaySubtotal.innerText = '₹ ' + subtotal.toFixed(2);
        displayTax.innerText = '₹ ' + taxTotal.toFixed(2);
        grandTotalSpan.innerText = '₹ ' + grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    tableBody.addEventListener('change', function(e) {
        if (e.target.classList.contains('sret-item-select')) {
            const selectedOpt = e.target.options[e.target.selectedIndex];
            const rate = selectedOpt.dataset.rate || 0;
            const gst = selectedOpt.dataset.gst || 18;
            const row = e.target.closest('.sales-ret-row');
            row.querySelector('.sret-rate').value = rate;
            row.querySelector('.sret-gst').value = gst;
        }
        calculateTotal();
    });

    tableBody.addEventListener('input', calculateTotal);

    addRowBtn.addEventListener('click', function() {
        const firstRow = document.querySelector('.sales-ret-row');
        const newRow = firstRow.cloneNode(true);
        newRow.querySelectorAll('input, select').forEach(input => {
            const name = input.getAttribute('name');
            if (name) {
                input.setAttribute('name', name.replace(/\[\d+\]/, '[' + rowIndex + ']'));
            }
            if (input.classList.contains('sret-qty')) input.value = 1;
            if (input.classList.contains('sret-rate')) input.value = '0.00';
            if (input.classList.contains('sret-item-select')) input.selectedIndex = 0;
        });
        newRow.querySelector('.remove-sret-row').removeAttribute('disabled');
        tableBody.appendChild(newRow);
        rowIndex++;
        calculateTotal();
    });

    tableBody.addEventListener('click', function(e) {
        if (e.target.closest('.remove-sret-row') && document.querySelectorAll('.sales-ret-row').length > 1) {
            e.target.closest('.sales-ret-row').remove();
            calculateTotal();
        }
    });

    calculateTotal();
});
</script>
@endsection
