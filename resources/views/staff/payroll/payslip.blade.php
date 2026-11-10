@extends('layouts.app')

@section('title', 'Payslip - ' . $payroll->staff->full_name . ' (' . $payroll->salary_month . ')')

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <div class="d-flex justify-content-between align-items-center mb-3 d-print-none">
            <a href="{{ route('staff.payroll.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Payroll
            </a>
            <div>
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer me-1"></i> Print Payslip
                </button>
            </div>
        </div>

        <div class="card shadow-sm border p-4 bg-white" id="payslip-print-area">
            <!-- Header -->
            <div class="row border-bottom pb-3 mb-3">
                <div class="col-8">
                    <h3 class="fw-bold mb-1 text-primary">{{ $payroll->company->name ?? 'Company ERP' }}</h3>
                    <p class="text-muted small mb-0">{{ $payroll->company->address ?? 'Registered Office' }}</p>
                    <p class="text-muted small mb-0">GSTIN: {{ $payroll->company->gst_no ?? 'N/A' }} | PAN: {{ $payroll->company->pan_no ?? 'N/A' }}</p>
                </div>
                <div class="col-4 text-end">
                    <span class="badge bg-light text-dark border px-3 py-2 fs-6">CONFIDENTIAL PAYSLIP</span>
                    <h5 class="fw-bold mt-2 mb-0">{{ \Carbon\Carbon::parse($payroll->salary_month . '-01')->format('F Y') }}</h5>
                    <small class="text-muted">Status: <span class="badge bg-{{ $payroll->payroll_status === 'paid' ? 'success' : 'warning' }}">{{ strtoupper($payroll->payroll_status) }}</span></small>
                </div>
            </div>

            <!-- Employee Details Grid -->
            <div class="card bg-light border-0 mb-4">
                <div class="card-body p-3">
                    <div class="row g-2 small">
                        <div class="col-md-3"><strong>Employee Name:</strong> {{ $payroll->staff->full_name }}</div>
                        <div class="col-md-3"><strong>Employee ID:</strong> <span class="badge bg-dark">{{ $payroll->staff->employee_id }}</span></div>
                        <div class="col-md-3"><strong>Department:</strong> {{ $payroll->staff->department->name ?? 'N/A' }}</div>
                        <div class="col-md-3"><strong>Designation:</strong> {{ $payroll->staff->designation ?? 'Staff' }}</div>

                        <div class="col-md-3"><strong>Date of Joining:</strong> {{ $payroll->staff->date_of_joining ? \Carbon\Carbon::parse($payroll->staff->date_of_joining)->format('d M Y') : 'N/A' }}</div>
                        <div class="col-md-3"><strong>Bank Name:</strong> {{ $payroll->staff->bank_name ?? 'N/A' }}</div>
                        <div class="col-md-3"><strong>Bank A/C No:</strong> {{ $payroll->staff->bank_account_number ? '••••' . substr($payroll->staff->bank_account_number, -4) : 'N/A' }}</div>
                        <div class="col-md-3"><strong>IFSC Code:</strong> {{ $payroll->staff->bank_ifsc ?? 'N/A' }}</div>

                        <div class="col-md-3"><strong>PAN:</strong> {{ $payroll->staff->pan_number ? '••••' . substr($payroll->staff->pan_number, -4) : 'N/A' }}</div>
                        <div class="col-md-3"><strong>PF Number:</strong> {{ $payroll->staff->pf_number ?? 'N/A' }}</div>
                        <div class="col-md-3"><strong>ESI Number:</strong> {{ $payroll->staff->esi_number ?? 'N/A' }}</div>
                        <div class="col-md-3"><strong>Payment Mode:</strong> {{ $payroll->payment_mode ?? 'Bank Transfer' }}</div>
                    </div>
                </div>
            </div>

            <!-- Earnings and Deductions Table -->
            <div class="row g-4 mb-4">
                <div class="col-md-6">
                    <div class="card h-100 border">
                        <div class="card-header bg-success bg-opacity-10 py-2">
                            <h6 class="mb-0 text-success fw-bold"><i class="bi bi-plus-circle me-1"></i> Earnings (INR)</h6>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0">
                                <tbody>
                                    <tr>
                                        <td>Basic Salary</td>
                                        <td class="text-end fw-semibold">₹ {{ number_format($payroll->basic_salary, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>House Rent Allowance (HRA)</td>
                                        <td class="text-end fw-semibold">₹ {{ number_format($payroll->hra, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Other Allowances</td>
                                        <td class="text-end fw-semibold">₹ {{ number_format($payroll->other_allowances, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Overtime Pay</td>
                                        <td class="text-end fw-semibold">₹ {{ number_format($payroll->overtime_amount, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Incentives / Bonus</td>
                                        <td class="text-end fw-semibold">₹ {{ number_format($payroll->incentives, 2) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot class="border-top fw-bold bg-light">
                                    <tr>
                                        <td>Total Gross Earnings</td>
                                        <td class="text-end text-success">₹ {{ number_format($payroll->gross_salary, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card h-100 border">
                        <div class="card-header bg-danger bg-opacity-10 py-2">
                            <h6 class="mb-0 text-danger fw-bold"><i class="bi bi-dash-circle me-1"></i> Deductions (INR)</h6>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-sm mb-0">
                                <tbody>
                                    <tr>
                                        <td>Provident Fund (PF)</td>
                                        <td class="text-end fw-semibold">₹ {{ number_format($payroll->pf_deduction, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Employee State Insurance (ESI)</td>
                                        <td class="text-end fw-semibold">₹ {{ number_format($payroll->esi_deduction, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td>Other Deductions / TDS</td>
                                        <td class="text-end fw-semibold">₹ {{ number_format($payroll->other_deductions, 2) }}</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">&nbsp;</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                    <tr>
                                        <td class="text-muted">&nbsp;</td>
                                        <td>&nbsp;</td>
                                    </tr>
                                </tbody>
                                <tfoot class="border-top fw-bold bg-light">
                                    <tr>
                                        <td>Total Deductions</td>
                                        <td class="text-end text-danger">₹ {{ number_format($payroll->total_deductions, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Net Pay Box -->
            <div class="card bg-primary bg-opacity-10 border border-primary border-opacity-25 mb-4">
                <div class="card-body p-3 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted text-uppercase small fw-bold">Net Salary Payable</span>
                        <h3 class="fw-bold text-primary mb-0">₹ {{ number_format($payroll->net_salary, 2) }}</h3>
                    </div>
                    <div class="text-end">
                        <small class="text-muted">Payment Date: {{ $payroll->payment_date ? \Carbon\Carbon::parse($payroll->payment_date)->format('d M Y') : 'Pending Disbursement' }}</small>
                        <br>
                        <small class="text-muted">Voucher Ref: {{ $payroll->journalEntry->voucher_no ?? 'N/A' }}</small>
                    </div>
                </div>
            </div>

            <!-- Signatures -->
            <div class="row mt-5 pt-4 text-center">
                <div class="col-4">
                    <div class="border-top pt-2">
                        <small class="text-muted fw-bold">Employee Signature</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border-top pt-2">
                        <small class="text-muted fw-bold">Prepared By (HR Dept)</small>
                    </div>
                </div>
                <div class="col-4">
                    <div class="border-top pt-2">
                        <small class="text-muted fw-bold">Authorised Signatory / Director</small>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4 text-muted small border-top pt-2">
                This is a computer-generated payslip generated via Common ERP on {{ now()->format('d M Y H:i:s') }}.
            </div>
        </div>
    </div>
</div>
@endsection

