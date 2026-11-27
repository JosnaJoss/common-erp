@extends('layouts.app')

@section('title', 'Material Consumption Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Material Consumption Report</h4>
        <p class="text-muted small mb-0">Audit raw material and packing consumption across departments, operations, batches, and locations</p>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('inventory.reports.material_consumption') }}" class="row g-3">
            <div class="col-md-4">
                <label class="form-label small text-muted">Department</label>
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    @foreach($departments as $d)
                        <option value="{{ $d->id }}" {{ request('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label small text-muted">Material / Item</label>
                <select name="item_id" class="form-select form-select-sm">
                    <option value="">All Materials</option>
                    @foreach($items as $i)
                        <option value="{{ $i->id }}" {{ request('item_id') == $i->id ? 'selected' : '' }}>{{ $i->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4 d-flex align-items-end gap-1">
                <button type="submit" class="btn btn-sm btn-primary w-100">Filter</button>
                <a href="{{ route('inventory.reports.material_consumption') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Operation Ref</th>
                        <th>Department</th>
                        <th>Date</th>
                        <th>Material</th>
                        <th>Batch</th>
                        <th>Source Location</th>
                        <th class="text-end">Consumed Qty</th>
                        <th class="text-end">Wastage Qty</th>
                        <th class="text-end">Total Cost</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($consumptions as $c)
                        @php
                            $op = $c->dailyOperation;
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('daily_operations.show', $op->id) }}" class="fw-bold">
                                    {{ $op->operation_number }}
                                </a>
                                <div class="small text-muted">{{ $op->process_name }}</div>
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ $op->department->name ?? '-' }}</span></td>
                            <td>{{ \Carbon\Carbon::parse($op->operation_date)->format('d M Y') }}</td>
                            <td>
                                <strong>{{ $c->item->name }}</strong>
                                <div class="small text-muted"><code>{{ $c->item->code }}</code></div>
                            </td>
                            <td><code>{{ $c->batch_no }}</code></td>
                            <td>{{ $c->sourceGodown->name ?? '-' }}</td>
                            <td class="text-end fw-bold text-primary">{{ number_format($c->consumed_quantity, 3) }} {{ $c->item->unit->symbol ?? '' }}</td>
                            <td class="text-end text-danger">{{ number_format($c->wastage_quantity, 3) }}</td>
                            <td class="text-end fw-bold">₹{{ number_format($c->total_cost, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">No material consumption records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">
            {{ $consumptions->links() }}
        </div>
    </div>
</div>
@endsection
