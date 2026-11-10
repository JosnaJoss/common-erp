@extends('layouts.app')

@section('title', 'Register Staff')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card card-custom">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-person-plus text-primary me-2"></i> Register New Staff Member</h5>
                <a href="{{ route('staff.index') }}" class="btn btn-sm btn-outline-secondary">Back to List</a>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('staff.store') }}" method="POST">
                    @csrf

                    <!-- 1. PERSONAL INFORMATION -->
                    <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">
                        <i class="bi bi-person me-1"></i> 1. Personal Information
                    </h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" value="{{ old('first_name') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" value="{{ old('last_name') }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Employee ID <span class="text-danger">*</span></label>
                            <input type="text" name="employee_id" class="form-control" value="{{ old('employee_id', 'EMP-' . rand(100, 999)) }}" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Email Address</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Aadhaar Number</label>
                            <input type="text" name="aadhaar_number" class="form-control" value="{{ old('aadhaar_number') }}" placeholder="12-digit UID">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">PAN Number</label>
                            <input type="text" name="pan_number" class="form-control" value="{{ old('pan_number') }}" placeholder="10-digit PAN">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select" required>
                                <option value="Male" {{ old('gender') == 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('gender') == 'Female' ? 'selected' : '' }}>Female</option>
                                <option value="Other" {{ old('gender') == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Blood Group</label>
                            <select name="blood_group" class="form-select">
                                <option value="">Select</option>
                                <option value="A+">A+</option>
                                <option value="A-">A-</option>
                                <option value="B+">B+</option>
                                <option value="B-">B-</option>
                                <option value="O+">O+</option>
                                <option value="O-">O-</option>
                                <option value="AB+">AB+</option>
                                <option value="AB-">AB-</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Marital Status</label>
                            <select name="marital_status" class="form-select">
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Divorced">Divorced</option>
                                <option value="Widowed">Widowed</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Permanent Address</label>
                            <textarea name="permanent_address" class="form-control" rows="2">{{ old('permanent_address') }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Temporary / Current Address</label>
                            <textarea name="temporary_address" class="form-control" rows="2">{{ old('temporary_address') }}</textarea>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Emergency Contact Person</label>
                            <input type="text" name="emergency_contact_person" class="form-control" value="{{ old('emergency_contact_person') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Relationship</label>
                            <input type="text" name="emergency_contact_relation" class="form-control" value="{{ old('emergency_contact_relation') }}" placeholder="e.g. Spouse / Father">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Emergency Phone Number</label>
                            <input type="text" name="emergency_contact_number" class="form-control" value="{{ old('emergency_contact_number') }}">
                        </div>
                    </div>

                    <!-- 2. EMPLOYMENT DETAILS -->
                    <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">
                        <i class="bi bi-briefcase me-1"></i> 2. Employment & Compensation
                    </h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Designation <span class="text-danger">*</span></label>
                            <input type="text" name="designation" class="form-control" value="{{ old('designation') }}" required placeholder="e.g. Senior Machine Operator">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Department <span class="text-danger">*</span></label>
                            <select name="department_id" class="form-select" required>
                                <option value="">Select Department</option>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Branch</label>
                            <select name="branch_id" class="form-select">
                                <option value="">Select Branch</option>
                                @foreach($branches as $br)
                                <option value="{{ $br->id }}" {{ old('branch_id') == $br->id ? 'selected' : '' }}>{{ $br->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Employment Type <span class="text-danger">*</span></label>
                            <select name="employee_type" class="form-select" required>
                                <option value="salaried" {{ old('employee_type') == 'salaried' ? 'selected' : '' }}>Monthly Salaried</option>
                                <option value="daily_wage" {{ old('employee_type') == 'daily_wage' ? 'selected' : '' }}>Daily Wage</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Gross Salary (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="salary_amount" class="form-control" value="{{ old('salary_amount', 30000) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Date of Joining <span class="text-danger">*</span></label>
                            <input type="date" name="date_of_joining" class="form-control" value="{{ old('date_of_joining', now()->toDateString()) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" selected>Active</option>
                                <option value="probation">Probation</option>
                                <option value="resigned">Resigned</option>
                                <option value="terminated">Terminated</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Basic Salary (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="basic_salary" class="form-control" value="{{ old('basic_salary', 15000) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">HRA (₹)</label>
                            <input type="number" step="0.01" name="hra" class="form-control" value="{{ old('hra', 7500) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Other Allowances (₹)</label>
                            <input type="number" step="0.01" name="other_allowances" class="form-control" value="{{ old('other_allowances', 7500) }}">
                        </div>
                    </div>

                    <!-- 3. STATUTORY (PF / ESI) -->
                    <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">
                        <i class="bi bi-shield-check me-1"></i> 3. Statutory Benefits (Configurable PF & ESI)
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" name="pf_applicable" value="1" id="pfSwitch" {{ old('pf_applicable') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold small" for="pfSwitch">PF Applicable</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">PF Number</label>
                            <input type="text" name="pf_number" class="form-control" value="{{ old('pf_number') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">PF UAN</label>
                            <input type="text" name="pf_uan" class="form-control" value="{{ old('pf_uan') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">PF Deduction Rate (%)</label>
                            <input type="number" step="0.01" name="pf_rate_percent" class="form-control" value="{{ old('pf_rate_percent', 12.00) }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" name="esi_applicable" value="1" id="esiSwitch" {{ old('esi_applicable') ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold small" for="esiSwitch">ESI Applicable</label>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small">ESI IP Number</label>
                            <input type="text" name="esi_number" class="form-control" value="{{ old('esi_number') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">ESI Rate (%)</label>
                            <input type="number" step="0.01" name="esi_rate_percent" class="form-control" value="{{ old('esi_rate_percent', 0.75) }}">
                        </div>
                    </div>

                    <!-- 4. BANK & DRIVING LICENSE -->
                    <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">
                        <i class="bi bi-bank me-1"></i> 4. Bank Account & Driving License Details
                    </h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name') }}" placeholder="e.g. HDFC Bank">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Account Holder Name</label>
                            <input type="text" name="bank_holder_name" class="form-control" value="{{ old('bank_holder_name') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Account Number</label>
                            <input type="text" name="bank_account_number" class="form-control" value="{{ old('bank_account_number') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">IFSC Code</label>
                            <input type="text" name="bank_ifsc" class="form-control" value="{{ old('bank_ifsc') }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Driving License No.</label>
                            <input type="text" name="license_number" class="form-control" value="{{ old('license_number') }}" placeholder="e.g. MH-02-...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">License Vehicle Class</label>
                            <input type="text" name="license_class" class="form-control" value="{{ old('license_class') }}" placeholder="e.g. LMV / Transport">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">License Start Date</label>
                            <input type="date" name="license_start_date" class="form-control" value="{{ old('license_start_date') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">License Expiry Date</label>
                            <input type="date" name="license_expiry_date" class="form-control" value="{{ old('license_expiry_date') }}">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('staff.index') }}" class="btn btn-light border">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i> Register Staff</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

