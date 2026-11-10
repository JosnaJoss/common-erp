@extends('layouts.app')

@section('title', 'Categories & Subcategories')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Categories & Subcategories</h4>
        <p class="text-muted small mb-0">Organize products, components, and raw materials into structured classifications.</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newSubcategoryModal">
            <i class="bi bi-plus me-1"></i> Add Subcategory
        </button>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newCategoryModal">
            <i class="bi bi-plus-lg me-1"></i> Add Category
        </button>
    </div>
</div>

<div class="row g-4 mb-4">
    @forelse($categories as $category)
    <div class="col-md-6">
        <div class="card card-custom h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">{{ $category->name }}</h6>
                    <small class="text-muted">{{ $category->description ?? 'No description' }}</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-{{ $category->status === 'active' ? 'success' : 'secondary' }}">
                        {{ ucfirst($category->status) }}
                    </span>
                    <button type="button" class="btn btn-sm btn-light border text-primary" data-bs-toggle="modal" data-bs-target="#editCategoryModal{{ $category->id }}" title="Edit Category {{ $category->name }}">
                        <i class="bi bi-pencil me-1"></i> Edit
                    </button>
                </div>
            </div>
            <div class="card-body p-3">
                <div class="small fw-bold text-muted text-uppercase mb-2">Subcategories:</div>
                <div class="d-flex flex-wrap gap-2">
                    @forelse($category->subcategories as $sub)
                    <span class="badge bg-light text-dark border px-3 py-2 d-inline-flex align-items-center gap-2">
                        <span><i class="bi bi-tag text-primary me-1"></i>{{ $sub->name }}</span>
                        @if($sub->status !== 'active')
                        <span class="badge bg-secondary-subtle text-secondary" style="font-size: 0.65rem;">Inactive</span>
                        @endif
                        <button type="button" class="btn btn-link p-0 text-primary ms-1" data-bs-toggle="modal" data-bs-target="#editSubcategoryModal{{ $sub->id }}" title="Edit Subcategory {{ $sub->name }}">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </span>
                    @empty
                    <span class="text-muted small">No subcategories created for this category yet.</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @empty
    <div class="col-12">
        <div class="card card-custom text-center py-5">
            <i class="bi bi-folder-x fs-1 text-muted mb-2"></i>
            <h6 class="text-muted">No item categories found. Click "Add Category" above to get started.</h6>
        </div>
    </div>
    @endforelse
</div>

@if($categories->hasPages())
<div class="mb-4">
    {{ $categories->links() }}
</div>
@endif

<!-- All Subcategories Table View -->
@php
    $allSubcategories = collect();
    foreach($categories as $category) {
        foreach($category->subcategories as $sub) {
            $allSubcategories->push($sub);
        }
    }
@endphp

@if($allSubcategories->count() > 0)
<div class="card card-custom mt-4">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0 text-dark"><i class="bi bi-tags text-primary me-2"></i> Subcategories Directory</h6>
        <span class="badge bg-light text-muted border">{{ $allSubcategories->count() }} Subcategories</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Subcategory Name</th>
                        <th>Parent Category</th>
                        <th>Description</th>
                        <th>Status</th>
                        <th class="text-end pe-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($allSubcategories as $sub)
                    <tr>
                        <td class="ps-4 fw-bold text-dark">
                            <i class="bi bi-tag text-primary me-1"></i> {{ $sub->name }}
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border">{{ $sub->category->name ?? 'N/A' }}</span>
                        </td>
                        <td class="small text-muted">{{ $sub->description ?? 'No description' }}</td>
                        <td>
                            <span class="badge bg-{{ $sub->status === 'active' ? 'success' : 'secondary' }}">
                                {{ ucfirst($sub->status) }}
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            <button type="button" class="btn btn-sm btn-light border text-primary" data-bs-toggle="modal" data-bs-target="#editSubcategoryModal{{ $sub->id }}" title="Edit Subcategory">
                                <i class="bi bi-pencil me-1"></i> Edit
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- Add Category Modal -->
<div class="modal fade" id="newCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('master.categories.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Item Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Category Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Electrical Components">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Optional category description..."></textarea>
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
                <button type="submit" class="btn btn-primary">Save Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Subcategory Modal -->
<div class="modal fade" id="newSubcategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('master.subcategories.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Subcategory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Parent Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Select Category</option>
                        @foreach($allCategories ?? $categories as $cat)
                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Subcategory Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required placeholder="e.g. Induction Motors">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="2" placeholder="Optional subcategory description..."></textarea>
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
                <button type="submit" class="btn btn-primary">Save Subcategory</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Category & Subcategory Modals -->
@foreach($categories as $category)
<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal{{ $category->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('master.categories.update', $category) }}" method="POST" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Category Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $category->name) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $category->description) }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" {{ old('status', $category->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $category->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Update Category</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Subcategory Modals for this category -->
@foreach($category->subcategories as $sub)
<div class="modal fade" id="editSubcategoryModal{{ $sub->id }}" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('master.subcategories.update', $sub) }}" method="POST" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Edit Subcategory</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Parent Category <span class="text-danger">*</span></label>
                    <select name="category_id" class="form-select" required>
                        <option value="">Select Category</option>
                        @foreach($allCategories ?? $categories as $cat)
                        <option value="{{ $cat->id }}" {{ old('category_id', $sub->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Subcategory Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $sub->name) }}" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $sub->description) }}</textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Status</label>
                    <select name="status" class="form-select">
                        <option value="active" {{ old('status', $sub->status) === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ old('status', $sub->status) === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Update Subcategory</button>
            </div>
        </form>
    </div>
</div>
@endforeach
@endforeach
@endsection
