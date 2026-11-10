@extends('layouts.app')

@section('title', 'Record Purchase Invoice Entry')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">New Purchase Bill Entry</h4>
        <p class="text-muted small mb-0">Record vendor bill: updates inventory stock batches and creates double-entry journal</p>
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
            <div class="card shadow-sm border-0 h-100">
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
                        <select name="payment_type" class="form-select" required>
                            <option value="Credit" selected>Credit (Bill Payable)</option>
                            <option value="Cash">Cash Immediate</option>
                            <option value="Bank">Bank Transfer</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Remarks / Reference</label>
                        <textarea name="remarks" class="form-control" rows="2" placeholder="Notes, GRN reference, or transporter details">{{ old('remarks') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-box-seam me-1"></i> Billed Items & Stock Batches</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addBillItemBtn">
                        <i class="bi bi-plus-lg me-1"></i> Add Line
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0" id="billItemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 30%;">Item</th>
                                    <th style="width: 15%;">Batch #</th>
                                    <th style="width: 15%;">Quantity</th>
                                    <th style="width: 15%;">Rate (₹)</th>
                                    <th style="width: 15%;">GST %</th>
                                    <th class="text-center" style="width: 10%;">Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="bill-row">
                                    <td>
                                        <select name="items[0][item_id]" class="form-select bill-item-select" required>
                                            <option value="">Select Item</option>
                                            @foreach($items as $it)
                                                <option value="{{ $it->id }}" data-rate="{{ $it->min_price ?? 0 }}" data-gst="{{ $it->gst_percentage }}">
                                                    {{ $it->name }} ({{ $it->code }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="items[0][batch_number]" class="form-control" value="BATCH-{{ date('Ymd') }}">
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0.01" name="items[0][quantity]" class="form-control bill-qty" value="1" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="items[0][rate]" class="form-control bill-rate" value="0.00" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" max="100" name="items[0][tax_percentage]" class="form-control bill-gst" value="18">
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
                        <div class="col-md-6">
                            <div class="badge bg-success bg-opacity-25 text-success p-2">
                                <i class="bi bi-check-circle me-1"></i> Stock will be added to the selected Godown automatically.
                            </div>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="fs-6 text-muted">Grand Total Payable: <span class="fw-bold text-primary fs-5" id="billGrandTotal">₹ 0.00</span></div>
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

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.bill-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.bill-qty').value) || 0;
            const rate = parseFloat(row.querySelector('.bill-rate').value) || 0;
            const gst = parseFloat(row.querySelector('.bill-gst').value) || 0;
            const sub = qty * rate;
            const tax = sub * (gst / 100);
            total += (sub + tax);
        });
        grandTotalSpan.innerText = '₹ ' + total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    tableBody.addEventListener('change', function(e) {
        if (e.target.classList.contains('bill-item-select')) {
            const selectedOpt = e.target.options[e.target.selectedIndex];
            const rate = selectedOpt.dataset.rate || 0;
            const gst = selectedOpt.dataset.gst || 18;
            const row = e.target.closest('.bill-row');
            row.querySelector('.bill-rate').value = rate;
            row.querySelector('.bill-gst').value = gst;
        }
        calculateTotal();
    });

    tableBody.addEventListener('input', calculateTotal);

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
            if (input.classList.contains('bill-item-select')) input.selectedIndex = 0;
        });
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

