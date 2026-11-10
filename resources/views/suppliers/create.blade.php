@extends('layouts.app')

@section('title', 'Register Supplier')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card card-custom">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-truck text-primary me-2"></i> Register New Supplier</h5>
                <a href="{{ route('suppliers.index') }}" class="btn btn-sm btn-outline-secondary">Back to List</a>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('suppliers.store') }}" method="POST">
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Supplier / Vendor Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Tata Steel Tubes & Alloys Ltd" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Supplier Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="e.g. SUPP-TATA-01" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Contact Person</label>
                            <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Email Address</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">GST Number</label>
                            <input type="text" name="gst_number" class="form-control" value="{{ old('gst_number') }}" placeholder="e.g. 27AAACT2727Q1ZW">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">PAN Number</label>
                            <input type="text" name="pan_number" class="form-control" value="{{ old('pan_number') }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Street Address</label>
                            <input type="text" name="address" class="form-control" value="{{ old('address') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold small">City</label>
                            <input type="text" name="city" class="form-control" value="{{ old('city') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-semibold small">Pincode</label>
                            <input type="text" name="pincode" class="form-control" value="{{ old('pincode') }}">
                        </div>
                    </div>

                    <div class="border rounded p-3 mb-4 bg-light">
                        <h6 class="fw-bold mb-3"><i class="bi bi-wallet2 text-primary me-1"></i> Opening Ledger & Balance Configuration</h6>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Opening Balance Amount (₹) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" name="opening_balance" class="form-control" value="{{ old('opening_balance', 0) }}" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Balance Type <span class="text-danger">*</span></label>
                                <select name="account_type" class="form-select" required>
                                    <option value="credit" selected>Credit (Payable to Supplier)</option>
                                    <option value="debit">Debit (Advance with Supplier)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-semibold small">Opening Balance Date</label>
                                <input type="date" name="opening_balance_date" class="form-control" value="{{ old('opening_balance_date', now()->startOfYear()->toDateString()) }}">
                            </div>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Status</label>
                        <select name="status" class="form-select w-auto">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('suppliers.index') }}" class="btn btn-light border">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i> Save Supplier</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

