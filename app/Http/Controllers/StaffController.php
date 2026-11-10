<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Overtime;
use App\Models\Staff;
use App\Models\StaffDocument;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $query = Staff::where('company_id', $companyId)->with(['department', 'branch']);

        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name', 'like', "%{$s}%")
                  ->orWhere('employee_id', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('designation', 'like', "%{$s}%");
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->get('department_id'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->get('status'));
        }

        $staffMembers = $query->latest()->paginate(15);
        $departments = Department::where('company_id', $companyId)->where('status', 'active')->get();

        return view('staff.index', compact('staffMembers', 'departments'));
    }

    public function create()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $departments = Department::where('company_id', $companyId)->where('status', 'active')->get();
        $branches = Branch::where('company_id', $companyId)->where('status', 'active')->get();
        return view('staff.create', compact('departments', 'branches'));
    }

    public function store(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'employee_id' => 'required|string|max:50',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:50',
            'aadhaar_number' => 'nullable|string|max:50',
            'pan_number' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date',
            'gender' => 'required|in:Male,Female,Other',
            'blood_group' => 'nullable|string|max:10',
            'highest_education' => 'nullable|string|max:100',
            'marital_status' => 'required|in:Single,Married,Divorced,Widowed',
            'permanent_address' => 'nullable|string',
            'temporary_address' => 'nullable|string',
            'emergency_contact_person' => 'nullable|string|max:100',
            'emergency_contact_relation' => 'nullable|string|max:50',
            'emergency_contact_number' => 'nullable|string|max:50',
            'designation' => 'required|string|max:100',
            'employee_type' => 'required|in:salaried,daily_wage',
            'salary_amount' => 'required|numeric|min:0',
            'date_of_joining' => 'required|date',
            'status' => 'required|in:active,probation,resigned,terminated',
            'basic_salary' => 'required|numeric|min:0',
            'hra' => 'nullable|numeric|min:0',
            'other_allowances' => 'nullable|numeric|min:0',
            'pf_applicable' => 'nullable|boolean',
            'pf_number' => 'nullable|string|max:50',
            'pf_uan' => 'nullable|string|max:50',
            'pf_rate_percent' => 'nullable|numeric|min:0|max:100',
            'esi_applicable' => 'nullable|boolean',
            'esi_number' => 'nullable|string|max:50',
            'esi_rate_percent' => 'nullable|numeric|min:0|max:100',
            'license_number' => 'nullable|string|max:50',
            'license_class' => 'nullable|string|max:50',
            'license_start_date' => 'nullable|date',
            'license_expiry_date' => 'nullable|date',
            'bank_name' => 'nullable|string|max:100',
            'bank_ifsc' => 'nullable|string|max:30',
            'bank_branch' => 'nullable|string|max:100',
            'bank_account_type' => 'nullable|in:Savings,Current,Salary',
            'bank_holder_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
        ]);

        $data['company_id'] = $companyId;
        $data['pf_applicable'] = $request->boolean('pf_applicable');
        $data['esi_applicable'] = $request->boolean('esi_applicable');
        $data['hra'] = $data['hra'] ?? 0;
        $data['other_allowances'] = $data['other_allowances'] ?? 0;

        $staff = Staff::create($data);

        AuditLog::log('staff', 'create', "Created staff member {$staff->full_name} ({$staff->employee_id})");
        return redirect()->route('staff.index')->with('success', 'Staff member created successfully.');
    }

    public function show(Staff $staff)
    {
        $staff->load(['department', 'branch', 'attendances' => function ($q) {
            $q->latest('attendance_date')->take(10);
        }, 'payrolls' => function ($q) {
            $q->latest('salary_month')->take(6);
        }, 'leaves', 'documents']);

        return view('staff.show', compact('staff'));
    }

    public function edit(Staff $staff)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $departments = Department::where('company_id', $companyId)->where('status', 'active')->get();
        $branches = Branch::where('company_id', $companyId)->where('status', 'active')->get();
        return view('staff.edit', compact('staff', 'departments', 'branches'));
    }

    public function update(Request $request, Staff $staff)
    {
        $data = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'department_id' => 'nullable|exists:departments,id',
            'employee_id' => 'required|string|max:50',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:100',
            'phone' => 'nullable|string|max:50',
            'aadhaar_number' => 'nullable|string|max:50',
            'pan_number' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date',
            'gender' => 'required|in:Male,Female,Other',
            'blood_group' => 'nullable|string|max:10',
            'highest_education' => 'nullable|string|max:100',
            'marital_status' => 'required|in:Single,Married,Divorced,Widowed',
            'permanent_address' => 'nullable|string',
            'temporary_address' => 'nullable|string',
            'emergency_contact_person' => 'nullable|string|max:100',
            'emergency_contact_relation' => 'nullable|string|max:50',
            'emergency_contact_number' => 'nullable|string|max:50',
            'designation' => 'required|string|max:100',
            'employee_type' => 'required|in:salaried,daily_wage',
            'salary_amount' => 'required|numeric|min:0',
            'date_of_joining' => 'required|date',
            'last_working_date' => 'nullable|date',
            'status' => 'required|in:active,probation,resigned,terminated',
            'basic_salary' => 'required|numeric|min:0',
            'hra' => 'nullable|numeric|min:0',
            'other_allowances' => 'nullable|numeric|min:0',
            'pf_applicable' => 'nullable|boolean',
            'pf_number' => 'nullable|string|max:50',
            'pf_uan' => 'nullable|string|max:50',
            'pf_rate_percent' => 'nullable|numeric|min:0|max:100',
            'esi_applicable' => 'nullable|boolean',
            'esi_number' => 'nullable|string|max:50',
            'esi_rate_percent' => 'nullable|numeric|min:0|max:100',
            'license_number' => 'nullable|string|max:50',
            'license_class' => 'nullable|string|max:50',
            'license_start_date' => 'nullable|date',
            'license_expiry_date' => 'nullable|date',
            'bank_name' => 'nullable|string|max:100',
            'bank_ifsc' => 'nullable|string|max:30',
            'bank_branch' => 'nullable|string|max:100',
            'bank_account_type' => 'nullable|in:Savings,Current,Salary',
            'bank_holder_name' => 'nullable|string|max:100',
            'bank_account_number' => 'nullable|string|max:50',
        ]);

        $data['pf_applicable'] = $request->boolean('pf_applicable');
        $data['esi_applicable'] = $request->boolean('esi_applicable');

        $staff->update($data);
        AuditLog::log('staff', 'update', "Updated staff details for {$staff->full_name}");

        return redirect()->route('staff.index')->with('success', 'Staff details updated successfully.');
    }

    public function destroy(Staff $staff)
    {
        $staff->delete();
        AuditLog::log('staff', 'delete', "Deleted staff member {$staff->full_name}");
        return redirect()->route('staff.index')->with('success', 'Staff member deleted successfully.');
    }

    public function uploadDocument(Request $request, Staff $staff)
    {
        $request->validate([
            'document_type' => 'required|string|max:100',
            'title' => 'required|string|max:255',
            'document_file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $path = $request->file('document_file')->store('staff_documents/' . $staff->id, 'local');

        StaffDocument::create([
            'staff_id' => $staff->id,
            'document_type' => $request->document_type,
            'title' => $request->title,
            'file_path' => $path,
        ]);

        AuditLog::log('staff', 'upload_document', "Uploaded document '{$request->title}' for {$staff->full_name}");
        return back()->with('success', 'Document uploaded securely.');
    }

    // Shifts, Holidays & Overtime delegates
    public function shifts(Request $request)
    {
        return app(AttendanceController::class)->shifts($request);
    }

    public function shiftStore(Request $request)
    {
        return app(AttendanceController::class)->shiftStore($request);
    }

    public function holidays(Request $request)
    {
        return app(AttendanceController::class)->holidays($request);
    }

    public function holidayStore(Request $request)
    {
        return app(AttendanceController::class)->holidayStore($request);
    }

    public function overtime(Request $request)
    {
        return app(AttendanceController::class)->overtime($request);
    }

    public function overtimeStore(Request $request)
    {
        return app(AttendanceController::class)->overtimeStore($request);
    }

    public function overtimeApprove(Overtime $overtime)
    {
        return app(AttendanceController::class)->overtimeApprove($overtime);
    }
}

