@extends('layouts.app')

@section('title', 'Edit Staff')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="card card-custom">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i> Edit Staff: {{ $staff->full_name }} ({{ $staff->employee_id }})</h5>
                <a href="{{ route('staff.index') }}" class="btn btn-sm btn-outline-secondary">Back to List</a>
            </div>
            <div class="card-body p-4">
                <form action="{{ route('staff.update', $staff) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <!-- 1. PERSONAL INFORMATION -->
                    <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">
                        <i class="bi bi-person me-1"></i> 1. Personal Information
                    </h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">First Name <span class="text-danger">*</span></label>
                            <input type="text" name="first_name" class="form-control" value="{{ old('first_name', $staff->first_name) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Last Name <span class="text-danger">*</span></label>
                            <input type="text" name="last_name" class="form-control" value="{{ old('last_name', $staff->last_name) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Employee ID <span class="text-danger">*</span></label>
                            <input type="text" name="employee_id" class="form-control" value="{{ old('employee_id', $staff->employee_id) }}" required>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Email Address</label>
                            <input type="email" name="email" class="form-control" value="{{ old('email', $staff->email) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Phone Number</label>
                            <input type="text" name="phone" class="form-control" value="{{ old('phone', $staff->phone) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Aadhaar Number</label>
                            <input type="text" name="aadhaar_number" class="form-control" value="{{ old('aadhaar_number', $staff->aadhaar_number) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">PAN Number</label>
                            <input type="text" name="pan_number" class="form-control" value="{{ old('pan_number', $staff->pan_number) }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Date of Birth</label>
                            <input type="date" name="date_of_birth" class="form-control" value="{{ old('date_of_birth', $staff->date_of_birth?->toDateString()) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Gender <span class="text-danger">*</span></label>
                            <select name="gender" class="form-select" required>
                                <option value="Male" {{ old('gender', $staff->gender) == 'Male' ? 'selected' : '' }}>Male</option>
                                <option value="Female" {{ old('gender', $staff->gender) == 'Female' ? 'selected' : '' }}>Female</option>
                                <option value="Other" {{ old('gender', $staff->gender) == 'Other' ? 'selected' : '' }}>Other</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Blood Group</label>
                            <input type="text" name="blood_group" class="form-control" value="{{ old('blood_group', $staff->blood_group) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Marital Status</label>
                            <select name="marital_status" class="form-select">
                                <option value="Single" {{ old('marital_status', $staff->marital_status) == 'Single' ? 'selected' : '' }}>Single</option>
                                <option value="Married" {{ old('marital_status', $staff->marital_status) == 'Married' ? 'selected' : '' }}>Married</option>
                                <option value="Divorced" {{ old('marital_status', $staff->marital_status) == 'Divorced' ? 'selected' : '' }}>Divorced</option>
                                <option value="Widowed" {{ old('marital_status', $staff->marital_status) == 'Widowed' ? 'selected' : '' }}>Widowed</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Permanent Address</label>
                            <textarea name="permanent_address" class="form-control" rows="2">{{ old('permanent_address', $staff->permanent_address) }}</textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-semibold small">Temporary / Current Address</label>
                            <textarea name="temporary_address" class="form-control" rows="2">{{ old('temporary_address', $staff->temporary_address) }}</textarea>
                        </div>
                    </div>

                    <!-- 2. EMPLOYMENT DETAILS -->
                    <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">
                        <i class="bi bi-briefcase me-1"></i> 2. Employment & Compensation
                    </h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Designation <span class="text-danger">*</span></label>
                            <input type="text" name="designation" class="form-control" value="{{ old('designation', $staff->designation) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Department <span class="text-danger">*</span></label>
                            <select name="department_id" class="form-select" required>
                                @foreach($departments as $dept)
                                <option value="{{ $dept->id }}" {{ old('department_id', $staff->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Branch</label>
                            <select name="branch_id" class="form-select">
                                @foreach($branches as $br)
                                <option value="{{ $br->id }}" {{ old('branch_id', $staff->branch_id) == $br->id ? 'selected' : '' }}>{{ $br->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Employment Type <span class="text-danger">*</span></label>
                            <select name="employee_type" class="form-select" required>
                                <option value="salaried" {{ old('employee_type', $staff->employee_type) == 'salaried' ? 'selected' : '' }}>Salaried</option>
                                <option value="daily_wage" {{ old('employee_type', $staff->employee_type) == 'daily_wage' ? 'selected' : '' }}>Daily Wage</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Gross Salary (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="salary_amount" class="form-control" value="{{ old('salary_amount', $staff->salary_amount) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Date of Joining</label>
                            <input type="date" name="date_of_joining" class="form-control" value="{{ old('date_of_joining', $staff->date_of_joining?->toDateString()) }}" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Status</label>
                            <select name="status" class="form-select">
                                <option value="active" {{ old('status', $staff->status) == 'active' ? 'selected' : '' }}>Active</option>
                                <option value="probation" {{ old('status', $staff->status) == 'probation' ? 'selected' : '' }}>Probation</option>
                                <option value="resigned" {{ old('status', $staff->status) == 'resigned' ? 'selected' : '' }}>Resigned</option>
                                <option value="terminated" {{ old('status', $staff->status) == 'terminated' ? 'selected' : '' }}>Terminated</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Basic Salary (₹) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="basic_salary" class="form-control" value="{{ old('basic_salary', $staff->basic_salary) }}" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">HRA (₹)</label>
                            <input type="number" step="0.01" name="hra" class="form-control" value="{{ old('hra', $staff->hra) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">Other Allowances (₹)</label>
                            <input type="number" step="0.01" name="other_allowances" class="form-control" value="{{ old('other_allowances', $staff->other_allowances) }}">
                        </div>
                    </div>

                    <!-- 3. STATUTORY (PF / ESI) -->
                    <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">
                        <i class="bi bi-shield-check me-1"></i> 3. Statutory Benefits
                    </h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" name="pf_applicable" value="1" id="pfSwitch" {{ old('pf_applicable', $staff->pf_applicable) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold small" for="pfSwitch">PF Applicable</label>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">PF Number</label>
                            <input type="text" name="pf_number" class="form-control" value="{{ old('pf_number', $staff->pf_number) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">PF UAN</label>
                            <input type="text" name="pf_uan" class="form-control" value="{{ old('pf_uan', $staff->pf_uan) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">PF Rate (%)</label>
                            <input type="number" step="0.01" name="pf_rate_percent" class="form-control" value="{{ old('pf_rate_percent', $staff->pf_rate_percent) }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="form-check form-switch mt-4">
                                <input class="form-check-input" type="checkbox" name="esi_applicable" value="1" id="esiSwitch" {{ old('esi_applicable', $staff->esi_applicable) ? 'checked' : '' }}>
                                <label class="form-check-label fw-semibold small" for="esiSwitch">ESI Applicable</label>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-semibold small">ESI IP Number</label>
                            <input type="text" name="esi_number" class="form-control" value="{{ old('esi_number', $staff->esi_number) }}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-semibold small">ESI Rate (%)</label>
                            <input type="number" step="0.01" name="esi_rate_percent" class="form-control" value="{{ old('esi_rate_percent', $staff->esi_rate_percent) }}">
                        </div>
                    </div>

                    <!-- 4. BANK & LICENSE -->
                    <h6 class="fw-bold mb-3 text-primary border-bottom pb-2">
                        <i class="bi bi-bank me-1"></i> 4. Bank Account & License
                    </h6>
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Bank Name</label>
                            <input type="text" name="bank_name" class="form-control" value="{{ old('bank_name', $staff->bank_name) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Account Holder Name</label>
                            <input type="text" name="bank_holder_name" class="form-control" value="{{ old('bank_holder_name', $staff->bank_holder_name) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Account Number</label>
                            <input type="text" name="bank_account_number" class="form-control" value="{{ old('bank_account_number', $staff->bank_account_number) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">IFSC Code</label>
                            <input type="text" name="bank_ifsc" class="form-control" value="{{ old('bank_ifsc', $staff->bank_ifsc) }}">
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">Driving License No.</label>
                            <input type="text" name="license_number" class="form-control" value="{{ old('license_number', $staff->license_number) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">License Vehicle Class</label>
                            <input type="text" name="license_class" class="form-control" value="{{ old('license_class', $staff->license_class) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">License Start Date</label>
                            <input type="date" name="license_start_date" class="form-control" value="{{ old('license_start_date', $staff->license_start_date?->toDateString()) }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-semibold small">License Expiry Date</label>
                            <input type="date" name="license_expiry_date" class="form-control" value="{{ old('license_expiry_date', $staff->license_expiry_date?->toDateString()) }}">
                        </div>
                    </div>

                    <div class="d-flex justify-content-end gap-2 border-top pt-3">
                        <a href="{{ route('staff.index') }}" class="btn btn-light border">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4"><i class="bi bi-check-lg me-1"></i> Update Staff</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

