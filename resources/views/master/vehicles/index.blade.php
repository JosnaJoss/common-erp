@extends('layouts.app')

@section('title', 'Vehicles & Fleet')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Vehicles & Transport Fleet</h4>
        <p class="text-muted small mb-0">Delivery logistics, capacity, vehicle insurance, and fitness monitoring.</p>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newVehicleModal">
        <i class="bi bi-plus-lg me-1"></i> Register Vehicle
    </button>
</div>

<div class="card card-custom">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Vehicle Details</th>
                        <th>Type & Capacity</th>
                        <th>Assigned Driver</th>
                        <th>Insurance Expiry</th>
                        <th>Fitness Expiry</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($vehicles as $vehicle)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">{{ $vehicle->vehicle_name }}</div>
                            <span class="badge bg-light text-dark border">{{ $vehicle->registration_number }}</span>
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary">{{ $vehicle->vehicle_type }}</span>
                            <div class="small text-muted">{{ $vehicle->capacity }} KG / Tons</div>
                        </td>
                        <td>{{ $vehicle->assigned_driver ?? 'Unassigned' }}</td>
                        <td class="small text-muted">{{ $vehicle->insurance_expiry_date ? $vehicle->insurance_expiry_date->format('d M Y') : 'N/A' }}</td>
                        <td class="small text-muted">{{ $vehicle->fitness_certificate_expiry_date ? $vehicle->fitness_certificate_expiry_date->format('d M Y') : 'N/A' }}</td>
                        <td>
                            <span class="badge bg-{{ $vehicle->status === 'active' ? 'success' : ($vehicle->status === 'maintenance' ? 'warning' : 'secondary') }}">
                                {{ ucfirst($vehicle->status) }}
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <button class="btn btn-sm btn-light border text-primary" data-bs-toggle="modal" data-bs-target="#editVehicleModal{{ $vehicle->id }}" title="Edit Vehicle">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form action="{{ route('master.vehicles.destroy', $vehicle) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this vehicle?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light border text-danger" title="Delete Vehicle">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>

                    <!-- Edit Vehicle Modal -->
                    <div class="modal fade" id="editVehicleModal{{ $vehicle->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <form action="{{ route('master.vehicles.update', $vehicle) }}" method="POST" class="modal-content">
                                @csrf
                                @method('PUT')
                                <div class="modal-header">
                                    <h5 class="modal-title fw-bold">Edit Vehicle: {{ $vehicle->registration_number }}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body text-start">
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Vehicle Name / Make <span class="text-danger">*</span></label>
                                        <input type="text" name="vehicle_name" class="form-control" value="{{ old('vehicle_name', $vehicle->vehicle_name) }}" required>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">Registration Number <span class="text-danger">*</span></label>
                                            <input type="text" name="registration_number" class="form-control" value="{{ old('registration_number', $vehicle->registration_number) }}" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">Vehicle Type <span class="text-danger">*</span></label>
                                            <select name="vehicle_type" class="form-select" required>
                                                <option value="Truck" {{ $vehicle->vehicle_type === 'Truck' ? 'selected' : '' }}>Heavy Truck</option>
                                                <option value="Tempo" {{ $vehicle->vehicle_type === 'Tempo' ? 'selected' : '' }}>Tempo / Mini Truck</option>
                                                <option value="Van" {{ $vehicle->vehicle_type === 'Van' ? 'selected' : '' }}>Delivery Van</option>
                                                <option value="3-Wheeler" {{ $vehicle->vehicle_type === '3-Wheeler' ? 'selected' : '' }}>3-Wheeler</option>
                                                <option value="Bike" {{ $vehicle->vehicle_type === 'Bike' ? 'selected' : '' }}>Bike / Courier</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">Capacity (KG) <span class="text-danger">*</span></label>
                                            <input type="number" step="0.01" name="capacity" class="form-control" value="{{ old('capacity', $vehicle->capacity) }}" required>
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">Branch</label>
                                            <select name="branch_id" class="form-select">
                                                <option value="">Select Branch</option>
                                                @foreach($branches as $b)
                                                <option value="{{ $b->id }}" {{ old('branch_id', $vehicle->branch_id) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Assigned Driver</label>
                                        <input type="text" name="assigned_driver" class="form-control" value="{{ old('assigned_driver', $vehicle->assigned_driver) }}">
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">Insurance Expiry Date</label>
                                            <input type="date" name="insurance_expiry_date" class="form-control" value="{{ old('insurance_expiry_date', $vehicle->insurance_expiry_date ? $vehicle->insurance_expiry_date->format('Y-m-d') : '') }}">
                                        </div>
                                        <div class="col-6">
                                            <label class="form-label small fw-semibold">Fitness Expiry Date</label>
                                            <input type="date" name="fitness_certificate_expiry_date" class="form-control" value="{{ old('fitness_certificate_expiry_date', $vehicle->fitness_certificate_expiry_date ? $vehicle->fitness_certificate_expiry_date->format('Y-m-d') : '') }}">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-semibold">Status</label>
                                        <select name="status" class="form-select">
                                            <option value="active" {{ $vehicle->status === 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="maintenance" {{ $vehicle->status === 'maintenance' ? 'selected' : '' }}>In Maintenance</option>
                                            <option value="inactive" {{ $vehicle->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                                    <button type="submit" class="btn btn-primary">Update Vehicle</button>
                                </div>
                            </form>
                        </div>
                    </div>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No vehicles registered yet.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($vehicles->hasPages())
    <div class="card-footer bg-white border-top py-3">
        {{ $vehicles->links() }}
    </div>
    @endif
</div>

<!-- Add Vehicle Modal -->
<div class="modal fade" id="newVehicleModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('master.vehicles.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Register Vehicle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Vehicle Name / Make <span class="text-danger">*</span></label>
                    <input type="text" name="vehicle_name" class="form-control" required placeholder="e.g. Tata 407 Heavy Truck">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Registration Number <span class="text-danger">*</span></label>
                        <input type="text" name="registration_number" class="form-control" required placeholder="e.g. MH-02-CB-4040">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Vehicle Type <span class="text-danger">*</span></label>
                        <select name="vehicle_type" class="form-select" required>
                            <option value="Truck">Heavy Truck</option>
                            <option value="Tempo">Tempo / Mini Truck</option>
                            <option value="Van">Delivery Van</option>
                            <option value="3-Wheeler">3-Wheeler</option>
                            <option value="Bike">Bike / Courier</option>
                        </select>
                    </div>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Capacity (KG) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="capacity" class="form-control" required placeholder="4500">
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
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Assigned Driver</label>
                    <input type="text" name="assigned_driver" class="form-control" placeholder="Driver name">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Insurance Expiry Date</label>
                        <input type="date" name="insurance_expiry_date" class="form-control">
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Fitness Expiry Date</label>
                        <input type="date" name="fitness_certificate_expiry_date" class="form-control">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="active">Active</option>
                        <option value="maintenance">In Maintenance</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Vehicle</button>
            </div>
        </form>
    </div>
</div>
@endsection
