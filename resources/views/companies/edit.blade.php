@extends('layouts.app')

@section('title', 'Edit Company & Modules')

@section('content')
<div class="row">
    <!-- Company Profile Details -->
    <div class="col-lg-7">
        <div class="card card-custom mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i> Company Profile: {{ $company->name }}</h5>
                <a href="{{ route('companies.index') }}" class="btn btn-sm btn-outline-secondary">Back to List</a>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('companies.update', $company) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Company Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $company->name) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Business Profile <span class="text-danger">*</span></label>
                            <select name="business_type" class="form-select" required>
                                <option value="manufacturing" {{ $company->business_type === 'manufacturing' ? 'selected' : '' }}>Manufacturing</option>
                                <option value="wholesale" {{ $company->business_type === 'wholesale' ? 'selected' : '' }}>Wholesale</option>
                                <option value="distribution" {{ $company->business_type === 'distribution' ? 'selected' : '' }}>Distribution</option>
                                <option value="trading" {{ $company->business_type === 'trading' ? 'selected' : '' }}>Trading</option>
                                <option value="retail" {{ $company->business_type === 'retail' ? 'selected' : '' }}>Retail</option>
                                <option value="service" {{ $company->business_type === 'service' ? 'selected' : '' }}>Service-Based</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">GST Number</label>
                            <input type="text" name="gst_no" class="form-control" value="{{ old('gst_no', $company->gst_no) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">PAN Number</label>
                            <input type="text" name="pan_no" class="form-control" value="{{ old('pan_no', $company->pan_no) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Registration / CIN</label>
                            <input type="text" name="registration_no" class="form-control" value="{{ old('registration_no', $company->registration_no) }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Phone</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $company->phone) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Email</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $company->email) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Website</label>
                            <input type="text" name="website" class="form-control" value="{{ old('website', $company->website) }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Registered Address</label>
                        <textarea name="address" class="form-control" rows="2">{{ old('address', $company->address) }}</textarea>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Currency</label>
                            <input type="text" name="currency" class="form-control" value="{{ old('currency', $company->currency) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Symbol</label>
                            <input type="text" name="currency_symbol" class="form-control" value="{{ old('currency_symbol', $company->currency_symbol) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">FY Start</label>
                            <input type="date" name="financial_year_start" class="form-control" value="{{ old('financial_year_start', $company->financial_year_start?->toDateString()) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">FY End</label>
                            <input type="date" name="financial_year_end" class="form-control" value="{{ old('financial_year_end', $company->financial_year_end?->toDateString()) }}" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Status</label>
                        <select name="status" class="form-select w-auto">
                            <option value="active" {{ $company->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $company->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <!-- Module Activation Checklist -->
                    <div class="border rounded p-3 mb-4 bg-light">
                        <h6 class="fw-bold mb-2"><i class="bi bi-toggles text-primary me-1"></i> Enabled Business Modules</h6>
                        <p class="text-muted small mb-3">Activate or disable functional modules to match this company's operational model.</p>

                        <div class="row g-2">
                            @php
                                $modulesList = [
                                    'staff_hr' => 'Staff, HR & Payroll',
                                    'inventory' => 'Inventory & Godown Tracking',
                                    'purchase' => 'Purchase & Procurement',
                                    'sales' => 'Sales & Delivery Dispatch',
                                    'accounts' => 'Double-Entry Accounting & Ledger',
                                    'production' => 'Factory Production & BOM',
                                    'vehicle_route' => 'Vehicles & Route Logistics',
                                    'quality' => 'Quality Checks (QC)',
                                    'crm' => 'CRM & Lead Management',
                                ];
                            @endphp
                            @foreach($modulesList as $key => $title)
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="modules[]" value="{{ $key }}" id="mod_{{ $key }}"
                                        {{ $company->isModuleEnabled($key) ? 'checked' : '' }}>
                                    <label class="form-check-label small fw-semibold" for="mod_{{ $key }}">
                                        {{ $title }}
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2">
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i> Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Company Branches -->
    <div class="col-lg-5">
        <div class="card card-custom mb-4">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold"><i class="bi bi-geo-alt-fill text-danger me-1"></i> Company Branches</h6>
                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#newBranchForm">
                    <i class="bi bi-plus"></i> Add Branch
                </button>
            </div>

            <!-- New Branch Collapsible Form -->
            <div class="collapse p-3 border-bottom bg-light" id="newBranchForm">
                <form action="{{ route('companies.branches.store', $company) }}" method="POST">
                    @csrf
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Branch Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" required placeholder="e.g. Pune Regional Branch">
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Branch Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control form-control-sm" required placeholder="PUN-01">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">City</label>
                            <input type="text" name="city" class="form-control form-control-sm" placeholder="Pune">
                        </div>
                    </div>
                    <div class="mb-2">
                        <label class="form-label small fw-semibold">Address</label>
                        <input type="text" name="address" class="form-control form-control-sm">
                    </div>
                    <div class="row g-2 mb-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Contact No</label>
                            <input type="text" name="contact_number" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">Manager</label>
                            <input type="text" name="branch_manager" class="form-control form-control-sm">
                        </div>
                    </div>
                    <input type="hidden" name="status" value="active">
                    <div class="text-end">
                        <button type="submit" class="btn btn-sm btn-primary">Save Branch</button>
                    </div>
                </form>
            </div>

            <div class="card-body p-0">
                <ul class="list-group list-group-flush">
                    @forelse($company->branches as $branch)
                    <li class="list-group-item p-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <div class="fw-bold">{{ $branch->name }}</div>
                            @if($branch->is_main_branch)
                                <span class="badge bg-primary">Head Office</span>
                            @else
                                <span class="badge bg-light text-dark border">{{ $branch->code }}</span>
                            @endif
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-geo-alt me-1"></i> {{ $branch->city ?? 'No city' }} &middot; {{ $branch->contact_number ?? 'No phone' }}
                        </div>
                        @if($branch->branch_manager)
                        <div class="small text-secondary mt-1">Manager: {{ $branch->branch_manager }}</div>
                        @endif
                    </li>
                    @empty
                    <li class="list-group-item p-3 text-center text-muted">No branches created yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection

