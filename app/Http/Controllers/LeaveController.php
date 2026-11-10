<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LeaveApplication;
use App\Models\Staff;
use App\Services\CompanyContextService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $leaves = LeaveApplication::where('company_id', $companyId)->with(['staff', 'approver'])->latest()->paginate(15);
        $staffMembers = Staff::where('company_id', $companyId)->where('status', 'active')->get();

        return view('staff.leaves.index', compact('leaves', 'staffMembers'));
    }

    public function store(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'leave_type' => 'required|in:Casual,Sick,Earned,Maternity,Unpaid',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'reason' => 'required|string',
        ]);

        $start = Carbon::parse($data['start_date']);
        $end = Carbon::parse($data['end_date']);
        $days = $start->diffInDays($end) + 1;

        $data['company_id'] = $companyId;
        $data['number_of_days'] = $days;
        $data['status'] = 'pending';

        LeaveApplication::create($data);
        AuditLog::log('leave', 'apply', "Submitted leave application for staff #{$data['staff_id']}");

        return back()->with('success', 'Leave application submitted successfully.');
    }

    public function updateStatus(Request $request, LeaveApplication $leave)
    {
        $data = $request->validate([
            'status' => 'required|in:approved,rejected',
            'approval_remarks' => 'nullable|string',
        ]);

        $leave->update([
            'status' => $data['status'],
            'approved_by' => auth()->id(),
            'approval_remarks' => $data['approval_remarks'] ?? null,
        ]);

        AuditLog::log('leave', 'status_update', "Updated leave status to {$data['status']} for leave #{$leave->id}");
        return back()->with('success', "Leave application {$data['status']}.");
    }
}

