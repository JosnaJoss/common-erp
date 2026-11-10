@extends('layouts.app')

@section('title', 'Product Lines')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Product Lines & Assemblies</h4>
        <p class="text-muted small mb-0">High-level product models grouped with variants and components.</p>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newProductModal">
        <i class="bi bi-plus-lg me-1"></i> Add Product
    </button>
</div>

<div class="card card-custom">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Product Name & Code</th>
                        <th>Category</th>
                        <th>Subcategory</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $prod)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">{{ $prod->name }}</div>
                            <span class="badge bg-light text-primary border">{{ $prod->code }}</span>
                        </td>
                        <td>{{ $prod->category->name ?? 'N/A' }}</td>
                        <td>{{ $prod->subcategory->name ?? 'N/A' }}</td>
                        <td class="small text-muted">{{ $prod->description ?? 'No description' }}</td>
                        <td>
                            <span class="badge bg-{{ $prod->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($prod->status) }}
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <button type="button" class="btn btn-sm btn-light border text-primary" data-bs-toggle="modal" data-bs-target="#editProductModal{{ $prod->id }}" title="Edit Product {{ $prod->name }}">
                                <i class="bi bi-pencil me-1"></i> Edit
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="text-center py-4 text-muted">No products defined yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
        <div class="p-3 border-top">
            {{ $products->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="newProductModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('master.products.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Product Line</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Product Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Apex 5HP Induction Motor">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Product Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" required placeholder="e.g. PRD-MTR-5HP">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Subcategory</label>
                        <select name="subcategory_id" class="form-select">
                            <option value="">Select Subcategory</option>
                            @foreach($subcategories as $sub)
                            <option value="{{ $sub->id }}">{{ $sub->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Optional product description..."></textarea>
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
                <button type="submit" class="btn btn-primary">Save Product</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Product Modals -->
@foreach($products as $prod)
<div class="modal fade" id="editProductModal{{ $prod->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('master.products.update', $prod) }}" method="POST" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Product: {{ $prod->name }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Product Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $prod->name) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Product Code <span class="text-danger">*</span></label>
                    <input type="text" name="code" class="form-control" value="{{ old('code', $prod->code) }}" required>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Category</label>
                        <select name="category_id" class="form-select">
                            <option value="">Select Category</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $prod->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Subcategory</label>
                        <select name="subcategory_id" class="form-select">
                            <option value="">Select Subcategory</option>
                            @foreach($subcategories as $sub)
                            <option value="{{ $sub->id }}" {{ old('subcategory_id', $prod->subcategory_id) == $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $prod->description) }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" {{ old('status', $prod->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $prod->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Update Product</button>
            </div>
        </form>
    </div>
</div>
@endforeach
@endsection
