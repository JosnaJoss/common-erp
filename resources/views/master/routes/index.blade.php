@extends('layouts.app')

@section('title', 'Territories & Delivery Routes')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Territory & Delivery Routes</h4>
        <p class="text-muted small mb-0">Multi-city and multi-location route assignments for sales distribution and driver logistics.</p>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newRouteModal">
        <i class="bi bi-plus-lg me-1"></i> Add Delivery Route
    </button>
</div>

<div class="card card-custom">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Route Name</th>
                        <th>Assigned Vehicle</th>
                        <th>Assigned Driver</th>
                        <th>Coverage (Cities & Locations)</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($routes as $route)
                    <tr>
                        <td class="ps-4 fw-bold text-dark">{{ $route->route_name }}</td>
                        <td>{{ $route->vehicle ? $route->vehicle->vehicle_name . ' (' . $route->vehicle->registration_number . ')' : 'No Vehicle Assigned' }}</td>
                        <td>{{ $route->assigned_driver ?? 'Unassigned' }}</td>
                        <td>
                            <div class="d-flex flex-wrap gap-1">
                                @foreach($route->cities as $c)
                                <span class="badge bg-light text-dark border">{{ $c->name }}</span>
                                @endforeach
                                @foreach($route->locations as $loc)
                                <span class="badge bg-primary-subtle text-primary">{{ $loc->name }}</span>
                                @endforeach
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-{{ $route->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($route->status) }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Route Modal -->
<div class="modal fade" id="newRouteModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('master.routes.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Delivery Route</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Route Name <span class="text-danger">*</span></label>
                    <input type="text" name="route_name" class="form-control" required placeholder="e.g. Pune Highway Direct Route">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Assigned Vehicle</label>
                        <select name="vehicle_id" class="form-select">
                            <option value="">Select Vehicle</option>
                            @foreach($vehicles as $v)
                            <option value="{{ $v->id }}">{{ $v->vehicle_name }} ({{ $v->registration_number }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Driver Name</label>
                        <input type="text" name="assigned_driver" class="form-control" placeholder="Driver name">
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Assigned Cities (Hold Ctrl/Cmd to select multiple)</label>
                    <select name="cities[]" class="form-select" multiple size="3">
                        @foreach($cities as $ct)
                        <option value="{{ $ct->id }}">{{ $ct->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Assigned Locations</label>
                    <select name="locations[]" class="form-select" multiple size="3">
                        @foreach($locations as $loc)
                        <option value="{{ $loc->id }}">{{ $loc->name }} (PIN: {{ $loc->pincode }})</option>
                        @endforeach
                    </select>
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
                <button type="submit" class="btn btn-primary">Save Route</button>
            </div>
        </form>
    </div>
</div>
@endsection

