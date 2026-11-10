@extends('layouts.app')

@section('title', 'Register Customer')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card card-custom">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-person-plus text-primary me-2"></i> Register New Customer</h5>
                <a href="{{ route('customers.index') }}" class="btn btn-sm btn-outline-secondary">Back to List</a>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('customers.store') }}" method="POST">
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small">Customer / Company Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Mahavir Engineering Works" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Shop / Trade Name</label>
                            <input type="text" name="shop_name" class="form-control" value="{{ old('shop_name') }}" placeholder="e.g. Mahavir Tools & Hardware">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Customer Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="e.g. CUST-MAH-001" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Email Address</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Price Level (Tier) <span class="text-danger">*</span></label>
                            <select name="price_level" class="form-select" required>
                                <option value="minimum" {{ old('price_level') == 'minimum' ? 'selected' : '' }}>Minimum Price (Wholesale Floor)</option>
                                <option value="normal" {{ old('price_level', 'normal') == 'normal' ? 'selected' : '' }}>Normal Price (Standard Rate)</option>
                                <option value="maximum" {{ old('price_level') == 'maximum' ? 'selected' : '' }}>Maximum Price (MRP / Retail)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Delivery Route</label>
                            <select name="route_id" class="form-select">
                                <option value="">Select Route</option>
                                @foreach($routes as $rt)
                                <option value="{{ $rt->id }}" {{ old('route_id') == $rt->id ? 'selected' : '' }}>{{ $rt->route_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">City Location</label>
                            <select name="location_id" class="form-select">
                                <option value="">Select Location</option>
                                @foreach($locations as $loc)
                                <option value="{{ $loc->id }}" {{ old('location_id') == $loc->id ? 'selected' : '' }}>{{ $loc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Territory Zone</label>
                            <input type="text" name="territory" class="form-control" value="{{ old('territory') }}" placeholder="e.g. West Suburbs">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">GST Number</label>
                            <input type="text" name="gst_number" class="form-control" value="{{ old('gst_number') }}" placeholder="e.g. 27AABCM8899M1Z1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">PAN Number</label>
                            <input type="text" name="pan_number" class="form-control" value="{{ old('pan_number') }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-8">
                            <label class="form-label fw-semibold small">Delivery / Billing Address</label>
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
                                    <option value="debit" selected>Debit (Receivable from Customer)</option>
                                    <option value="credit">Credit (Advance from Customer)</option>
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
                        <a href="{{ route('customers.index') }}" class="btn btn-light border">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i> Save Customer</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

