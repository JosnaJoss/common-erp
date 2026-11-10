@extends('layouts.app')

@section('title', 'Stock Ledger & Movements')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Stock Ledger & Movements</h4>
        <p class="text-muted small mb-0">Complete audit trail of all inward, outward, adjustments, and transfer transactions</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('inventory.inward.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg me-1"></i> Inward
        </a>
        <a href="{{ route('inventory.outward.create') }}" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-dash-lg me-1"></i> Outward
        </a>
    </div>
</div>

<!-- Filters -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="{{ route('inventory.movements') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small fw-bold">Filter by Item</label>
                <select name="item_id" class="form-select form-select-sm">
                    <option value="">All Items</option>
                    @foreach($items as $it)
                        <option value="{{ $it->id }}" {{ request('item_id') == $it->id ? 'selected' : '' }}>{{ $it->name }} ({{ $it->code }})</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Filter by Godown</label>
                <select name="godown_id" class="form-select form-select-sm">
                    <option value="">All Godowns</option>
                    @foreach($godowns as $g)
                        <option value="{{ $g->id }}" {{ request('godown_id') == $g->id ? 'selected' : '' }}>{{ $g->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-bold">Movement Type</label>
                <select name="type" class="form-select form-select-sm">
                    <option value="">All Movement Types</option>
                    <option value="inward" {{ request('type') == 'inward' ? 'selected' : '' }}>Inward (Purchase / Direct)</option>
                    <option value="outward" {{ request('type') == 'outward' ? 'selected' : '' }}>Outward (Sales / Direct)</option>
                    <option value="transfer" {{ request('type') == 'transfer' ? 'selected' : '' }}>Godown Transfer</option>
                    <option value="adjustment" {{ request('type') == 'adjustment' ? 'selected' : '' }}>Adjustment</option>
                </select>
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1"><i class="bi bi-filter me-1"></i> Apply Filter</button>
                <a href="{{ route('inventory.movements') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Movements Table -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Date & Time</th>
                        <th>Item Details</th>
                        <th>Godown</th>
                        <th>Batch</th>
                        <th>Type</th>
                        <th class="text-end">Quantity</th>
                        <th class="text-end">Unit Cost</th>
                        <th>Reference Type</th>
                        <th>Reference / Narration</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $m)
                        <tr>
                            <td>
                                <div class="fw-semibold">{{ \Carbon\Carbon::parse($m->movement_date)->format('d M Y') }}</div>
                                <small class="text-muted">{{ $m->created_at ? $m->created_at->format('H:i') : '' }}</small>
                            </td>
                            <td>
                                <div class="fw-bold">{{ $m->item->name ?? 'Item #' . $m->item_id }}</div>
                                <small class="text-muted">{{ $m->item->code ?? '' }}</small>
                            </td>
                            <td>{{ $m->godown->name ?? 'Main Godown' }}</td>
                            <td>
                                <span class="badge bg-light text-dark border">{{ $m->batch->batch_no ?? 'DEFAULT' }}</span>
                            </td>
                            <td>
                                @if($m->movement_type === 'inward')
                                    <span class="badge bg-success"><i class="bi bi-box-arrow-in-down me-1"></i> Inward</span>
                                @elseif($m->movement_type === 'outward')
                                    <span class="badge bg-danger"><i class="bi bi-box-arrow-up-right me-1"></i> Outward</span>
                                @elseif($m->movement_type === 'transfer')
                                    <span class="badge bg-info text-dark"><i class="bi bi-arrow-left-right me-1"></i> Transfer</span>
                                @else
                                    <span class="badge bg-secondary">{{ ucfirst($m->movement_type) }}</span>
                                @endif
                            </td>
                            <td class="text-end fw-bold {{ $m->movement_type === 'inward' ? 'text-success' : 'text-danger' }}">
                                {{ $m->movement_type === 'inward' ? '+' : '-' }}{{ number_format($m->quantity, 2) }} {{ $m->item->unit->symbol ?? '' }}
                            </td>
                            <td class="text-end">₹ {{ number_format($m->unit_cost, 2) }}</td>
                            <td><span class="badge bg-light text-secondary border">{{ $m->reference_type ?? 'Manual' }}</span></td>
                            <td>
                                <div class="small fw-semibold">{{ $m->reference_number ?? '-' }}</div>
                                <small class="text-muted">{{ $m->remarks ?? '' }}</small>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-clock-history fs-2 d-block mb-2"></i> No stock movements recorded yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($movements->hasPages())
        <div class="card-footer bg-white border-0 py-3">
            {{ $movements->withQueryString()->links() }}
        </div>
    @endif
</div>
@endsection

