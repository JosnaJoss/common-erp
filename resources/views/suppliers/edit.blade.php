@extends('layouts.app')

@section('title', 'Edit Supplier')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card card-custom">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i> Edit Supplier: {{ $supplier->name }}</h5>
                <a href="{{ route('suppliers.index') }}" class="btn btn-sm btn-outline-secondary">Back to List</a>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('suppliers.update', $supplier) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Supplier Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $supplier->name) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Supplier Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" value="{{ old('code', $supplier->code) }}" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person', $supplier->contact_person) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $supplier->phone) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Email Address</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $supplier->email) }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">GST Number</label>
                            <input type="text" name="gst_number" class="form-control" value="{{ old('gst_number', $supplier->gst_number) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">PAN Number</label>
                            <input type="text" name="pan_number" class="form-control" value="{{ old('pan_number', $supplier->pan_number) }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Street Address</label>
                            <input type="text" name="address" class="form-control" value="{{ old('address', $supplier->address) }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold small">City</label>
                            <input type="text" name="city" class="form-control" value="{{ old('city', $supplier->city) }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold small">Pincode</label>
                            <input type="text" name="pincode" class="form-control" value="{{ old('pincode', $supplier->pincode) }}">
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Status</label>
                        <select name="status" class="form-select w-auto">
                            <option value="active" {{ $supplier->status === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $supplier->status === 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('suppliers.index') }}" class="btn btn-light border">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i> Update Supplier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

