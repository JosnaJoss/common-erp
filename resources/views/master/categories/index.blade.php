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

<div class="row g-4">
    @foreach($categories as $category)
    <div class="col-md-6">
        <div class="card card-custom h-100">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <div>
                    <h6 class="fw-bold mb-0 text-dark">{{ $category->name }}</h6>
                    <small class="text-muted">{{ $category->description ?? 'No description' }}</small>
                </div>
                <span class="badge bg-{{ $category->status === 'active' ? 'success' : 'secondary' }}">
                    {{ ucfirst($category->status) }}
                </span>
            </div>
            <div class="card-body p-3">
                <div class="small fw-bold text-muted text-uppercase mb-2">Subcategories:</div>
                <div class="d-flex flex-wrap gap-2">
                    @forelse($category->subcategories as $sub)
                    <span class="badge bg-light text-dark border px-3 py-2 d-flex align-items-center gap-1">
                        <i class="bi bi-tag text-primary"></i> {{ $sub->name }}
                    </span>
                    @empty
                    <span class="text-muted small">No subcategories created for this category yet.</span>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>

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
                    <textarea name="description" class="form-control" rows="2"></textarea>
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
                        @foreach($categories as $cat)
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
                    <textarea name="description" class="form-control" rows="2"></textarea>
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
@endsection

