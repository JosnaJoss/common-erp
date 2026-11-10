@extends('layouts.app')

@section('title', 'Create Sales Order')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Create Sales Order</h4>
        <p class="text-muted small mb-0">Record confirmed customer booking with 3-tier pricing and GST calculation</p>
    </div>
    <a href="{{ route('sales.orders.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Orders
    </a>
</div>

<form action="{{ route('sales.orders.store') }}" method="POST" id="soForm">
    @csrf

    <div class="row g-4">
        <!-- Order Header -->
        <div class="col-lg-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-person-check me-1"></i> Customer & Order Info</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Select Customer <span class="text-danger">*</span></label>
                        <select name="customer_id" id="customerSelect" class="form-select @error('customer_id') is-invalid @enderror" required>
                            <option value="">Choose Customer</option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" data-price-level="{{ $c->pricing_level ?? 'normal' }}">
                                    {{ $c->name }} ({{ $c->city ?? 'Local' }}) - Level: {{ ucfirst($c->pricing_level ?? 'normal') }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Order Date <span class="text-danger">*</span></label>
                        <input type="date" name="order_date" class="form-control" value="{{ old('order_date', date('Y-m-d')) }}" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Expected Delivery Date</label>
                        <input type="date" name="expected_delivery_date" class="form-control" value="{{ old('expected_delivery_date', now()->addDays(3)->toDateString()) }}">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Customer Notes / PO Reference</label>
                        <textarea name="notes" class="form-control" rows="3" placeholder="Customer order notes, PO reference">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <!-- Line Items -->
        <div class="col-lg-8">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                    <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-cart me-1"></i> Ordered Items</h6>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="addSoItemBtn">
                        <i class="bi bi-plus-lg me-1"></i> Add Item Line
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered align-middle mb-0" id="soItemsTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 40%;">Item</th>
                                    <th style="width: 15%;">Quantity</th>
                                    <th style="width: 20%;">Selling Price (₹)</th>
                                    <th style="width: 15%;">GST %</th>
                                    <th class="text-center" style="width: 10%;">Remove</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="so-row">
                                    <td>
                                        <select name="items[0][item_id]" class="form-select so-item-select" required>
                                            <option value="">Select Item</option>
                                            @foreach($items as $it)
                                                <option value="{{ $it->id }}" data-min="{{ $it->min_price }}" data-normal="{{ $it->normal_price }}" data-max="{{ $it->max_price }}" data-gst="{{ $it->gst_percentage }}">
                                                    {{ $it->name }} ({{ $it->code }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0.01" name="items[0][quantity]" class="form-control so-qty" value="1" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" name="items[0][selling_price]" class="form-control so-price" value="0.00" required>
                                    </td>
                                    <td>
                                        <input type="number" step="0.01" min="0" max="100" name="items[0][tax_percentage]" class="form-control so-gst" value="18">
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-so-row" disabled>
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
                            <small class="text-muted">Prices auto-populate based on customer's assigned price tier (Min/Normal/Max).</small>
                        </div>
                        <div class="col-md-6 text-end">
                            <div class="fs-6 text-muted">Estimated Order Total: <span class="fw-bold text-success fs-5" id="soGrandTotal">₹ 0.00</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="text-end">
                <button type="submit" class="btn btn-primary px-4 py-2">
                    <i class="bi bi-check2-circle me-1"></i> Confirm & Save Sales Order
                </button>
            </div>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function() {
    let rowIndex = 1;
    const tableBody = document.querySelector('#soItemsTable tbody');
    const addRowBtn = document.getElementById('addSoItemBtn');
    const grandTotalSpan = document.getElementById('soGrandTotal');
    const customerSelect = document.getElementById('customerSelect');

    function getCustomerPriceLevel() {
        const opt = customerSelect.options[customerSelect.selectedIndex];
        return opt ? (opt.dataset.priceLevel || 'normal') : 'normal';
    }

    function calculateTotal() {
        let total = 0;
        document.querySelectorAll('.so-row').forEach(row => {
            const qty = parseFloat(row.querySelector('.so-qty').value) || 0;
            const price = parseFloat(row.querySelector('.so-price').value) || 0;
            const gst = parseFloat(row.querySelector('.so-gst').value) || 0;
            const sub = qty * price;
            const tax = sub * (gst / 100);
            total += (sub + tax);
        });
        grandTotalSpan.innerText = '₹ ' + total.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    tableBody.addEventListener('change', function(e) {
        if (e.target.classList.contains('so-item-select')) {
            const selectedOpt = e.target.options[e.target.selectedIndex];
            const level = getCustomerPriceLevel();
            let price = selectedOpt.dataset.normal || 0;
            if (level === 'minimum') price = selectedOpt.dataset.min || price;
            if (level === 'maximum') price = selectedOpt.dataset.max || price;

            const gst = selectedOpt.dataset.gst || 18;
            const row = e.target.closest('.so-row');
            row.querySelector('.so-price').value = price;
            row.querySelector('.so-gst').value = gst;
        }
        calculateTotal();
    });

    tableBody.addEventListener('input', calculateTotal);

    addRowBtn.addEventListener('click', function() {
        const firstRow = document.querySelector('.so-row');
        const newRow = firstRow.cloneNode(true);
        newRow.querySelectorAll('input, select').forEach(input => {
            const name = input.getAttribute('name');
            if (name) {
                input.setAttribute('name', name.replace(/\[\d+\]/, '[' + rowIndex + ']'));
            }
            if (input.classList.contains('so-qty')) input.value = 1;
            if (input.classList.contains('so-price')) input.value = '0.00';
            if (input.classList.contains('so-item-select')) input.selectedIndex = 0;
        });
        newRow.querySelector('.remove-so-row').removeAttribute('disabled');
        tableBody.appendChild(newRow);
        rowIndex++;
        calculateTotal();
    });

    tableBody.addEventListener('click', function(e) {
        if (e.target.closest('.remove-so-row') && document.querySelectorAll('.so-row').length > 1) {
            e.target.closest('.so-row').remove();
            calculateTotal();
        }
    });

    calculateTotal();
});
</script>
@endsection

