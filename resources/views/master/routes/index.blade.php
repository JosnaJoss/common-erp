@extends('layouts.app')

@section('title', 'Territories & Delivery Routes')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1">Territory & Delivery Routes</h4>
        <p class="text-muted small mb-0">Manage organizational hierarchy: <strong>Branch &rarr; Cities &rarr; Locations &rarr; Delivery Routes</strong></p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newCityModal">
            <i class="bi bi-building-add me-1"></i> Add Cities
        </button>
        <button class="btn btn-outline-secondary btn-sm" data-bs-toggle="modal" data-bs-target="#newLocationModal">
            <i class="bi bi-geo-alt-fill me-1"></i> Add Locations
        </button>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newRouteModal">
            <i class="bi bi-plus-lg me-1"></i> Add Delivery Route
        </button>
    </div>
</div>

<!-- Hierarchy Overview Cards -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-md-3">
        <div class="card card-custom p-3 border-start border-primary border-4">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-primary-subtle p-3 me-3 text-primary">
                    <i class="bi bi-buildings fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Branches</div>
                    <h5 class="fw-bold mb-0 text-dark">{{ $metrics['total_branches'] }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card card-custom p-3 border-start border-info border-4">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-info-subtle p-3 me-3 text-info">
                    <i class="bi bi-building fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Cities</div>
                    <h5 class="fw-bold mb-0 text-dark">{{ $metrics['total_cities'] }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card card-custom p-3 border-start border-warning border-4">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-warning-subtle p-3 me-3 text-warning">
                    <i class="bi bi-geo-alt fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Locations</div>
                    <h5 class="fw-bold mb-0 text-dark">{{ $metrics['total_locations'] }}</h5>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-md-3">
        <div class="card card-custom p-3 border-start border-success border-4">
            <div class="d-flex align-items-center">
                <div class="rounded-circle bg-success-subtle p-3 me-3 text-success">
                    <i class="bi bi-signpost-split fs-4"></i>
                </div>
                <div>
                    <div class="text-muted small fw-semibold">Routes</div>
                    <h5 class="fw-bold mb-0 text-dark">{{ $metrics['total_routes'] }}</h5>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-pills mb-4 bg-white p-2 rounded border shadow-sm" id="territoryTabs" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold px-4 {{ $activeTab === 'routes' ? 'active' : '' }}" id="routes-tab" data-bs-toggle="tab" data-bs-target="#routesTab" type="button" role="tab">
            <i class="bi bi-signpost-split me-1"></i> Delivery Routes
            <span class="badge {{ $activeTab === 'routes' ? 'bg-white text-primary' : 'bg-secondary' }} ms-1">{{ $routes->total() }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold px-4 {{ $activeTab === 'cities' ? 'active' : '' }}" id="cities-tab" data-bs-toggle="tab" data-bs-target="#citiesTab" type="button" role="tab">
            <i class="bi bi-building me-1"></i> City Management
            <span class="badge {{ $activeTab === 'cities' ? 'bg-white text-primary' : 'bg-secondary' }} ms-1">{{ $cities->total() }}</span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-semibold px-4 {{ $activeTab === 'locations' ? 'active' : '' }}" id="locations-tab" data-bs-toggle="tab" data-bs-target="#locationsTab" type="button" role="tab">
            <i class="bi bi-geo-alt me-1"></i> Location Management
            <span class="badge {{ $activeTab === 'locations' ? 'bg-white text-primary' : 'bg-secondary' }} ms-1">{{ $locations->total() }}</span>
        </button>
    </li>
</ul>

<!-- Tabs Content -->
<div class="tab-content" id="territoryTabsContent">
    <!-- TAB 1: DELIVERY ROUTES -->
    <div class="tab-pane fade {{ $activeTab === 'routes' ? 'show active' : '' }}" id="routesTab" role="tabpanel">
        <div class="card card-custom">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-signpost-split text-primary me-2"></i> Active Delivery Routes</h6>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newRouteModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Route
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Route Name</th>
                                <th>Branch</th>
                                <th>Assigned Vehicle</th>
                                <th>Assigned Driver</th>
                                <th>Coverage (Cities & Locations)</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($routes as $route)
                            <tr>
                                <td class="ps-4 fw-bold text-dark">{{ $route->route_name }}</td>
                                <td>
                                    <span class="badge bg-light text-primary border">
                                        <i class="bi bi-buildings me-1"></i>{{ $route->branch->name ?? 'All Branches' }}
                                    </span>
                                </td>
                                <td>{{ $route->vehicle ? $route->vehicle->vehicle_name . ' (' . $route->vehicle->registration_number . ')' : 'No Vehicle Assigned' }}</td>
                                <td>{{ $route->assigned_driver ?? 'Unassigned' }}</td>
                                <td>
                                    <div class="d-flex flex-wrap gap-1">
                                        @foreach($route->cities as $c)
                                        <span class="badge bg-light text-dark border">
                                            <i class="bi bi-building text-info me-1"></i>{{ $c->name }}
                                        </span>
                                        @endforeach
                                        @foreach($route->locations as $loc)
                                        <span class="badge bg-primary-subtle text-primary">
                                            <i class="bi bi-geo-alt me-1"></i>{{ $loc->name }}
                                        </span>
                                        @endforeach
                                        @if($route->cities->isEmpty() && $route->locations->isEmpty())
                                        <span class="text-muted small">No coverage assigned</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $route->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($route->status) }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-light border text-primary" data-bs-toggle="modal" data-bs-target="#editRouteModal{{ $route->id }}" title="Edit Route">
                                            <i class="bi bi-pencil me-1"></i> Edit
                                        </button>
                                        <form action="{{ route('master.routes.destroy', $route) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete route \'{{ $route->route_name }}\'?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light border text-danger" title="Delete Route">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No delivery routes created yet. Click "Add Route" above to create one.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($routes->hasPages())
                <div class="p-3 border-top">
                    {{ $routes->appends(['tab' => 'routes'])->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- TAB 2: CITY MANAGEMENT -->
    <div class="tab-pane fade {{ $activeTab === 'cities' ? 'show active' : '' }}" id="citiesTab" role="tabpanel">
        <div class="card card-custom">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-building text-info me-2"></i> Cities by Branch</h6>
                    <small class="text-muted">Manage cities grouped under their respective company branches.</small>
                </div>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newCityModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Cities
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">City Name</th>
                                <th>Associated Branch</th>
                                <th>Total Locations</th>
                                <th>Active Routes</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cities as $city)
                            <tr>
                                <td class="ps-4 fw-bold text-dark">
                                    <i class="bi bi-building text-info me-1"></i> {{ $city->name }}
                                </td>
                                <td>
                                    <span class="badge bg-light text-primary border">
                                        <i class="bi bi-buildings me-1"></i> {{ $city->branch->name ?? 'Unassigned Branch' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-secondary-subtle text-secondary">{{ $city->locations->count() }} Location(s)</span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $city->routes->count() }} Route(s)</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $city->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($city->status) }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-light border text-primary" data-bs-toggle="modal" data-bs-target="#editCityModal{{ $city->id }}" title="Edit City">
                                            <i class="bi bi-pencil me-1"></i> Edit
                                        </button>
                                        <form action="{{ route('master.cities.destroy', $city) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete city \'{{ $city->name }}\'?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light border text-danger" title="Delete City">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No cities defined yet. Click "Add City" above to create one.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($cities->hasPages())
                <div class="p-3 border-top">
                    {{ $cities->appends(['tab' => 'cities'])->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- TAB 3: LOCATION MANAGEMENT -->
    <div class="tab-pane fade {{ $activeTab === 'locations' ? 'show active' : '' }}" id="locationsTab" role="tabpanel">
        <div class="card card-custom">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-geo-alt text-warning me-2"></i> Locations & Delivery Points</h6>
                    <small class="text-muted">Manage specific areas and PIN codes classified under cities.</small>
                </div>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newLocationModal">
                    <i class="bi bi-plus-lg me-1"></i> Add Locations
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Location Name</th>
                                <th>City</th>
                                <th>Associated Branch</th>
                                <th>PIN Code</th>
                                <th>Routes Assigned</th>
                                <th>Status</th>
                                <th class="text-end pe-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($locations as $loc)
                            <tr>
                                <td class="ps-4 fw-bold text-dark">
                                    <i class="bi bi-geo-alt text-warning me-1"></i> {{ $loc->name }}
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-building me-1"></i> {{ $loc->city->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-light text-primary border">
                                        <i class="bi bi-buildings me-1"></i> {{ $loc->city?->branch?->name ?? 'N/A' }}
                                    </span>
                                </td>
                                <td>
                                    <code>{{ $loc->pincode ?? '--' }}</code>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">{{ $loc->routes->count() }} Route(s)</span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $loc->status === 'active' ? 'success' : 'secondary' }}">
                                        {{ ucfirst($loc->status) }}
                                    </span>
                                </td>
                                <td class="text-end pe-4">
                                    <div class="d-inline-flex gap-1">
                                        <button type="button" class="btn btn-sm btn-light border text-primary" data-bs-toggle="modal" data-bs-target="#editLocationModal{{ $loc->id }}" title="Edit Location">
                                            <i class="bi bi-pencil me-1"></i> Edit
                                        </button>
                                        <form action="{{ route('master.locations.destroy', $loc) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete location \'{{ $loc->name }}\'?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-light border text-danger" title="Delete Location">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">No locations defined yet. Click "Add Location" above to create one.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($locations->hasPages())
                <div class="p-3 border-top">
                    {{ $locations->appends(['tab' => 'locations'])->links() }}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- ==================== MODALS ==================== -->

<!-- 1. ADD CITY MODAL (BULK & SINGLE) -->
<div class="modal fade" id="newCityModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('master.cities.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold mb-0"><i class="bi bi-building-add text-primary me-2"></i>Add Cities</h5>
                    <small class="text-muted">Add one or multiple cities under a branch in a single submission</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Select Branch <span class="text-danger">*</span></label>
                    <select name="branch_id" class="form-select" required>
                        <option value="">Select Branch</option>
                        @foreach($branches as $b)
                        <option value="{{ $b->id }}">{{ $b->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text small">All cities listed below will be associated with this branch.</div>
                </div>

                <div class="border rounded p-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold small text-dark"><i class="bi bi-list-ul me-1"></i> City Names to Add</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addCityRow()">
                            <i class="bi bi-plus-lg me-1"></i>Add More (+)
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm bg-white align-middle mb-0" id="cityRowsTable">
                            <thead class="table-light text-muted small">
                                <tr>
                                    <th style="width: 45px;" class="text-center">#</th>
                                    <th>City Name <span class="text-danger">*</span></th>
                                    <th style="width: 140px;">Status</th>
                                    <th style="width: 50px;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="cityRowsContainer">
                                <tr class="city-input-row">
                                    <td class="text-center fw-semibold text-muted row-index">1</td>
                                    <td>
                                        <input type="text" name="cities[0][name]" class="form-control form-control-sm" required placeholder="e.g. Ernakulam">
                                    </td>
                                    <td>
                                        <select name="cities[0][status]" class="form-select form-select-sm">
                                            <option value="active" selected>Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-city" onclick="removeCityRow(this)" title="Remove Row" disabled>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2 text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addCityRow()">
                            <i class="bi bi-plus-circle me-1"></i>Add More (+)
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Cities</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. ADD LOCATION MODAL (BULK & SINGLE) -->
<div class="modal fade" id="newLocationModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('master.locations.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold mb-0"><i class="bi bi-geo-alt text-primary me-2"></i>Add Locations</h5>
                    <small class="text-muted">Add one or multiple locations and pincodes under a city</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-5">
                        <label class="form-label small fw-semibold">Filter by Branch (Optional)</label>
                        <select id="newLocBranchFilter" class="form-select form-select-sm" onchange="filterCitiesForNewLocation(this.value)">
                            <option value="">-- All Branches --</option>
                            @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label small fw-semibold">Select City <span class="text-danger">*</span></label>
                        <select name="city_id" id="newLocCitySelect" class="form-select form-select-sm" required onchange="showCityBranchInfo(this)">
                            <option value="">Select City</option>
                            @foreach($allCities as $ct)
                            <option value="{{ $ct->id }}" data-branch-id="{{ $ct->branch_id }}" data-branch-name="{{ $ct->branch->name ?? 'Unassigned' }}">
                                {{ $ct->name }} &middot; [{{ $ct->branch->name ?? 'No Branch' }}]
                            </option>
                            @endforeach
                        </select>
                        <div id="cityBranchBadge" class="form-text text-primary small mt-1"></div>
                    </div>
                </div>

                <div class="border rounded p-3 bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="fw-semibold small text-dark"><i class="bi bi-geo-fill me-1"></i> Locations to Add</span>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addLocationRow()">
                            <i class="bi bi-plus-lg me-1"></i>Add More (+)
                        </button>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-sm bg-white align-middle mb-0" id="locationRowsTable">
                            <thead class="table-light text-muted small">
                                <tr>
                                    <th style="width: 45px;" class="text-center">#</th>
                                    <th>Location / Area Name <span class="text-danger">*</span></th>
                                    <th style="width: 140px;">PIN Code</th>
                                    <th style="width: 130px;">Status</th>
                                    <th style="width: 50px;" class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody id="locationRowsContainer">
                                <tr class="location-input-row">
                                    <td class="text-center fw-semibold text-muted row-index">1</td>
                                    <td>
                                        <input type="text" name="locations[0][name]" class="form-control form-control-sm" required placeholder="e.g. Kakkanad">
                                    </td>
                                    <td>
                                        <input type="text" name="locations[0][pincode]" class="form-control form-control-sm" placeholder="e.g. 682030">
                                    </td>
                                    <td>
                                        <select name="locations[0][status]" class="form-select form-select-sm">
                                            <option value="active" selected>Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-remove-location" onclick="removeLocationRow(this)" title="Remove Row" disabled>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2 text-end">
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="addLocationRow()">
                            <i class="bi bi-plus-circle me-1"></i>Add More (+)
                        </button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Locations</button>
            </div>
        </form>
    </div>
</div>

<!-- 3. ADD ROUTE MODAL -->
<div class="modal fade" id="newRouteModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('master.routes.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-signpost-split text-primary me-2"></i>Add Delivery Route</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" id="newRouteBranchSelect" class="form-select" required onchange="filterTerritoryForRoute('newRoute', this.value)">
                            <option value="">Select Branch</option>
                            @foreach($branches as $b)
                            <option value="{{ $b->id }}">{{ $b->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text small">Route cities and locations will be restricted to this branch.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Route Name <span class="text-danger">*</span></label>
                        <input type="text" name="route_name" class="form-control" required placeholder="e.g. Kochi Metro Route">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Assigned Vehicle</label>
                        <select name="vehicle_id" class="form-select">
                            <option value="">Select Vehicle (Optional)</option>
                            @foreach($vehicles as $v)
                            <option value="{{ $v->id }}">{{ $v->vehicle_name }} ({{ $v->registration_number }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Driver Name</label>
                        <input type="text" name="assigned_driver" class="form-control" placeholder="e.g. Suresh Kumar">
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">
                            Assign Cities (Branch-Filtered)
                            <small class="text-muted d-block">Select multiple cities belonging to this branch</small>
                        </label>
                        <select name="cities[]" id="newRouteCitiesSelect" class="form-select" multiple size="5">
                            @foreach($allCities as $ct)
                            <option value="{{ $ct->id }}" data-branch-id="{{ $ct->branch_id }}">
                                {{ $ct->name }} ({{ $ct->branch->name ?? 'No Branch' }})
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text small text-muted">Hold Ctrl / Cmd to select multiple cities.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">
                            Assign Locations (Branch-Filtered)
                            <small class="text-muted d-block">Select specific delivery points under this branch</small>
                        </label>
                        <select name="locations[]" id="newRouteLocationsSelect" class="form-select" multiple size="5">
                            @foreach($allLocations as $loc)
                            <option value="{{ $loc->id }}" data-branch-id="{{ $loc->city?->branch_id }}" data-city-id="{{ $loc->city_id }}">
                                {{ $loc->name }} - {{ $loc->city->name ?? '' }} (PIN: {{ $loc->pincode ?? '--' }})
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text small text-muted">Hold Ctrl / Cmd to select multiple locations.</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select w-auto">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Route</button>
            </div>
        </form>
    </div>
</div>

<!-- ==================== EDIT MODALS ==================== -->

<!-- EDIT CITY MODALS -->
@foreach($cities as $city)
<div class="modal fade" id="editCityModal{{ $city->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('master.cities.update', $city) }}" method="POST" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Edit City: {{ $city->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Branch <span class="text-danger">*</span></label>
                    <select name="branch_id" class="form-select" required>
                        <option value="">Select Branch</option>
                        @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ old('branch_id', $city->branch_id) == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">City Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $city->name) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" {{ old('status', $city->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $city->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update City</button>
            </div>
        </form>
    </div>
</div>
@endforeach

<!-- EDIT LOCATION MODALS -->
@foreach($locations as $loc)
<div class="modal fade" id="editLocationModal{{ $loc->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('master.locations.update', $loc) }}" method="POST" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Location: {{ $loc->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Select City <span class="text-danger">*</span></label>
                    <select name="city_id" class="form-select" required>
                        <option value="">Select City</option>
                        @foreach($allCities as $ct)
                        <option value="{{ $ct->id }}" {{ old('city_id', $loc->city_id) == $ct->id ? 'selected' : '' }}>
                            {{ $ct->name }} &middot; [{{ $ct->branch->name ?? 'No Branch' }}]
                        </option>
                        @endforeach
                    </select>
                    <div class="form-text small">Associated branch is determined by the selected city.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Location / Area Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $loc->name) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">PIN Code</label>
                    <input type="text" name="pincode" class="form-control" value="{{ old('pincode', $loc->pincode) }}">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" {{ old('status', $loc->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $loc->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update Location</button>
            </div>
        </form>
    </div>
</div>
@endforeach

<!-- EDIT ROUTE MODALS -->
@foreach($routes as $route)
<div class="modal fade" id="editRouteModal{{ $route->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form action="{{ route('master.routes.update', $route) }}" method="POST" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Route: {{ $route->route_name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Branch <span class="text-danger">*</span></label>
                        <select name="branch_id" class="form-select" required onchange="filterTerritoryForRoute('editRoute{{ $route->id }}', this.value)">
                            <option value="">Select Branch</option>
                            @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ old('branch_id', $route->branch_id) == $b->id ? 'selected' : '' }}>
                                {{ $b->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Route Name <span class="text-danger">*</span></label>
                        <input type="text" name="route_name" class="form-control" value="{{ old('route_name', $route->route_name) }}" required>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Assigned Vehicle</label>
                        <select name="vehicle_id" class="form-select">
                            <option value="">Select Vehicle (Optional)</option>
                            @foreach($vehicles as $v)
                            <option value="{{ $v->id }}" {{ old('vehicle_id', $route->vehicle_id) == $v->id ? 'selected' : '' }}>
                                {{ $v->vehicle_name }} ({{ $v->registration_number }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Driver Name</label>
                        <input type="text" name="assigned_driver" class="form-control" value="{{ old('assigned_driver', $route->assigned_driver) }}">
                    </div>
                </div>

                @php
                    $routeCityIds = $route->cities->pluck('id')->toArray();
                    $routeLocIds = $route->locations->pluck('id')->toArray();
                @endphp

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Assign Cities (Branch-Filtered)</label>
                        <select name="cities[]" id="editRoute{{ $route->id }}CitiesSelect" class="form-select" multiple size="5">
                            @foreach($allCities as $ct)
                            <option value="{{ $ct->id }}" data-branch-id="{{ $ct->branch_id }}" {{ in_array($ct->id, $routeCityIds) ? 'selected' : '' }}>
                                {{ $ct->name }} ({{ $ct->branch->name ?? 'No Branch' }})
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text small text-muted">Hold Ctrl / Cmd to select multiple.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Assign Locations (Branch-Filtered)</label>
                        <select name="locations[]" id="editRoute{{ $route->id }}LocationsSelect" class="form-select" multiple size="5">
                            @foreach($allLocations as $loc)
                            <option value="{{ $loc->id }}" data-branch-id="{{ $loc->city?->branch_id }}" data-city-id="{{ $loc->city_id }}" {{ in_array($loc->id, $routeLocIds) ? 'selected' : '' }}>
                                {{ $loc->name }} - {{ $loc->city->name ?? '' }} (PIN: {{ $loc->pincode ?? '--' }})
                            </option>
                            @endforeach
                        </select>
                        <div class="form-text small text-muted">Hold Ctrl / Cmd to select multiple.</div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select w-auto">
                        <option value="active" {{ old('status', $route->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $route->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Update Route</button>
            </div>
        </form>
    </div>
</div>
@endforeach

<!-- Dynamic Dropdown Javascript -->
<script>
    // ==================== DYNAMIC BULK ROWS FOR CITIES ====================
    function updateCityRowIndices() {
        const container = document.getElementById('cityRowsContainer');
        if (!container) return;
        const rows = container.querySelectorAll('.city-input-row');
        rows.forEach((row, idx) => {
            const indexCell = row.querySelector('.row-index');
            if (indexCell) indexCell.textContent = idx + 1;

            const nameInput = row.querySelector('input[name*="[name]"]');
            if (nameInput) nameInput.name = `cities[${idx}][name]`;

            const statusSelect = row.querySelector('select[name*="[status]"]');
            if (statusSelect) statusSelect.name = `cities[${idx}][status]`;

            const removeBtn = row.querySelector('.btn-remove-city');
            if (removeBtn) {
                removeBtn.disabled = (rows.length === 1);
            }
        });
    }

    function addCityRow() {
        const container = document.getElementById('cityRowsContainer');
        if (!container) return;
        const newIndex = container.querySelectorAll('.city-input-row').length;
        const tr = document.createElement('tr');
        tr.className = 'city-input-row';
        tr.innerHTML = `
            <td class="text-center fw-semibold text-muted row-index">${newIndex + 1}</td>
            <td>
                <input type="text" name="cities[${newIndex}][name]" class="form-control form-control-sm" required placeholder="e.g. City Name">
            </td>
            <td>
                <select name="cities[${newIndex}][status]" class="form-select form-select-sm">
                    <option value="active" selected>Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-city" onclick="removeCityRow(this)" title="Remove Row">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        container.appendChild(tr);
        updateCityRowIndices();
        const firstInput = tr.querySelector('input');
        if (firstInput) firstInput.focus();
    }

    function removeCityRow(btn) {
        const container = document.getElementById('cityRowsContainer');
        if (!container) return;
        const rows = container.querySelectorAll('.city-input-row');
        if (rows.length <= 1) return;
        const row = btn.closest('.city-input-row');
        if (row) {
            row.remove();
            updateCityRowIndices();
        }
    }

    // ==================== DYNAMIC BULK ROWS FOR LOCATIONS ====================
    function updateLocationRowIndices() {
        const container = document.getElementById('locationRowsContainer');
        if (!container) return;
        const rows = container.querySelectorAll('.location-input-row');
        rows.forEach((row, idx) => {
            const indexCell = row.querySelector('.row-index');
            if (indexCell) indexCell.textContent = idx + 1;

            const nameInput = row.querySelector('input[name*="[name]"]');
            if (nameInput) nameInput.name = `locations[${idx}][name]`;

            const pinInput = row.querySelector('input[name*="[pincode]"]');
            if (pinInput) pinInput.name = `locations[${idx}][pincode]`;

            const statusSelect = row.querySelector('select[name*="[status]"]');
            if (statusSelect) statusSelect.name = `locations[${idx}][status]`;

            const removeBtn = row.querySelector('.btn-remove-location');
            if (removeBtn) {
                removeBtn.disabled = (rows.length === 1);
            }
        });
    }

    function addLocationRow() {
        const container = document.getElementById('locationRowsContainer');
        if (!container) return;
        const newIndex = container.querySelectorAll('.location-input-row').length;
        const tr = document.createElement('tr');
        tr.className = 'location-input-row';
        tr.innerHTML = `
            <td class="text-center fw-semibold text-muted row-index">${newIndex + 1}</td>
            <td>
                <input type="text" name="locations[${newIndex}][name]" class="form-control form-control-sm" required placeholder="e.g. Location / Area">
            </td>
            <td>
                <input type="text" name="locations[${newIndex}][pincode]" class="form-control form-control-sm" placeholder="e.g. 682030">
            </td>
            <td>
                <select name="locations[${newIndex}][status]" class="form-select form-select-sm">
                    <option value="active" selected>Active</option>
                    <option value="inactive">Inactive</option>
                </select>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-location" onclick="removeLocationRow(this)" title="Remove Row">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        container.appendChild(tr);
        updateLocationRowIndices();
        const firstInput = tr.querySelector('input');
        if (firstInput) firstInput.focus();
    }

    function removeLocationRow(btn) {
        const container = document.getElementById('locationRowsContainer');
        if (!container) return;
        const rows = container.querySelectorAll('.location-input-row');
        if (rows.length <= 1) return;
        const row = btn.closest('.location-input-row');
        if (row) {
            row.remove();
            updateLocationRowIndices();
        }
    }

    // Filter cities in New Location Modal based on Branch
    function filterCitiesForNewLocation(branchId) {
        const citySelect = document.getElementById('newLocCitySelect');
        const options = citySelect.querySelectorAll('option');

        options.forEach(opt => {
            if (!opt.value) return; // Keep placeholder
            const optBranch = opt.getAttribute('data-branch-id');
            if (!branchId || optBranch === branchId) {
                opt.style.display = '';
            } else {
                opt.style.display = 'none';
            }
        });

        // Reset selection if current selected option is hidden
        const selected = citySelect.selectedOptions[0];
        if (selected && selected.style.display === 'none') {
            citySelect.value = '';
        }
        showCityBranchInfo(citySelect);
    }

    // Display associated branch info when city selected
    function showCityBranchInfo(selectEl) {
        const infoBadge = document.getElementById('cityBranchBadge');
        const selected = selectEl.selectedOptions[0];
        if (selected && selected.value) {
            const branchName = selected.getAttribute('data-branch-name');
            infoBadge.innerHTML = '<i class="bi bi-info-circle me-1"></i>Branch: <strong>' + (branchName || 'Unassigned') + '</strong>';
        } else {
            infoBadge.innerHTML = '';
        }
    }

    // Dependent filtering for Routes (Branch -> Cities & Locations)
    function filterTerritoryForRoute(prefix, branchId) {
        const citiesSelect = document.getElementById(prefix + 'CitiesSelect');
        const locsSelect = document.getElementById(prefix + 'LocationsSelect');

        if (citiesSelect) {
            Array.from(citiesSelect.options).forEach(opt => {
                const optBranch = opt.getAttribute('data-branch-id');
                if (!branchId || optBranch === branchId) {
                    opt.style.display = '';
                    opt.disabled = false;
                } else {
                    opt.style.display = 'none';
                    opt.disabled = true;
                    opt.selected = false;
                }
            });
        }

        if (locsSelect) {
            Array.from(locsSelect.options).forEach(opt => {
                const optBranch = opt.getAttribute('data-branch-id');
                if (!branchId || optBranch === branchId) {
                    opt.style.display = '';
                    opt.disabled = false;
                } else {
                    opt.style.display = 'none';
                    opt.disabled = true;
                    opt.selected = false;
                }
            });
        }
    }

    // Apply filtering initially on modals when opened
    document.addEventListener('DOMContentLoaded', function() {
        const newBranchSelect = document.getElementById('newRouteBranchSelect');
        if (newBranchSelect && newBranchSelect.value) {
            filterTerritoryForRoute('newRoute', newBranchSelect.value);
        }

        @foreach($routes as $route)
        filterTerritoryForRoute('editRoute{{ $route->id }}', '{{ $route->branch_id }}');
        @endforeach
    });
</script>
@endsection
