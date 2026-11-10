@extends('layouts.app')

@section('title', 'Daily Attendance Sheet')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Daily Staff Attendance & Overtime</h4>
        <p class="text-muted small mb-0">Record and mark staff daily attendance status, in/out timings, and overtime hours.</p>
    </div>
    <div>
        <form method="GET" class="d-flex align-items-center gap-2">
            <label class="small fw-semibold text-muted text-nowrap">Select Date:</label>
            <input type="date" name="date" class="form-control form-control-sm" value="{{ $date }}" onchange="this.form.submit()">
        </form>
    </div>
</div>

<div class="card card-custom">
    <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
        <h6 class="fw-bold mb-0"><i class="bi bi-calendar-check text-primary me-2"></i> Attendance for {{ \Carbon\Carbon::parse($date)->format('l, d F Y') }}</h6>
        <span class="badge bg-light text-dark border">{{ $staffMembers->count() }} Active Staff Members</span>
    </div>
    <div class="card-body p-0">
        <form action="{{ route('staff.attendance.bulk') }}" method="POST">
            @csrf
            <input type="hidden" name="attendance_date" value="{{ $date }}">

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light small">
                        <tr>
                            <th class="ps-4">Employee</th>
                            <th>Department</th>
                            <th>Attendance Status</th>
                            <th>Check-In</th>
                            <th>Check-Out</th>
                            <th style="width: 120px;">Work Hours</th>
                            <th style="width: 120px;">Overtime (Hrs)</th>
                            <th>Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($staffMembers as $staff)
                        @php
                            $att = $attendances->firstWhere('staff_id', $staff->id);
                            $currentStatus = $att ? $att->attendance_status : 'Present';
                            $checkIn = $att && $att->check_in_time ? \Carbon\Carbon::parse($att->check_in_time)->format('H:i') : '09:00';
                            $checkOut = $att && $att->check_out_time ? \Carbon\Carbon::parse($att->check_out_time)->format('H:i') : '18:00';
                            $workHrs = $att ? $att->working_hours : 8.0;
                            $otHrs = $att ? $att->overtime_hours : 0.0;
                        @endphp
                        <tr>
                            <td class="ps-4">
                                <div class="fw-bold">{{ $staff->full_name }}</div>
                                <span class="badge bg-light text-primary border" style="font-size: 0.7rem;">{{ $staff->employee_id }}</span>
                            </td>
                            <td class="small text-muted">{{ $staff->department->name ?? 'N/A' }}</td>
                            <td>
                                <select name="attendance[{{ $staff->id }}][status]" class="form-select form-select-sm" style="width: 130px;">
                                    <option value="Present" {{ $currentStatus === 'Present' ? 'selected' : '' }}>Present</option>
                                    <option value="Absent" {{ $currentStatus === 'Absent' ? 'selected' : '' }}>Absent</option>
                                    <option value="Half Day" {{ $currentStatus === 'Half Day' ? 'selected' : '' }}>Half Day</option>
                                    <option value="On Leave" {{ $currentStatus === 'On Leave' ? 'selected' : '' }}>On Leave</option>
                                    <option value="Holiday" {{ $currentStatus === 'Holiday' ? 'selected' : '' }}>Holiday</option>
                                </select>
                            </td>
                            <td>
                                <input type="time" name="attendance[{{ $staff->id }}][check_in]" class="form-control form-control-sm" value="{{ $checkIn }}" style="width: 110px;">
                            </td>
                            <td>
                                <input type="time" name="attendance[{{ $staff->id }}][check_out]" class="form-control form-control-sm" value="{{ $checkOut }}" style="width: 110px;">
                            </td>
                            <td>
                                <input type="number" step="0.5" name="attendance[{{ $staff->id }}][working_hours]" class="form-control form-control-sm" value="{{ $workHrs }}">
                            </td>
                            <td>
                                <input type="number" step="0.5" name="attendance[{{ $staff->id }}][overtime_hours]" class="form-control form-control-sm" value="{{ $otHrs }}">
                            </td>
                            <td>
                                <input type="text" name="attendance[{{ $staff->id }}][remarks]" class="form-control form-control-sm" value="{{ $att->remarks ?? '' }}" placeholder="Remarks">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card-footer bg-white border-top p-3 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary px-4 fw-semibold">
                    <i class="bi bi-save me-1"></i> Save Attendance Sheet
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

