@extends('layouts.app')

@section('title', 'Company Holidays')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="fw-bold mb-1">Holiday Calendar</h4>
        <p class="text-muted small mb-0">National holidays, company festivals, and gazetted non-working days.</p>
    </div>
    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newHolidayModal">
        <i class="bi bi-plus-lg me-1"></i> Add Holiday
    </button>
</div>

<div class="card card-custom">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-4">Holiday Name</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($holidays as $holiday)
                    <tr>
                        <td class="ps-4 fw-bold text-dark">{{ $holiday->holiday_name }}</td>
                        <td>{{ $holiday->holiday_date ? $holiday->holiday_date->format('d M Y (l)') : 'N/A' }}</td>
                        <td>
                            <span class="badge bg-light text-primary border">{{ $holiday->holiday_type }}</span>
                        </td>
                        <td class="small text-muted">{{ $holiday->description ?? 'N/A' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Holiday Modal -->
<div class="modal fade" id="newHolidayModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('staff.holidays.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Add Holiday</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Holiday Name <span class="text-danger">*</span></label>
                    <input type="text" name="holiday_name" class="form-control" required placeholder="e.g. Gandhi Jayanti">
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Date <span class="text-danger">*</span></label>
                        <input type="date" name="holiday_date" class="form-control" required>
                    </div>
                    <div class="col-6">
                        <label class="form-label small fw-semibold">Type <span class="text-danger">*</span></label>
                        <select name="holiday_type" class="form-select" required>
                            <option value="National">National Holiday</option>
                            <option value="Festival">Festival Holiday</option>
                            <option value="Company">Company Holiday</option>
                            <option value="Weekly Off">Weekly Off</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Description</label>
                    <textarea name="description" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Holiday</button>
            </div>
        </form>
    </div>
</div>
@endsection

