@extends('layouts.app')

@section('title', 'New Internal Stock Request')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">New Internal Stock Request</h4>
        <p class="text-muted small mb-0">Create a material requisition from warehouse or godown for production, packaging, or department use</p>
    </div>
    <a href="{{ route('inventory.requests.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Requests
    </a>
</div>

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <ul class="mb-0">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form method="POST" action="{{ route('inventory.requests.store') }}" id="stockRequestForm">
    @csrf

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-0">
            <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-info-circle me-2"></i>Request Header Details</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Requesting Department <span class="text-danger">*</span></label>
                    <select name="from_department_id" id="from_department_id" class="form-select" required>
                        <option value="">-- Select Department --</option>
                        @foreach($departments as $d)
                            <option value="{{ $d->id }}" data-maintains-stock="{{ $d->maintains_stock ? '1' : '0' }}" data-godown-id="{{ $d->default_godown_id }}">
                                {{ $d->name }} ({{ strtoupper($d->department_type) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Fulfilling Source Godown <span class="text-danger">*</span></label>
                    <select name="source_godown_id" class="form-select" required>
                        <option value="">-- Select Source Warehouse --</option>
                        @foreach($godowns as $g)
                            <option value="{{ $g->id }}" {{ $g->location_type === 'warehouse' ? 'selected' : '' }}>
                                {{ $g->name }} ({{ strtoupper($g->location_type) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4" id="destination_godown_col">
                    <label class="form-label">Destination Stock Location</label>
                    <select name="from_godown_id" id="from_godown_id" class="form-select">
                        <option value="">-- Default Central Delivery --</option>
                        @foreach($godowns as $g)
                            <option value="{{ $g->id }}">{{ $g->name }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted" style="font-size: 0.75rem;">Required only if department maintains its own stock location.</small>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Request Date <span class="text-danger">*</span></label>
                    <input type="date" name="request_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Required By Date</label>
                    <input type="date" name="required_date" class="form-control" value="{{ date('Y-m-d', strtotime('+2 days')) }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Priority <span class="text-danger">*</span></label>
                    <select name="priority" class="form-select" required>
                        <option value="low">Low</option>
                        <option value="medium" selected>Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">Remarks / Purpose</label>
                    <input type="text" name="remarks" class="form-control" placeholder="e.g. Batch #44 Production Requisition">
                </div>
            </div>
        </div>
    </div>

    <!-- Requested Items Matrix -->
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-box-seam me-2"></i>Requested Items</h5>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addRowBtn">
                <i class="bi bi-plus-lg me-1"></i> Add Item Line
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-bordered mb-0 align-middle" id="itemsTable">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 45%;">Item / Material <span class="text-danger">*</span></th>
                            <th style="width: 25%;">Requested Quantity <span class="text-danger">*</span></th>
                            <th style="width: 25%;">Remarks</th>
                            <th style="width: 5%;" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody id="itemsTableBody">
                        <tr>
                            <td>
                                <select name="items[0][item_id]" class="form-select form-select-sm" required>
                                    <option value="">-- Select Material / Item --</option>
                                    @foreach($items as $i)
                                        <option value="{{ $i->id }}">{{ $i->name }} ({{ $i->code }}) - {{ $i->unit->name ?? 'Units' }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" step="0.001" min="0.001" name="items[0][quantity]" class="form-control form-control-sm" placeholder="0.000" required>
                            </td>
                            <td>
                                <input type="text" name="items[0][remarks]" class="form-control form-control-sm" placeholder="Optional notes">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-row-btn" disabled><i class="bi bi-trash"></i></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white py-3 text-end">
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-send me-1"></i> Submit Stock Request
            </button>
        </div>
    </div>
</form>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tableBody = document.getElementById('itemsTableBody');
    const addRowBtn = document.getElementById('addRowBtn');
    const deptSelect = document.getElementById('from_department_id');
    const godownSelect = document.getElementById('from_godown_id');
    let rowIndex = 1;

    deptSelect.addEventListener('change', function () {
        const selected = this.options[this.selectedIndex];
        const godownId = selected.getAttribute('data-godown-id');
        if (godownId) {
            godownSelect.value = godownId;
        }
    });

    addRowBtn.addEventListener('click', function () {
        const firstRow = tableBody.rows[0];
        const newRow = firstRow.cloneNode(true);

        const select = newRow.querySelector('select');
        select.name = `items[${rowIndex}][item_id]`;
        select.value = '';

        const qtyInput = newRow.querySelector('input[type="number"]');
        qtyInput.name = `items[${rowIndex}][quantity]`;
        qtyInput.value = '';

        const remarksInput = newRow.querySelector('input[type="text"]');
        remarksInput.name = `items[${rowIndex}][remarks]`;
        remarksInput.value = '';

        const removeBtn = newRow.querySelector('.remove-row-btn');
        removeBtn.disabled = false;
        removeBtn.addEventListener('click', function () {
            if (tableBody.rows.length > 1) {
                newRow.remove();
            }
        });

        tableBody.appendChild(newRow);
        rowIndex++;
    });
});
</script>
@endsection
