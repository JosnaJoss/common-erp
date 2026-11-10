@extends('layouts.app')

@section('title', 'Company Module Activation')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Company Module Configuration</h4>
        <p class="text-muted small mb-0">Enable or disable modules dynamically for <strong>{{ $company->name ?? 'Active Company' }}</strong> ({{ ucfirst($company->business_type ?? 'All-in-one') }})</p>
    </div>
</div>

<form action="{{ route('settings.modules.update') }}" method="POST">
    @csrf

    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-white py-3 border-bottom">
            <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-puzzle me-1"></i> Active Modules Configuration</h6>
        </div>
        <div class="card-body">
            <div class="row g-4">
                @php
                    $moduleCatalog = [
                        'staff_hr' => [
                            'name' => 'Staff & Human Resources (HR)',
                            'desc' => 'Staff profiles, attendance, shifts, leave applications, overtime, statutory PF/ESI, and payroll generation.',
                            'icon' => 'bi-people',
                            'color' => 'primary',
                        ],
                        'inventory' => [
                            'name' => 'Stock & Warehouse Management',
                            'desc' => 'Multi-godown inventory, batch tracking with FIFO, inward/outward registers, physical stock reconciliation, transfers, and expiry alerts.',
                            'icon' => 'bi-boxes',
                            'color' => 'success',
                        ],
                        'purchase' => [
                            'name' => 'Procurement & Purchase Cycle',
                            'desc' => 'Requisitions, Purchase Orders (PO), Goods Receipt Notes (GRN), Quality Checks (QC), vendor bills, and supplier payments.',
                            'icon' => 'bi-cart-check',
                            'color' => 'info',
                        ],
                        'sales' => [
                            'name' => 'Sales, Invoicing & CRM',
                            'desc' => 'Sales Orders, 3-tier price levels, GST tax invoicing, customer payments / collections, and dispatches.',
                            'icon' => 'bi-receipt',
                            'color' => 'warning',
                        ],
                        'accounts' => [
                            'name' => 'Accounts & Financial Double-Entry',
                            'desc' => 'Chart of Accounts, General Ledgers, journal vouchers, Day Book, Trial Balance, Profit & Loss, and Balance Sheet.',
                            'icon' => 'bi-bank',
                            'color' => 'danger',
                        ],
                        'production' => [
                            'name' => 'Production & Bill of Materials (BOM)',
                            'desc' => 'Bill of Materials recipe formulation, production work orders, and raw-material to finished-goods conversions.',
                            'icon' => 'bi-gear-wide-connected',
                            'color' => 'secondary',
                        ],
                        'vehicle_route' => [
                            'name' => 'Logistics, Vehicles & Route Management',
                            'desc' => 'Delivery routes, multi-city allocations, vehicle fleet, fitness certificates, and driver dispatches.',
                            'icon' => 'bi-truck',
                            'color' => 'dark',
                        ],
                        'quality' => [
                            'name' => 'Quality Control (QC) & Inspection',
                            'desc' => 'Goods receipt quality inspections, inspection checklists, sample tolerances, and acceptance/rejection workflows.',
                            'icon' => 'bi-shield-check',
                            'color' => 'success',
                        ],
                        'crm' => [
                            'name' => 'CRM & Lead Management',
                            'desc' => 'Sales leads tracking, lead source qualification, conversion pipeline, and follow-up activities.',
                            'icon' => 'bi-person-lines-fill',
                            'color' => 'primary',
                        ],
                    ];

                    $activeKeys = $modules->where('is_enabled', true)->pluck('module_key')->toArray();
                @endphp

                @foreach($moduleCatalog as $key => $mod)
                    <div class="col-md-6">
                        <div class="card h-100 border p-3">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-3 bg-{{ $mod['color'] }} bg-opacity-10 p-3 text-{{ $mod['color'] }}">
                                        <i class="bi {{ $mod['icon'] }} fs-3"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-1">{{ $mod['name'] }}</h6>
                                        <p class="text-muted small mb-0">{{ $mod['desc'] }}</p>
                                    </div>
                                </div>
                                <div class="form-check form-switch ms-3">
                                    <input class="form-check-input fs-5" type="checkbox" name="modules[]" value="{{ $key }}" id="modSwitch{{ $key }}" {{ in_array($key, $activeKeys) ? 'checked' : '' }}>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
        <div class="card-footer bg-light p-3 text-end">
            <button type="submit" class="btn btn-primary px-4">
                <i class="bi bi-check2-circle me-1"></i> Save Module Configuration
            </button>
        </div>
    </div>
</form>
@endsection

