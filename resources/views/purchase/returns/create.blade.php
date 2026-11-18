@extends('layouts.app')

@section('title', 'Record Purchase Return / Debit Note')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Create Purchase Return (Debit Note)</h4>
        <p class="text-muted small mb-0">Return damaged or excess goods to vendor: deducts stock from godown and posts accounting debit note.</p>
    </div>
    <a href="{{ route('purchase.returns.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Returns
    </a>
</div>

<form action="{{ route('purchase.returns.store') }}" method="POST" id="purchaseReturnForm">
    @csrf

    <div class="row g-4">
        <!-- Header Info -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-danger"><i class="bi bi-arrow-return-left me-1"></i> Return Header</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Supplier <span class="text-danger">*</span></label>
                        <select name="supplier_id" class="form-select @error('supplier_id') is-invalid @enderror" required>
                            <option value="">Choose Supplier</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" {{ (old('supplier_id', $purchaseEntry?->supplier_id) == $s->id) ? 'selected' : '' }}>
                                    {{ $s->name }} ({{ $s->code }})
                                </option>
                            @endforeach
                        </select>
                        @error('supplier_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Dispatch From Godown <span class="text-danger">*</span></label>
                        <select name="godown_id" class="form-select @error('godown_id') is-invalid @enderror" required>
                            <option value="">Choose Godown</option>
                            @foreach($godowns as $g)
                                <option value="{{ $g->id }}" {{ (old('godown_id', $purchaseEntry?->godown_id) == $g->id) ? 'selected' : '' }}>{{ $g->name }} ({{ $g->code }})</option>
                            @endforeach
                        </select>
                        @error('godown_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Against Purchase Bill (Optional)</label>
                        <input type="hidden" name="purchase_entry_id" value="{{ $purchaseEntry?->id }}">
                        <input type="text" class="form-control" value="{{ $purchaseEntry ? $purchaseEntry->purchase_number . ' (' . $purchaseEntry->invoice_number . ')' : 'Direct Return' }}" readonly>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Return Date <span class="text-danger">*</span></label>
                        <input type="date" name="return_date" class="form-control" value="{{ old('return_date', date('Y-m-d')) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Return Reason <span class="text-danger">*</span></label>
                        <textarea name="return_reason" class="form-control @error('return_reason') is-invalid @enderror" rows="3" placeholder="Reason for rejection (e.g. damaged in transit, quality check failure, excess shipment)" required>{{ old('return_reason') }}</textarea>
                        @error('return_reason') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-danger"><i class="bi bi-box-seam me-1"></i> Returned Items & Quantities</h6>
                    <button type="button" class="btn btn-sm btn-outline-danger" id="addReturnItemBtn">
                        <i class="bi bi-plus-lg me-1"></i> Add Line
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0" id="returnItemsTable">
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
                                @if($purchaseEntry && $purchaseEntry->items->count())
                                    @foreach($purchaseEntry->items as $idx => $pi)
                                        <tr class="return-row">
                                            <td>
                                                <select name="items[{{ $idx }}][item_id]" class="form-select form-select-sm ret-item-select" required>
                                                    @foreach($items as $it)
                                                        <option value="{{ $it->id }}" data-rate="{{ $it->min_price ?? 0 }}" data-gst="{{ $it->gst_percentage }}" {{ $pi->item_id == $it->id ? 'selected' : '' }}>
                                                            {{ $it->name }} ({{ $it->code }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </td>
                                            <td>
                                                <input type="text" name="items[{{ $idx }}][batch_number]" class="form-control form-control-sm" value="{{ $pi->batch_number }}">
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0.01" name="items[{{ $idx }}][return_quantity]" class="form-control form-control-sm ret-qty" value="{{ $pi->quantity }}" required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" name="items[{{ $idx }}][rate]" class="form-control form-control-sm ret-rate" value="{{ $pi->rate }}" required>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" min="0" max="100" name="items[{{ $idx }}][tax_percentage]" class="form-control form-control-sm ret-gst" value="{{ $pi->tax_percentage ?? 18 }}">
                                            </td>
                                            <td class="text-end fw-semibold ret-line-total">₹ 0.00</td>
                                            <td class="text-center">
                                                <button type="button" class="btn btn-sm btn-outline-danger remove-ret-row">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr class="return-row">
                                        <td>
                                            <select name="items[0][item_id]" class="form-select form-select-sm ret-item-select" required>
                                                <option value="">Select Item</option>
                                                @foreach($items as $it)
                                                    <option value="{{ $it->id }}" data-rate="{{ $it->min_price ?? 0 }}" data-gst="{{ $it->gst_percentage }}">
                                                        {{ $it->name }} ({{ $it->code }})
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <input type="text" name="items[0][batch_number]" class="form-control form-control-sm" value="DEFAULT">
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0.01" name="items[0][return_quantity]" class="form-control form-control-sm ret-qty" value="1" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" name="items[0][rate]" class="form-control form-control-sm ret-rate" value="0.00" required>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" max="100" name="items[0][tax_percentage]" class="form-control form-control-sm ret-gst" value="18">
                                        </td>
                                        <td class="text-end fw-semibold ret-line-total">₹ 0.00</td>
                                        <td class="text-center">
                                            <button type="button" class="btn btn-sm btn-outline-danger remove-ret-row" disabled>
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
                                <i class="bi bi-dash-circle me-1"></i> Stock will be deducted from godown and vendor balance reduced.
                            </span>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="small text-muted">Subtotal: <span class="fw-semibold text-dark" id="displaySubtotal">₹ 0.00</span> | Tax: <span class="fw-semibold text-dark" id="displayTax">₹ 0.00</span></div>
                            <div class="fs-5 text-muted mt-1">Debit Note Amount: <span class="fw-bold text-danger fs-4" id="retGrandTotal">₹ 0.00</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-danger px-4 py-2">
                    <i class="bi bi-check2-circle me-1"></i> Issue Debit Note & Deduct Outward Stock
                </button>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = {{ $purchaseEntry && $purchaseEntry->items->count() ? $purchaseEntry->items->count() : 1 }};
    const tableBody = document.querySelector('#returnItemsTable tbody');
    const addRowBtn = document.getElementById('addReturnItemBtn');
    const grandTotalSpan = document.getElementById('retGrandTotal');
    const displaySubtotal = document.getElementById('displaySubtotal');
    const displayTax = document.getElementById('displayTax');

    function calculateTotal() {
        let subtotal = 0;
        let taxTotal = 0;

        document.querySelectorAll('.return-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.ret-qty').value) || 0;
            const rate = parseFloat(row.querySelector('.ret-rate').value) || 0;
            const gst = parseFloat(row.querySelector('.ret-gst').value) || 0;

            const lineGross = qty * rate;
            const lineTax = lineGross * (gst / 100);
            const lineTotal = lineGross + lineTax;

            row.querySelector('.ret-line-total').innerText = '₹ ' + lineTotal.toFixed(2);

            subtotal += lineGross;
            taxTotal += lineTax;
        });

        const grandTotal = subtotal + taxTotal;
        displaySubtotal.innerText = '₹ ' + subtotal.toFixed(2);
        displayTax.innerText = '₹ ' + taxTotal.toFixed(2);
        grandTotalSpan.innerText = '₹ ' + grandTotal.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    tableBody.addEventListener('change', function(e) {
        if (e.target.classList.contains('ret-item-select')) {
            const selectedOpt = e.target.options[e.target.selectedIndex];
            const rate = selectedOpt.dataset.rate || 0;
            const gst = selectedOpt.dataset.gst || 18;
            const row = e.target.closest('.return-row');
            row.querySelector('.ret-rate').value = rate;
            row.querySelector('.ret-gst').value = gst;
        }
        calculateTotal();
    });

    tableBody.addEventListener('input', calculateTotal);

    addRowBtn.addEventListener('click', function() {
        const firstRow = document.querySelector('.return-row');
        const newRow = firstRow.cloneNode(true);
        newRow.querySelectorAll('input, select').forEach(input => {
            const name = input.getAttribute('name');
            if (name) {
                input.setAttribute('name', name.replace(/\[\d+\]/, '[' + rowIndex + ']'));
            }
            if (input.classList.contains('ret-qty')) input.value = 1;
            if (input.classList.contains('ret-rate')) input.value = '0.00';
            if (input.classList.contains('ret-item-select')) input.selectedIndex = 0;
        });
        newRow.querySelector('.remove-ret-row').removeAttribute('disabled');
        tableBody.appendChild(newRow);
        rowIndex++;
        calculateTotal();
    });

    tableBody.addEventListener('click', function(e) {
        if (e.target.closest('.remove-ret-row') && document.querySelectorAll('.return-row').length > 1) {
            e.target.closest('.return-row').remove();
            calculateTotal();
        }
    });

    calculateTotal();
});
</script>
@endsection
