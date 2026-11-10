@extends('layouts.app')

@section('title', 'Create Purchase Order')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Create Purchase Order</h4>
        <p class="text-muted small mb-0">Generate a new supplier contract order with pricing and delivery terms</p>
    </div>
    <a href="{{ route('purchase.orders.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Orders
    </a>
</div>

<form action="{{ route('purchase.orders.store') }}" method="POST" id="poForm">
    @csrf

    <div class="row g-4">
        <!-- PO Header Info -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-info-circle me-1"></i> Order Details</h6>
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
                        <label class="form-label fw-bold">Link Purchase Request</label>
                        <select name="purchase_request_id" class="form-select">
                            <option value="">None (Direct PO)</option>
                            @foreach($requests as $r)
                                <option value="{{ $r->id }}">{{ $r->request_no }} ({{ $r->department->name ?? 'Dept' }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">PO Date <span class="text-danger">*</span></label>
                        <input type="date" name="po_date" class="form-control" value="{{ old('po_date', date('Y-m-d')) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Expected Delivery Date</label>
                        <input type="date" name="expected_delivery_date" class="form-control" value="{{ old('expected_delivery_date', now()->addDays(7)->toDateString()) }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Terms & Conditions</label>
                        <textarea name="terms_and_conditions" class="form-control" rows="3" placeholder="Payment within 30 days, delivery at plant">{{ old('terms_and_conditions', 'Payment within 30 days of GRN inspection.') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-cart-check me-1"></i> Order Line Items</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addRowBtn">
                        <i class="bi bi-plus-lg me-1"></i> Add Item Line
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0" id="itemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40%;">Item</th>
                                    <th style="width: 15%;">Quantity</th>
                                    <th style="width: 20%;">Purchase Rate (₹)</th>
                                    <th style="width: 15%;">GST %</th>
                                    <th class="text-center" style="width: 10%;">Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="item-row">
                                    <td>
                                        <select name="items[0][item_id]" class="form-select item-select" required>
                                            <option value="">Select Item</option>
                                            @foreach($items as $it)
                                                <option value="{{ $it->id }}" data-rate="{{ $it->min_price ?? 0 }}" data-gst="{{ $it->gst_percentage }}">
                                                    {{ $it->name }} ({{ $it->code }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0.01" name="items[0][quantity]" class="form-control item-qty" value="1" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="items[0][rate]" class="form-control item-rate" value="0.00" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" max="100" name="items[0][tax_percentage]" class="form-control item-gst" value="18">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-row" disabled>
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
                            <small class="text-muted">GST input credit will automatically reflect in double-entry accounts upon bill receipt.</small>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="fs-6 text-muted">Estimated Grand Total: <span class="fw-bold text-primary fs-5" id="liveGrandTotal">₹ 0.00</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary px-4 py-2">
                    <i class="bi bi-check2-circle me-1"></i> Save & Confirm Purchase Order
                </button>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = 1;
    const tableBody = document.querySelector('#itemsTable tbody');
    const addRowBtn = document.getElementById('addRowBtn');
    const liveGrandTotal = document.getElementById('liveGrandTotal');

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.item-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.item-qty').value) || 0;
            const rate = parseFloat(row.querySelector('.item-rate').value) || 0;
            const gst = parseFloat(row.querySelector('.item-gst').value) || 0;
            const sub = qty * rate;
            const tax = sub * (gst / 100);
            total += (sub + tax);
        });
        liveGrandTotal.innerText = '₹ ' + total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    tableBody.addEventListener('change', function(e) {
        if (e.target.classList.contains('item-select')) {
            const selectedOpt = e.target.options[e.target.selectedIndex];
            const rate = selectedOpt.dataset.rate || 0;
            const gst = selectedOpt.dataset.gst || 18;
            const row = e.target.closest('.item-row');
            row.querySelector('.item-rate').value = rate;
            row.querySelector('.item-gst').value = gst;
        }
        calculateTotal();
    });

    tableBody.addEventListener('input', calculateTotal);

    addRowBtn.addEventListener('click', function() {
        const firstRow = document.querySelector('.item-row');
        const newRow = firstRow.cloneNode(true);
        newRow.querySelectorAll('input, select').forEach(input => {
            const name = input.getAttribute('name');
            if (name) {
                input.setAttribute('name', name.replace(/\[\d+\]/, '[' + rowIndex + ']'));
            }
            if (input.classList.contains('item-qty')) input.value = 1;
            if (input.classList.contains('item-rate')) input.value = '0.00';
            if (input.classList.contains('item-select')) input.selectedIndex = 0;
        });
        newRow.querySelector('.remove-row').removeAttribute('disabled');
        tableBody.appendChild(newRow);
        rowIndex++;
        calculateTotal();
    });

    tableBody.addEventListener('click', function(e) {
        if (e.target.closest('.remove-row') && document.querySelectorAll('.item-row').length > 1) {
            e.target.closest('.item-row').remove();
            calculateTotal();
        }
    });

    calculateTotal();
});
</script>
@endsection

