@extends('layouts.app')

@section('title', 'Units of Measure')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Units of Measurement (UOM)</h4>
        <p class="text-muted small mb-0">Measurement units for stock, purchase, sales, and recipe conversions.</p>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newUnitModal">
        <i class="bi bi-plus-lg me-1"></i> Add Unit
    </button>
</div>

<div class="card card-custom">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Unit Name</th>
                        <th>Symbol</th>
                        <th>Type</th>
                        <th>Conversion Factor</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($units as $unit)
                    <tr>
                        <td class="ps-4 fw-semibold">{{ $unit->name }}</td>
                        <td><span class="badge bg-light text-primary border">{{ $unit->symbol }}</span></td>
                        <td><span class="badge bg-secondary-subtle text-secondary text-capitalize">{{ $unit->unit_type }}</span></td>
                        <td>{{ $unit->conversion_factor }}</td>
                        <td>
                            <span class="badge bg-{{ $unit->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($unit->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Unit Modal -->
<div class="modal fade" id="newUnitModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('master.units.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Unit of Measurement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Unit Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Kilogram">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Symbol <span class="text-danger">*</span></label>
                        <input type="text" name="symbol" class="form-control" required placeholder="e.g. KG">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Unit Type <span class="text-danger">*</span></label>
                        <select name="unit_type" class="form-select" required>
                            <option value="quantity">Quantity (Pcs/Boxes)</option>
                            <option value="weight">Weight (Kg/Tons)</option>
                            <option value="length">Length (Meters/Feet)</option>
                            <option value="volume">Volume (Liters)</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Conversion Factor</label>
                    <input type="number" step="0.0001" name="conversion_factor" class="form-control" value="1.0000" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Unit</button>
            </div>
        </form>
    </div>
</div>
@endsection

