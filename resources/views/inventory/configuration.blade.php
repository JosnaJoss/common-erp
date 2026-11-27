@extends('layouts.app')

@section('title', 'Branch & Department Stock Configuration')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Branch & Department Stock Configuration</h4>
        <p class="text-muted small mb-0">Configure centralized warehouse mode versus multi-location stock management per branch and department</p>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

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

<!-- 1. Branch Stock Mode Configuration -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-building me-2"></i>Branch-Level Stock Management Mode</h5>
        <small class="text-muted">In Centralized Mode, all departments draw materials directly from one central godown without inter-departmental transfers.</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Branch</th>
                        <th>Code</th>
                        <th>Stock Management Mode</th>
                        <th>Designated Central Godown</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($branches as $b)
                        <tr>
                            <form method="POST" action="{{ route('inventory.branches.stock_config', $b->id) }}">
                                @csrf
                                <td>
                                    <span class="fw-bold">{{ $b->name }}</span>
                                    @if($b->is_main_branch)
                                        <span class="badge bg-primary ms-1">HQ</span>
                                    @endif
                                </td>
                                <td><code>{{ $b->code }}</code></td>
                                <td>
                                    <select name="stock_mode" class="form-select form-select-sm" style="max-width: 220px;">
                                        <option value="centralized" {{ $b->stock_mode === 'centralized' ? 'selected' : '' }}>Centralized Warehouse</option>
                                        <option value="multiple_locations" {{ $b->stock_mode === 'multiple_locations' ? 'selected' : '' }}>Multiple Stock Locations</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="central_godown_id" class="form-select form-select-sm" style="max-width: 260px;">
                                        <option value="">-- None / Select Central Godown --</option>
                                        @foreach($godowns->where('branch_id', $b->id) as $g)
                                            <option value="{{ $g->id }}" {{ $b->central_godown_id == $g->id ? 'selected' : '' }}>
                                                {{ $g->name }} ({{ strtoupper($g->location_type) }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="text-end">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-save me-1"></i> Save
                                    </button>
                                </td>
                            </form>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 2. Department-Wise Stock Configuration -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3 border-0">
        <h5 class="fw-bold mb-0 text-primary"><i class="bi bi-diagram-3 me-2"></i>Department-Wise Inventory Configuration</h5>
        <small class="text-muted">Set whether each department maintains an independent stock balance, or consumes materials directly from a source warehouse.</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Department</th>
                        <th>Branch</th>
                        <th>Type</th>
                        <th>Maintains Independent Stock?</th>
                        <th>Assigned Stock Location</th>
                        <th>Designated Source Warehouse</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($departments as $d)
                        <tr>
                            <form method="POST" action="{{ route('inventory.departments.stock_config', $d->id) }}">
                                @csrf
                                <td>
                                    <span class="fw-bold">{{ $d->name }}</span>
                                    <div class="text-muted small"><code>{{ $d->code }}</code></div>
                                </td>
                                <td>{{ $d->branch->name ?? 'All Branches' }}</td>
                                <td><span class="badge bg-light text-dark border">{{ strtoupper($d->department_type) }}</span></td>
                                <td>
                                    <select name="maintains_stock" class="form-select form-select-sm" style="max-width: 140px;">
                                        <option value="1" {{ $d->maintains_stock ? 'selected' : '' }}>Yes (Store)</option>
                                        <option value="0" {{ !$d->maintains_stock ? 'selected' : '' }}>No (Direct)</option>
                                    </select>
                                </td>
                                <td>
                                    <select name="default_godown_id" class="form-select form-select-sm" style="max-width: 220px;">
                                        <option value="">-- None --</option>
                                        @foreach($godowns as $g)
                                            <option value="{{ $g->id }}" {{ $d->default_godown_id == $g->id ? 'selected' : '' }}>
                                                {{ $g->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td>
                                    <select name="source_godown_id" class="form-select form-select-sm" style="max-width: 220px;">
                                        <option value="">-- Default Central --</option>
                                        @foreach($godowns as $g)
                                            <option value="{{ $g->id }}" {{ $d->source_godown_id == $g->id ? 'selected' : '' }}>
                                                {{ $g->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="text-end">
                                    <button type="submit" class="btn btn-sm btn-outline-primary">
                                        <i class="bi bi-save me-1"></i> Update
                                    </button>
                                </td>
                            </form>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
