@extends('layouts.app')

@section('title', 'Godowns & Warehouses')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Godowns & Warehouses</h4>
        <p class="text-muted small mb-0">Storage locations, central plant depots, and regional distribution points.</p>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newGodownModal">
        <i class="bi bi-plus-lg me-1"></i> Add Godown
    </button>
</div>

<div class="card card-custom">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Godown Name & Code</th>
                        <th>Branch</th>
                        <th>Address & PIN</th>
                        <th>Responsible Staff</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($godowns as $godown)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">{{ $godown->name }}</div>
                            <span class="badge bg-light text-primary border">{{ $godown->code }}</span>
                        </td>
                        <td>{{ $godown->branch->name ?? 'Company Head Office' }}</td>
                        <td class="small text-muted">{{ $godown->address ?? 'N/A' }} {{ $godown->postal_code ? "({$godown->postal_code})" : '' }}</td>
                        <td>{{ $godown->responsible_staff ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-{{ $godown->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($godown->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Godown Modal -->
<div class="modal fade" id="newGodownModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('master.godowns.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Godown / Warehouse</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Godown Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Raw Material Godown">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Godown Code <span class="text-danger">*</span></label>
                        <input type="text" name="code" class="form-control" required placeholder="e.g. WH-RAW-01">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Branch</label>
                        <select name="branch_id" class="form-select">
                            <option value="">Select Branch</option>
                            @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-8">
                        <label class="form-label small fw-semibold">Address</label>
                        <input type="text" name="address" class="form-control">
                    </div>
                    <div class="col-4">
                        <label class="form-label small fw-semibold">Postal Code</label>
                        <input type="text" name="postal_code" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Responsible In-Charge Staff</label>
                    <input type="text" name="responsible_staff" class="form-control" placeholder="Staff Name">
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
                <button type="submit" class="btn btn-primary">Save Godown</button>
            </div>
        </form>
    </div>
</div>
@endsection

