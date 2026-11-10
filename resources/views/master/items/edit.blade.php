@extends('layouts.app')

@section('title', 'Edit Item')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card card-custom">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i> Edit Item: {{ $item->name }}</h5>
                <a href="{{ route('master.items.index') }}" class="btn btn-sm btn-outline-secondary">Back to List</a>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('master.items.update', $item) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Item Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $item->name) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Item Code / SKU <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" value="{{ old('code', $item->code) }}" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Category</label>
                            <select name="category_id" class="form-select">
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ old('category_id', $item->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Subcategory</label>
                            <select name="subcategory_id" class="form-select">
                                <option value="">Select Subcategory</option>
                                @foreach($subcategories as $sub)
                                <option value="{{ $sub->id }}" {{ old('subcategory_id', $item->subcategory_id) == $sub->id ? 'selected' : '' }}>{{ $sub->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Unit of Measure <span class="text-danger">*</span></label>
                            <select name="unit_id" class="form-select" required>
                                <option value="">Select Unit</option>
                                @foreach($units as $unit)
                                <option value="{{ $unit->id }}" {{ old('unit_id', $item->unit_id) == $unit->id ? 'selected' : '' }}>{{ $unit->name }} ({{ $unit->symbol }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Item Nature <span class="text-danger">*</span></label>
                            <select name="item_type" class="form-select" required>
                                <option value="sales_item" {{ old('item_type', $item->item_type) == 'sales_item' ? 'selected' : '' }}>Sales Item</option>
                                <option value="raw_material" {{ old('item_type', $item->item_type) == 'raw_material' ? 'selected' : '' }}>Raw Material</option>
                                <option value="both" {{ old('item_type', $item->item_type) == 'both' ? 'selected' : '' }}>Both (Sales & Raw)</option>
                            </select>
                        </div>
                    </div>

                    <!-- 3-TIER PRICING -->
                    <div class="border rounded p-3 mb-3 bg-light">
                        <h6 class="fw-bold mb-3"><i class="bi bi-tag text-primary me-1"></i> Multi-Tier Customer Pricing (₹)</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Minimum Price (Floor) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="min_price" class="form-control" value="{{ old('min_price', $item->min_price) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Normal Price (Standard) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="normal_price" class="form-control" value="{{ old('normal_price', $item->normal_price) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Maximum Price (MRP / Retail) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="max_price" class="form-control" value="{{ old('max_price', $item->max_price) }}" required>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">GST Percentage (%) <span class="text-danger">*</span></label>
                            <select name="gst_percentage" class="form-select" required>
                                <option value="0" {{ old('gst_percentage', $item->gst_percentage) == '0' ? 'selected' : '' }}>0% (Exempt)</option>
                                <option value="5" {{ old('gst_percentage', $item->gst_percentage) == '5' ? 'selected' : '' }}>5%</option>
                                <option value="12" {{ old('gst_percentage', $item->gst_percentage) == '12' ? 'selected' : '' }}>12%</option>
                                <option value="18" {{ old('gst_percentage', $item->gst_percentage) == '18' ? 'selected' : '' }}>18%</option>
                                <option value="28" {{ old('gst_percentage', $item->gst_percentage) == '28' ? 'selected' : '' }}>28%</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">HSN Code</label>
                            <input type="text" name="hsn_code" class="form-control" value="{{ old('hsn_code', $item->hsn_code) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Weight (in KG per unit)</label>
                            <input type="number" step="0.001" name="weight" class="form-control" value="{{ old('weight', $item->weight) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Reorder Quantity Level <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="reorder_quantity" class="form-control" value="{{ old('reorder_quantity', $item->reorder_quantity) }}" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Status</label>
                        <select name="status" class="form-select w-auto">
                            <option value="active" {{ $item->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $item->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('master.items.index') }}" class="btn btn-light border">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i> Update Item</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

