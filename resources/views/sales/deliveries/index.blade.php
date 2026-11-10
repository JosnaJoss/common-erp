@extends('layouts.app')

@section('title', 'Sales Deliveries & Dispatch')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Sales Deliveries & Dispatch Logistics</h4>
        <p class="text-muted small mb-0">Assign delivery routes, allocate company vehicles and drivers, and monitor dispatch status</p>
    </div>
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#newDeliveryModal">
        <i class="bi bi-truck me-1"></i> New Dispatch Assignment
    </button>
</div>

<!-- Deliveries Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Dispatch #</th>
                        <th>Delivery Date</th>
                        <th>Customer</th>
                        <th>Route</th>
                        <th>Vehicle & Driver</th>
                        <th>Delivery Destination</th>
                        <th>Status</th>
                        <th class="text-center">Update Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($deliveries as $del)
                        <tr>
                            <td><span class="badge bg-light text-primary border fw-bold">{{ $del->delivery_number }}</span></td>
                            <td>{{ \Carbon\Carbon::parse($del->delivery_date)->format('d M Y') }}</td>
                            <td>
                                <div class="fw-bold">{{ $del->customer->name ?? 'Customer' }}</div>
                                <small class="text-muted">{{ $del->customer->phone ?? '' }}</small>
                            </td>
                            <td>
                                <i class="bi bi-signpost-2 me-1 text-primary"></i>
                                {{ $del->route->route_name ?? 'Default Route' }}
                            </td>
                            <td>
                                <div><i class="bi bi-truck me-1 text-muted"></i> <strong>{{ $del->vehicle->registration_number ?? 'Vehicle' }}</strong></div>
                                <small class="text-muted">Driver: {{ $del->assigned_driver }}</small>
                            </td>
                            <td><small>{{ $del->delivery_address }}</small></td>
                            <td>
                                @if($del->delivery_status === 'delivered')
                                    <span class="badge bg-success"><i class="bi bi-check2-circle me-1"></i> Delivered</span>
                                @elseif($del->delivery_status === 'in_transit')
                                    <span class="badge bg-warning text-dark"><i class="bi bi-truck me-1"></i> In Transit</span>
                                @elseif($del->delivery_status === 'failed')
                                    <span class="badge bg-danger">Failed</span>
                                @else
                                    <span class="badge bg-secondary">Assigned</span>
                                @endif
                            </td>
                            <td class="text-center">
                                <form action="{{ route('sales.deliveries.status', $del) }}" method="POST" class="d-inline-flex gap-1">
                                    @csrf
                                    <select name="delivery_status" class="form-select form-select-sm" onchange="this.form.submit()">
                                        <option value="assigned" {{ $del->delivery_status == 'assigned' ? 'selected' : '' }}>Assigned</option>
                                        <option value="in_transit" {{ $del->delivery_status == 'in_transit' ? 'selected' : '' }}>In Transit</option>
                                        <option value="delivered" {{ $del->delivery_status == 'delivered' ? 'selected' : '' }}>Delivered</option>
                                        <option value="failed" {{ $del->delivery_status == 'failed' ? 'selected' : '' }}>Failed</option>
                                    </select>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-truck fs-2 d-block mb-2"></i> No delivery assignments recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($deliveries->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $deliveries->links() }}
        </div>
    @endif
</div>

<!-- New Delivery Modal -->
<div class="modal fade" id="newDeliveryModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form action="{{ route('sales.deliveries.store') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title fw-bold"><i class="bi bi-truck me-2 text-primary"></i> Create Dispatch Assignment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Select Customer <span class="text-danger">*</span></label>
                            <select name="customer_id" class="form-select" id="delCustomerSelect" required>
                                <option value="">Choose Customer</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}" data-address="{{ $c->address }}, {{ $c->city }}">{{ $c->name }} ({{ $c->city ?? 'Local' }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Delivery Route <span class="text-danger">*</span></label>
                            <select name="route_id" class="form-select" required>
                                <option value="">Choose Route</option>
                                @foreach($routes as $r)
                                    <option value="{{ $r->id }}">{{ $r->route_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Vehicle <span class="text-danger">*</span></label>
                            <select name="vehicle_id" id="vehicleSelect" class="form-select" required>
                                <option value="">Choose Vehicle</option>
                                @foreach($vehicles as $v)
                                    <option value="{{ $v->id }}" data-driver="{{ $v->assigned_driver }}">{{ $v->vehicle_name }} ({{ $v->registration_number }}) - Cap: {{ $v->capacity }}kg</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Assigned Driver <span class="text-danger">*</span></label>
                            <input type="text" name="assigned_driver" id="driverInput" class="form-control" placeholder="Driver name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Dispatch Date <span class="text-danger">*</span></label>
                            <input type="date" name="delivery_date" class="form-control" value="{{ date('Y-m-d') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Delivery Address <span class="text-danger">*</span></label>
                            <input type="text" name="delivery_address" id="delAddressInput" class="form-control" placeholder="Customer address" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Remarks / Gate Pass / Transporter Notes</label>
                            <textarea name="delivery_remarks" class="form-control" rows="2" placeholder="Driver contact, special delivery instructions"></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2-circle me-1"></i> Dispatch & Schedule</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const custSelect = document.getElementById('delCustomerSelect');
    const addrInput = document.getElementById('delAddressInput');
    const vehSelect = document.getElementById('vehicleSelect');
    const driverInput = document.getElementById('driverInput');

    if (custSelect && addrInput) {
        custSelect.addEventListener('change', function() {
            const opt = custSelect.options[custSelect.selectedIndex];
            if (opt && opt.dataset.address) {
                addrInput.value = opt.dataset.address;
            }
        });
    }

    if (vehSelect && driverInput) {
        vehSelect.addEventListener('change', function() {
            const opt = vehSelect.options[vehSelect.selectedIndex];
            if (opt && opt.dataset.driver) {
                driverInput.value = opt.dataset.driver;
            }
        });
    }
});
</script>
@endsection

