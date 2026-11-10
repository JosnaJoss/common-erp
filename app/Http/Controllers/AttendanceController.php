<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\Overtime;
use App\Models\Shift;
use App\Models\Staff;
use App\Services\CompanyContextService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $date = $request->get('date', Carbon::today()->toDateString());

        $attendances = Attendance::where('company_id', $companyId)
            ->where('attendance_date', $date)
            ->with(['staff.department'])
            ->get();

        $staffMembers = Staff::where('company_id', $companyId)->where('status', 'active')->get();

        return view('staff.attendance.index', compact('attendances', 'staffMembers', 'date'));
    }

    public function markBulk(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $date = $request->validate(['attendance_date' => 'required|date'])['attendance_date'];
        $records = $request->input('attendance', []);

        foreach ($records as $staffId => $data) {
            $staff = Staff::find($staffId);
            if (!$staff) continue;

            $status = $data['status'] ?? 'Present';
            $checkIn = !empty($data['check_in']) ? $data['check_in'] : '09:00:00';
            $checkOut = !empty($data['check_out']) ? $data['check_out'] : '18:00:00';
            $workingHours = (float)($data['working_hours'] ?? 8.0);
            $overtimeHours = (float)($data['overtime_hours'] ?? 0.0);

            Attendance::updateOrCreate(
                [
                    'company_id' => $companyId,
                    'staff_id' => $staffId,
                    'attendance_date' => $date,
                ],
                [
                    'branch_id' => $staff->branch_id,
                    'department_id' => $staff->department_id,
                    'attendance_status' => $status,
                    'check_in_time' => $checkIn,
                    'check_out_time' => $checkOut,
                    'working_hours' => $workingHours,
                    'overtime_hours' => $overtimeHours,
                    'remarks' => $data['remarks'] ?? null,
                ]
            );
        }

        AuditLog::log('attendance', 'mark_bulk', "Marked attendance for date {$date}");
        return back()->with('success', 'Attendance recorded successfully.');
    }

    public function bulkStore(Request $request)
    {
        return $this->markBulk($request);
    }

    public function store(Request $request)
    {
        return $this->markBulk($request);
    }

    // Shifts
    public function shifts()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $shifts = Shift::where('company_id', $companyId)->paginate(15);
        return view('staff.shifts.index', compact('shifts'));
    }

    public function shiftStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'shift_name' => 'required|string|max:100',
            'start_time' => 'required',
            'end_time' => 'required',
            'break_duration_minutes' => 'required|integer|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $data['company_id'] = $companyId;
        Shift::create($data);

        AuditLog::log('shift', 'create', "Created shift {$data['shift_name']}");
        return back()->with('success', 'Shift created successfully.');
    }

    // Holidays
    public function holidays()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $holidays = Holiday::where('company_id', $companyId)->orderBy('holiday_date')->paginate(15);
        return view('staff.holidays.index', compact('holidays'));
    }

    public function holidayStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'holiday_name' => 'required|string|max:255',
            'holiday_date' => 'required|date',
            'holiday_type' => 'required|in:National,Festival,Company,Weekly Off',
            'description' => 'nullable|string',
        ]);

        $data['company_id'] = $companyId;
        Holiday::create($data);

        AuditLog::log('holiday', 'create', "Created holiday {$data['holiday_name']}");
        return back()->with('success', 'Holiday created successfully.');
    }

    // Overtime
    public function overtime()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $overtimes = Overtime::where('company_id', $companyId)->with('staff')->latest('date')->paginate(15);
        $staffMembers = Staff::where('company_id', $companyId)->where('status', 'active')->get();
        return view('staff.overtime.index', compact('overtimes', 'staffMembers'));
    }

    public function overtimeStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'staff_id' => 'required|exists:staff,id',
            'date' => 'required|date',
            'overtime_hours' => 'required|numeric|min:0.5',
            'overtime_rate' => 'required|numeric|min:0',
        ]);

        $data['company_id'] = $companyId;
        $data['total_amount'] = $data['overtime_hours'] * $data['overtime_rate'];
        $data['approval_status'] = 'approved';
        $data['approved_by'] = auth()->id();

        Overtime::create($data);
        AuditLog::log('overtime', 'create', "Recorded overtime for staff #{$data['staff_id']}");
        return back()->with('success', 'Overtime recorded and approved.');
    }

    public function overtimeApprove(Overtime $overtime)
    {
        $overtime->update([
            'approval_status' => 'approved',
            'approved_by' => auth()->id(),
        ]);

        AuditLog::log('overtime', 'approve', "Approved overtime for staff #{$overtime->staff_id}");
        return back()->with('success', 'Overtime approved successfully.');
    }
}

