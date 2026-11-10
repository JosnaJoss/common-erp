@extends('layouts.app')

@section('title', 'Staff Profile - ' . $staff->full_name)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">{{ $staff->full_name }}</h4>
        <span class="badge bg-light text-primary border me-2">{{ $staff->employee_id }}</span>
        <span class="badge bg-secondary-subtle text-secondary me-2">{{ $staff->designation }}</span>
        <span class="badge bg-{{ $staff->status === 'active' ? 'success' : 'secondary' }}">{{ ucfirst($staff->status) }}</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('staff.edit', $staff) }}" class="btn btn-outline-secondary btn-sm"><i class="bi bi-pencil me-1"></i> Edit Profile</a>
        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#uploadDocModal"><i class="bi bi-upload me-1"></i> Upload Document</button>
    </div>
</div>

<div class="row g-4">
    <!-- Left Column: Details Overview -->
    <div class="col-lg-4">
        <!-- Personal Info Card -->
        <div class="card card-custom p-3 mb-4">
            <h6 class="fw-bold mb-3 border-bottom pb-2 text-primary"><i class="bi bi-person-lines-fill me-1"></i> Personal Profile</h6>
            <div class="mb-2 small">
                <span class="text-muted d-block">Department:</span>
                <span class="fw-semibold">{{ $staff->department->name ?? 'N/A' }} ({{ $staff->branch->name ?? 'Main Plant' }})</span>
            </div>
            <div class="mb-2 small">
                <span class="text-muted d-block">Phone & Email:</span>
                <span class="fw-semibold">{{ $staff->phone ?? 'N/A' }} &middot; {{ $staff->email ?? 'N/A' }}</span>
            </div>
            <div class="mb-2 small">
                <span class="text-muted d-block">Gender & DOB:</span>
                <span class="fw-semibold">{{ $staff->gender }} &middot; {{ $staff->date_of_birth ? $staff->date_of_birth->format('d M Y') : 'N/A' }}</span>
            </div>
            <div class="mb-2 small">
                <span class="text-muted d-block">Blood Group & Education:</span>
                <span class="fw-semibold">{{ $staff->blood_group ?? 'N/A' }} &middot; {{ $staff->highest_education ?? 'N/A' }}</span>
            </div>
            <div class="mb-2 small">
                <span class="text-muted d-block">Aadhaar (Masked):</span>
                <span class="fw-semibold font-monospace">XXXX-XXXX-{{ substr(str_replace(' ', '', $staff->aadhaar_number), -4) ?: 'XXXX' }}</span>
            </div>
            <div class="mb-2 small">
                <span class="text-muted d-block">PAN Number (Masked):</span>
                <span class="fw-semibold font-monospace">XXXXX{{ substr($staff->pan_number, -4) ?: 'XXXX' }}</span>
            </div>
            <div class="small">
                <span class="text-muted d-block">Permanent Address:</span>
                <span>{{ $staff->permanent_address ?? 'N/A' }}</span>
            </div>
        </div>

        <!-- Bank & Emergency -->
        <div class="card card-custom p-3 mb-4">
            <h6 class="fw-bold mb-3 border-bottom pb-2 text-primary"><i class="bi bi-bank me-1"></i> Bank & Emergency</h6>
            <div class="mb-2 small">
                <span class="text-muted d-block">Bank & Branch:</span>
                <span class="fw-semibold">{{ $staff->bank_name ?? 'N/A' }} ({{ $staff->bank_branch ?? 'N/A' }})</span>
            </div>
            <div class="mb-2 small">
                <span class="text-muted d-block">A/C Number (Masked):</span>
                <span class="fw-semibold font-monospace">XXXXXX{{ substr($staff->bank_account_number, -4) ?: 'XXXX' }}</span>
            </div>
            <div class="mb-2 small">
                <span class="text-muted d-block">IFSC Code:</span>
                <span class="fw-semibold">{{ $staff->bank_ifsc ?? 'N/A' }}</span>
            </div>
            <hr class="my-2">
            <div class="small">
                <span class="text-muted d-block">Emergency Contact:</span>
                <span class="fw-semibold">{{ $staff->emergency_contact_person ?? 'N/A' }} ({{ $staff->emergency_contact_relation ?? 'N/A' }})</span>
                <div>{{ $staff->emergency_contact_number ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <!-- Right Column: Tabs (Salary, Attendance, Documents) -->
    <div class="col-lg-8">
        <div class="card card-custom">
            <div class="card-header bg-white border-bottom p-0">
                <ul class="nav nav-tabs card-header-tabs m-0 px-3">
                    <li class="nav-item">
                        <button class="nav-link active py-3 fw-semibold" data-bs-toggle="tab" data-bs-target="#salaryTab">Compensation & Payslips</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-3 fw-semibold" data-bs-toggle="tab" data-bs-target="#attendanceTab">Recent Attendance</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link py-3 fw-semibold" data-bs-toggle="tab" data-bs-target="#docsTab">Staff Documents</button>
                    </li>
                </ul>
            </div>
            <div class="card-body p-4 tab-content">
                <!-- Salary Tab -->
                <div class="tab-pane fade show active" id="salaryTab">
                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <div class="border rounded p-2 text-center bg-light">
                                <small class="text-muted d-block">Basic Salary</small>
                                <span class="fw-bold text-dark fs-6">₹{{ number_format($staff->basic_salary, 2) }}</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-2 text-center bg-light">
                                <small class="text-muted d-block">HRA</small>
                                <span class="fw-bold text-dark fs-6">₹{{ number_format($staff->hra, 2) }}</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-2 text-center bg-light">
                                <small class="text-muted d-block">Allowances</small>
                                <span class="fw-bold text-dark fs-6">₹{{ number_format($staff->other_allowances, 2) }}</span>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="border rounded p-2 text-center bg-primary-subtle">
                                <small class="text-primary d-block fw-semibold">Gross Salary</small>
                                <span class="fw-bold text-primary fs-6">₹{{ number_format($staff->salary_amount, 2) }}</span>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-2">Payslip History</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Salary Month</th>
                                    <th class="text-end">Gross</th>
                                    <th class="text-end">Deductions</th>
                                    <th class="text-end">Net Pay</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-end">Payslip</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @forelse($staff->payrolls as $pay)
                                <tr>
                                    <td class="fw-semibold">{{ \Carbon\Carbon::parse($pay->salary_month . '-01')->format('F Y') }}</td>
                                    <td class="text-end">₹{{ number_format($pay->gross_salary, 2) }}</td>
                                    <td class="text-end text-danger">₹{{ number_format($pay->total_deductions, 2) }}</td>
                                    <td class="text-end fw-bold text-success">₹{{ number_format($pay->net_salary, 2) }}</td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $pay->payroll_status === 'paid' ? 'success' : 'warning text-dark' }}">
                                            {{ ucfirst($pay->payroll_status) }}
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <a href="{{ route('staff.payroll.payslip', $pay) }}" class="btn btn-sm btn-outline-primary py-0 px-2" target="_blank">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                    </td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center py-3 text-muted">No payslips generated yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Attendance Tab -->
                <div class="tab-pane fade" id="attendanceTab">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead class="table-light small">
                                <tr>
                                    <th>Date</th>
                                    <th>Check In</th>
                                    <th>Check Out</th>
                                    <th>Status</th>
                                    <th class="text-end">Hours Worked</th>
                                </tr>
                            </thead>
                            <tbody class="small">
                                @forelse($staff->attendances as $att)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($att->attendance_date)->format('d M Y (D)') }}</td>
                                    <td>{{ $att->check_in_time ? \Carbon\Carbon::parse($att->check_in_time)->format('h:i A') : '--' }}</td>
                                    <td>{{ $att->check_out_time ? \Carbon\Carbon::parse($att->check_out_time)->format('h:i A') : '--' }}</td>
                                    <td>
                                        <span class="badge bg-{{ $att->attendance_status === 'Present' ? 'success' : ($att->attendance_status === 'Absent' ? 'danger' : 'warning text-dark') }}">
                                            {{ $att->attendance_status }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-semibold">{{ $att->working_hours }} hrs</td>
                                </tr>
                                @empty
                                <tr><td colspan="5" class="text-center py-3 text-muted">No attendance logs found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Documents Tab -->
                <div class="tab-pane fade" id="docsTab">
                    <ul class="list-group list-group-flush mb-0">
                        @forelse($staff->documents as $doc)
                        <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <div>
                                <i class="bi bi-file-earmark-pdf text-danger fs-5 me-2"></i>
                                <span class="fw-semibold">{{ $doc->title }}</span>
                                <small class="text-muted d-block">Type: {{ $doc->document_type }} &middot; Uploaded: {{ $doc->created_at->format('d M Y') }}</small>
                            </div>
                            <span class="badge bg-light text-muted border">Protected Storage</span>
                        </li>
                        @empty
                        <li class="list-group-item text-center py-4 text-muted">No certificates or identity documents uploaded yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Upload Document Modal -->
<div class="modal fade" id="uploadDocModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('staff.documents.upload', $staff) }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Upload Staff Document</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Document Title <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" required placeholder="e.g. Police Verification Certificate">
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Document Category <span class="text-danger">*</span></label>
                    <select name="document_type" class="form-select" required>
                        <option value="Salary Certificate">Salary Certificate</option>
                        <option value="Police Clearance">Police Clearance Certificate</option>
                        <option value="Educational Certificate">Educational Degree / Diploma</option>
                        <option value="Driving License Copy">Driving License Copy</option>
                        <option value="ID Proof">Government ID Proof</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">File (PDF, JPG, PNG, Max 5MB) <span class="text-danger">*</span></label>
                    <input type="file" name="document_file" class="form-control" required accept=".pdf,.jpg,.jpeg,.png">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Upload Document</button>
            </div>
        </form>
    </div>
</div>
@endsection

