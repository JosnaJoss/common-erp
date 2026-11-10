@extends('layouts.app')

@section('title', 'Items & Pricing')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Items & Products Catalog</h4>
        <p class="text-muted small mb-0">Multi-tier pricing, inventory specifications, GST rates, and reorder levels.</p>
    </div>
    <a href="{{ route('master.items.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i> Add New Item
    </a>
</div>

<!-- FILTERS -->
<div class="card card-custom mb-4">
    <div class="card-body p-3">
        <form method="GET" class="row g-2 align-items-center">
            <div class="col-md-4">
                <input type="text" name="search" class="form-control form-control-sm" placeholder="Search item name, code, or HSN..." value="{{ request('search') }}">
            </div>
            <div class="col-md-3">
                <select name="category_id" class="form-select form-select-sm">
                    <option value="">All Categories</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <select name="item_type" class="form-select form-select-sm">
                    <option value="">All Item Types</option>
                    <option value="sales_item" {{ request('item_type') == 'sales_item' ? 'selected' : '' }}>Sales Item</option>
                    <option value="raw_material" {{ request('item_type') == 'raw_material' ? 'selected' : '' }}>Raw Material</option>
                    <option value="both" {{ request('item_type') == 'both' ? 'selected' : '' }}>Both (Sales & Raw)</option>
                </select>
            </div>
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary w-100"><i class="bi bi-funnel me-1"></i> Filter</button>
                <a href="{{ route('master.items.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Item Details</th>
                        <th>Type & Category</th>
                        <th class="text-end">Price Tiers (Min / Normal / Max)</th>
                        <th>Tax & HSN</th>
                        <th class="text-center">Stock</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $item)
                    @php
                        $stockQty = $item->totalStockQuantity();
                        $isLow = $stockQty <= (float)$item->reorder_quantity;
                    @endphp
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark">{{ $item->name }}</div>
                            <span class="badge bg-light text-muted border">{{ $item->code }}</span>
                            @if($item->weight > 0)
                                <small class="text-muted ms-1">({{ $item->weight }} kg)</small>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-secondary-subtle text-secondary text-capitalize mb-1 d-inline-block">
                                {{ str_replace('_', ' ', $item->item_type) }}
                            </span>
                            <div class="small text-muted">{{ $item->category->name ?? 'Uncategorized' }}</div>
                        </td>
                        <td class="text-end">
                            <div class="fw-bold text-primary">₹{{ number_format($item->normal_price, 2) }}</div>
                            <div class="small text-muted" style="font-size: 0.75rem;">
                                Min: ₹{{ number_format($item->min_price, 2) }} | Max: ₹{{ number_format($item->max_price, 2) }}
                            </div>
                        </td>
                        <td class="small">
                            <div>GST: <span class="fw-semibold">{{ $item->gst_percentage }}%</span></div>
                            <span class="text-muted">HSN: {{ $item->hsn_code ?? 'N/A' }}</span>
                        </td>
                        <td class="text-center">
                            <span class="fw-bold {{ $isLow ? 'text-danger' : 'text-success' }}">
                                {{ $stockQty }} {{ $item->unit->symbol ?? '' }}
                            </span>
                            @if($isLow)
                            <div class="badge bg-danger-subtle text-danger" style="font-size: 0.65rem;">
                                Reorder &le; {{ $item->reorder_quantity }}
                            </div>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $item->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($item->status) }}
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('master.items.edit', $item) }}" class="btn btn-sm btn-light border" title="Edit Item">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form action="{{ route('master.items.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this item?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light border text-danger" title="Delete Item">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No items found matching the filter criteria.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($items->hasPages())
    <div class="card-footer bg-white border-top py-3">
        {{ $items->links() }}
    </div>
    @endif
</div>
@endsection

