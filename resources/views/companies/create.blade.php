@extends('layouts.app')

@section('title', 'Register Company')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card card-custom">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-building-add text-primary me-2"></i> Register New Company</h5>
                <a href="{{ route('companies.index') }}" class="btn btn-sm btn-outline-secondary">Back to List</a>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('companies.store') }}" method="POST">
                    @csrf
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Company Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" placeholder="e.g. Acme Industries Ltd" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Company Code <span class="text-danger">*</span></label>
                            <input type="text" name="code" class="form-control" value="{{ old('code') }}" placeholder="e.g. ACME-01" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Business Profile <span class="text-danger">*</span></label>
                            <select name="business_type" class="form-select" required>
                                <option value="manufacturing">Manufacturing</option>
                                <option value="wholesale">Wholesale</option>
                                <option value="distribution">Distribution</option>
                                <option value="trading" selected>Trading</option>
                                <option value="retail">Retail</option>
                                <option value="service">Service-Based</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">GST Number</label>
                            <input type="text" name="gst_no" class="form-control" value="{{ old('gst_no') }}" placeholder="e.g. 27AAACA0000A1Z5">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">PAN Number</label>
                            <input type="text" name="pan_no" class="form-control" value="{{ old('pan_no') }}" placeholder="e.g. AAACA0000A">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Registration / CIN</label>
                            <input type="text" name="registration_no" class="form-control" value="{{ old('registration_no') }}" placeholder="e.g. U29100MH2020PTC123456">
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
                            <label class="form-label fw-semibold small">Website URL</label>
                            <input type="text" name="website" class="form-control" value="{{ old('website') }}">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Registered Business Address</label>
                        <textarea name="address" class="form-control" rows="2">{{ old('address') }}</textarea>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Currency Code</label>
                            <input type="text" name="currency" class="form-control" value="{{ old('currency', 'INR') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Currency Symbol</label>
                            <input type="text" name="currency_symbol" class="form-control" value="{{ old('currency_symbol', '₹') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Financial Year Start <span class="text-danger">*</span></label>
                            <input type="date" name="financial_year_start" class="form-control" value="{{ old('financial_year_start', '2026-04-01') }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Financial Year End <span class="text-danger">*</span></label>
                            <input type="date" name="financial_year_end" class="form-control" value="{{ old('financial_year_end', '2027-03-31') }}" required>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-semibold small">Company Status</label>
                        <select name="status" class="form-select w-auto">
                            <option value="active" selected>Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('companies.index') }}" class="btn btn-light border">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i> Register Company</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

