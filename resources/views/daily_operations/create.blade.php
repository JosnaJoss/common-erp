@extends('layouts.app')

@section('title', 'Record Daily Operation / Work Report')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="mb-0 fw-bold"><i class="bi bi-pencil-square text-primary me-2"></i>Record Daily Operation</h4>
        <small class="text-muted">Enter activity details, participating employees, raw material consumption, outputs, wastage and distribution</small>
    </div>
    <a href="{{ route('daily_operations.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Back to Operations
    </a>
</div>

@if($errors->any())
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please resolve the following errors:</div>
    <ul class="mb-0 ps-3">
        @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
@endif

<form method="POST" action="{{ route('daily_operations.store') }}" id="dailyOperationForm">
    @csrf

    <!-- 1. Operational Header Card -->
    <div class="card card-custom mb-3">
        <div class="card-header bg-white py-2 fw-semibold">
            <i class="bi bi-info-circle text-primary me-2"></i>1. Operation Activity & Header Details
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Department <span class="text-danger">*</span></label>
                    <select name="department_id" id="department_id" class="form-select form-select-sm" required>
                        <option value="">Select Department</option>
                        @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                            {{ $dept->name }} ({{ strtoupper($dept->department_type ?? 'general') }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Operation Date <span class="text-danger">*</span></label>
                    <input type="date" name="operation_date" class="form-control form-control-sm" value="{{ old('operation_date', date('Y-m-d')) }}" required>
                </div>

                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Process / Activity Name <span class="text-danger">*</span></label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="process_name" id="process_name" class="form-control form-control-sm" placeholder="e.g. Roasting, Grinding, Cutting, Stitching, Purification, Packing" value="{{ old('process_name') }}" required>
                        @if($templates->isNotEmpty())
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">Templates</button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                            @foreach($templates as $tpl)
                            <li>
                                <a class="dropdown-item small template-select-item" href="javascript:void(0);" data-process="{{ $tpl->process_name }}">
                                    <strong>{{ $tpl->process_name }}</strong> <span class="text-muted">({{ $tpl->department_name }})</span>
                                </a>
                            </li>
                            @endforeach
                        </ul>
                        @endif
                    </div>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Work Order / Job Ref (Optional)</label>
                    <input type="text" name="work_order_ref" class="form-control form-control-sm" placeholder="e.g. WO-2026-088" value="{{ old('work_order_ref') }}">
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Department Head</label>
                    <select name="department_head_id" id="department_head_id" class="form-select form-select-sm">
                        <option value="">Select Head</option>
                        @foreach($staff as $s)
                        <option value="{{ $s->id }}" {{ old('department_head_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->full_name }} ({{ $s->designation ?? 'Staff' }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Supervisor</label>
                    <select name="supervisor_id" id="supervisor_id" class="form-select form-select-sm">
                        <option value="">Select Supervisor</option>
                        @foreach($staff as $s)
                        <option value="{{ $s->id }}" {{ old('supervisor_id') == $s->id ? 'selected' : '' }}>
                            {{ $s->full_name }} ({{ $s->designation ?? 'Supervisor' }})
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Start Time</label>
                    <input type="time" name="start_time" class="form-control form-control-sm" value="{{ old('start_time', '09:00') }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-semibold">End Time</label>
                    <input type="time" name="end_time" class="form-control form-control-sm" value="{{ old('end_time', '17:30') }}">
                </div>

                <div class="col-md-2">
                    <label class="form-label small fw-semibold">Work Status</label>
                    <select name="work_status" class="form-select form-select-sm">
                        <option value="completed" {{ old('work_status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="in_progress" {{ old('work_status') == 'in_progress' ? 'selected' : '' }}>In Progress</option>
                        <option value="paused" {{ old('work_status') == 'paused' ? 'selected' : '' }}>Paused</option>
                        <option value="draft" {{ old('work_status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label small fw-semibold">Work Description / Process Notes</label>
                    <textarea name="work_description" class="form-control form-control-sm" rows="2" placeholder="Describe the operational steps, parameters, quality conditions, or machinery utilized...">{{ old('work_description') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <!-- 2. Employee Participation Section -->
    <div class="card card-custom mb-3">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-people text-primary me-2"></i>2. Employee Participation & Work Allocation</span>
            <button type="button" class="btn btn-sm btn-outline-primary" id="addEmployeeBtn">
                <i class="bi bi-plus-circle me-1"></i> Add Employee
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0" id="employeesTable">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 28%;">Employee / Staff <span class="text-danger">*</span></th>
                            <th style="width: 18%;">Employment Type</th>
                            <th style="width: 14%;">Hours Worked</th>
                            <th style="width: 15%;">Units Completed</th>
                            <th style="width: 15%;">Rate / Unit (₹)</th>
                            <th style="width: 10%;" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="small" id="employeeRowsContainer">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 3. Material / Stock Consumption Section -->
    <div class="card card-custom mb-3">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-box-arrow-right text-danger me-2"></i>3. Material & Stock Consumption</span>
            <button type="button" class="btn btn-sm btn-outline-danger" id="addMaterialBtn">
                <i class="bi bi-plus-circle me-1"></i> Add Consumed Material
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0" id="materialsTable">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 25%;">Material / Item <span class="text-danger">*</span></th>
                            <th style="width: 20%;">Source Warehouse <span class="text-danger">*</span></th>
                            <th style="width: 12%;">Batch No</th>
                            <th style="width: 10%;">Opening Qty</th>
                            <th style="width: 11%;">Consumed Qty <span class="text-danger">*</span></th>
                            <th style="width: 11%;">Returned Qty</th>
                            <th style="width: 11%;">Wastage Qty</th>
                            <th style="width: 5%;" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="small" id="materialRowsContainer">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 4. Production & Output Generated Section -->
    <div class="card card-custom mb-3">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-box-arrow-in-left text-success me-2"></i>4. Production Output / Finished Products</span>
            <button type="button" class="btn btn-sm btn-outline-success" id="addOutputBtn">
                <i class="bi bi-plus-circle me-1"></i> Add Output Product
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0" id="outputsTable">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 28%;">Produced Product / Item <span class="text-danger">*</span></th>
                            <th style="width: 22%;">Destination Warehouse <span class="text-danger">*</span></th>
                            <th style="width: 15%;">Production Batch #</th>
                            <th style="width: 12%;">Produced Qty <span class="text-danger">*</span></th>
                            <th style="width: 10%;">Rejected Qty</th>
                            <th style="width: 13%;">Quality Status</th>
                            <th style="width: 5%;" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="small" id="outputRowsContainer">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 5. Granular Wastage & Process Loss Section -->
    <div class="card card-custom mb-3">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-trash3 text-warning me-2"></i>5. Wastage, Process Loss & Recovery Tracking</span>
            <button type="button" class="btn btn-sm btn-outline-warning" id="addWastageBtn">
                <i class="bi bi-plus-circle me-1"></i> Add Wastage Record
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0" id="wastagesTable">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 25%;">Wastage Material / Description <span class="text-danger">*</span></th>
                            <th style="width: 18%;">Wastage Category</th>
                            <th style="width: 12%;">Quantity <span class="text-danger">*</span></th>
                            <th style="width: 8%;">Unit</th>
                            <th style="width: 12%;">Est. Loss Cost (₹)</th>
                            <th style="width: 12%;">Reusable / Scrap Qty</th>
                            <th style="width: 13%;">Reason / Cause</th>
                            <th style="width: 5%;" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="small" id="wastageRowsContainer">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 6. Packing & Distribution Section -->
    <div class="card card-custom mb-3">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
            <span class="fw-semibold"><i class="bi bi-truck text-info me-2"></i>6. Output Distribution & Transfer (Sales Counter, Vans, Showrooms)</span>
            <button type="button" class="btn btn-sm btn-outline-info" id="addDistributionBtn">
                <i class="bi bi-plus-circle me-1"></i> Add Distribution Destination
            </button>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-sm table-bordered align-middle mb-0" id="distributionsTable">
                    <thead class="table-light small">
                        <tr>
                            <th style="width: 28%;">Distributed Item <span class="text-danger">*</span></th>
                            <th style="width: 25%;">Destination Warehouse / Van <span class="text-danger">*</span></th>
                            <th style="width: 20%;">Destination Type</th>
                            <th style="width: 12%;">Batch No</th>
                            <th style="width: 10%;">Quantity <span class="text-danger">*</span></th>
                            <th style="width: 5%;" class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody class="small" id="distributionRowsContainer">
                        <!-- Populated dynamically via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- 7. Action & Submission Footer -->
    <div class="card card-custom mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <label class="form-label small fw-semibold mb-0">Save Action / Target Approval Level:</label>
                <select name="approval_status" class="form-select form-select-sm" style="width: 250px;">
                    <option value="draft">Save as Draft</option>
                    <option value="submitted">Submit for Approval</option>
                    <option value="supervisor_approved">Save as Supervisor Approved</option>
                    <option value="department_head_approved" selected>Save as Dept Head Approved (Post Stock)</option>
                    <option value="admin_approved">Save as Admin Approved (Post Stock)</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <a href="{{ route('daily_operations.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary px-4">
                    <i class="bi bi-check2-circle me-1"></i> Save Daily Operation Record
                </button>
            </div>
        </div>
    </div>
</form>

<!-- Client JavaScript for Dynamic Rows -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    const staffCatalog = @json($staff);
    const itemsCatalog = @json($items);
    const godownsCatalog = @json($godowns);

    let employeeIdx = 0;
    let materialIdx = 0;
    let outputIdx = 0;
    let wastageIdx = 0;
    let distributionIdx = 0;

    // Helper to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    // Process Template Click Handler
    document.querySelectorAll('.template-select-item').forEach(item => {
        item.addEventListener('click', function () {
            document.getElementById('process_name').value = this.dataset.process;
        });
    });

    // 1. Add Employee Row
    function addEmployeeRow(data = {}) {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <select name="employees[${employeeIdx}][staff_id]" class="form-select form-select-sm emp-select" required>
                    <option value="">Select Employee</option>
                    ${staffCatalog.map(s => `<option value="${s.id}" data-type="${s.employee_type || 'salaried'}" data-piece="${s.piece_rate || 0}" data-hourly="${s.hourly_rate || 0}" ${data.staff_id == s.id ? 'selected' : ''}>${escapeHtml(s.first_name)} ${escapeHtml(s.last_name)} (${s.designation || 'Staff'})</option>`).join('')}
                </select>
            </td>
            <td>
                <select name="employees[${employeeIdx}][employment_type]" class="form-select form-select-sm emp-type">
                    <option value="salaried" ${data.employment_type == 'salaried' ? 'selected' : ''}>Monthly Salaried</option>
                    <option value="daily_wage" ${data.employment_type == 'daily_wage' ? 'selected' : ''}>Daily Wage</option>
                    <option value="hourly" ${data.employment_type == 'hourly' ? 'selected' : ''}>Hourly Wage</option>
                    <option value="piece_rate" ${data.employment_type == 'piece_rate' ? 'selected' : ''}>Piece Rate</option>
                    <option value="contract" ${data.employment_type == 'contract' ? 'selected' : ''}>Contract Worker</option>
                </select>
            </td>
            <td>
                <input type="number" step="0.5" name="employees[${employeeIdx}][hours_worked]" class="form-control form-control-sm" value="${data.hours_worked || 8}" required>
            </td>
            <td>
                <input type="number" step="1" name="employees[${employeeIdx}][units_completed]" class="form-control form-control-sm" value="${data.units_completed || 0}">
            </td>
            <td>
                <input type="number" step="0.5" name="employees[${employeeIdx}][rate_per_unit]" class="form-control form-control-sm emp-rate" value="${data.rate_per_unit || 0}">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="bi bi-trash"></i></button>
            </td>
        `;

        const select = tr.querySelector('.emp-select');
        const typeSelect = tr.querySelector('.emp-type');
        const rateInput = tr.querySelector('.emp-rate');

        select.addEventListener('change', function () {
            const opt = this.options[this.selectedIndex];
            if (opt && opt.dataset.type) {
                typeSelect.value = opt.dataset.type;
                if (opt.dataset.type === 'piece_rate') {
                    rateInput.value = opt.dataset.piece;
                } else if (opt.dataset.type === 'hourly') {
                    rateInput.value = opt.dataset.hourly;
                }
            }
        });

        tr.querySelector('.btn-remove-row').addEventListener('click', function () {
            tr.remove();
        });

        document.getElementById('employeeRowsContainer').appendChild(tr);
        employeeIdx++;
    }

    // 2. Add Material Row
    function addMaterialRow(data = {}) {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <select name="materials[${materialIdx}][item_id]" class="form-select form-select-sm" required>
                    <option value="">Select Material</option>
                    ${itemsCatalog.map(i => `<option value="${i.id}" ${data.item_id == i.id ? 'selected' : ''}>${escapeHtml(i.name)} (${i.code})</option>`).join('')}
                </select>
            </td>
            <td>
                <select name="materials[${materialIdx}][source_godown_id]" class="form-select form-select-sm" required>
                    <option value="">Select Warehouse</option>
                    ${godownsCatalog.map(g => `<option value="${g.id}" ${data.source_godown_id == g.id ? 'selected' : ''}>${escapeHtml(g.name)}</option>`).join('')}
                </select>
            </td>
            <td>
                <input type="text" name="materials[${materialIdx}][batch_no]" class="form-control form-control-sm" value="${data.batch_no || 'DEFAULT'}">
            </td>
            <td>
                <input type="number" step="0.001" name="materials[${materialIdx}][opening_quantity]" class="form-control form-control-sm" value="${data.opening_quantity || 0}">
            </td>
            <td>
                <input type="number" step="0.001" name="materials[${materialIdx}][consumed_quantity]" class="form-control form-control-sm fw-bold text-danger" value="${data.consumed_quantity || ''}" placeholder="0.000" required>
            </td>
            <td>
                <input type="number" step="0.001" name="materials[${materialIdx}][returned_quantity]" class="form-control form-control-sm" value="${data.returned_quantity || 0}">
            </td>
            <td>
                <input type="number" step="0.001" name="materials[${materialIdx}][wastage_quantity]" class="form-control form-control-sm" value="${data.wastage_quantity || 0}">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="bi bi-trash"></i></button>
            </td>
        `;

        tr.querySelector('.btn-remove-row').addEventListener('click', function () {
            tr.remove();
        });

        document.getElementById('materialRowsContainer').appendChild(tr);
        materialIdx++;
    }

    // 3. Add Output Row
    function addOutputRow(data = {}) {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <select name="outputs[${outputIdx}][item_id]" class="form-select form-select-sm" required>
                    <option value="">Select Output Item</option>
                    ${itemsCatalog.map(i => `<option value="${i.id}" ${data.item_id == i.id ? 'selected' : ''}>${escapeHtml(i.name)} (${i.code})</option>`).join('')}
                </select>
            </td>
            <td>
                <select name="outputs[${outputIdx}][dest_godown_id]" class="form-select form-select-sm" required>
                    <option value="">Select Warehouse</option>
                    ${godownsCatalog.map(g => `<option value="${g.id}" ${data.dest_godown_id == g.id ? 'selected' : ''}>${escapeHtml(g.name)}</option>`).join('')}
                </select>
            </td>
            <td>
                <input type="text" name="outputs[${outputIdx}][batch_number]" class="form-control form-control-sm" value="${data.batch_number || ('BAT-' + new Date().toISOString().slice(0,10).replace(/-/g,'') + '-' + Math.floor(100 + Math.random()*900))}">
            </td>
            <td>
                <input type="number" step="0.001" name="outputs[${outputIdx}][produced_quantity]" class="form-control form-control-sm fw-bold text-success" value="${data.produced_quantity || ''}" placeholder="0.000" required>
            </td>
            <td>
                <input type="number" step="0.001" name="outputs[${outputIdx}][rejected_quantity]" class="form-control form-control-sm" value="${data.rejected_quantity || 0}">
            </td>
            <td>
                <select name="outputs[${outputIdx}][quality_status]" class="form-select form-select-sm">
                    <option value="passed" selected>QC Passed</option>
                    <option value="conditional">Conditional Approval</option>
                    <option value="pending">Pending QC</option>
                    <option value="failed">QC Failed</option>
                </select>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="bi bi-trash"></i></button>
            </td>
        `;

        tr.querySelector('.btn-remove-row').addEventListener('click', function () {
            tr.remove();
        });

        document.getElementById('outputRowsContainer').appendChild(tr);
        outputIdx++;
    }

    // 4. Add Wastage Row
    function addWastageRow(data = {}) {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <input type="text" name="wastages[${wastageIdx}][wastage_name]" class="form-control form-control-sm" placeholder="e.g. Broken chillies, fabric cuts, burrs" value="${data.wastage_name || ''}" required>
            </td>
            <td>
                <select name="wastages[${wastageIdx}][wastage_type]" class="form-select form-select-sm">
                    <option value="process_loss">Process Loss</option>
                    <option value="scrap_trimming">Scrap & Trimming</option>
                    <option value="spillage">Spillage</option>
                    <option value="burnt_defective">Burnt / Defective</option>
                    <option value="packaging_damage">Packaging Damage</option>
                    <option value="handling_loss">Handling Loss</option>
                    <option value="other">Other Loss</option>
                </select>
            </td>
            <td>
                <input type="number" step="0.001" name="wastages[${wastageIdx}][quantity]" class="form-control form-control-sm" value="${data.quantity || ''}" placeholder="0.000" required>
            </td>
            <td>
                <input type="text" name="wastages[${wastageIdx}][unit]" class="form-control form-control-sm" value="${data.unit || 'KG'}">
            </td>
            <td>
                <input type="number" step="1" name="wastages[${wastageIdx}][estimated_cost]" class="form-control form-control-sm" value="${data.estimated_cost || 0}">
            </td>
            <td>
                <input type="number" step="0.001" name="wastages[${wastageIdx}][recoverable_quantity]" class="form-control form-control-sm" value="${data.recoverable_quantity || 0}">
            </td>
            <td>
                <input type="text" name="wastages[${wastageIdx}][reason]" class="form-control form-control-sm" placeholder="Cause..." value="${data.reason || ''}">
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="bi bi-trash"></i></button>
            </td>
        `;

        tr.querySelector('.btn-remove-row').addEventListener('click', function () {
            tr.remove();
        });

        document.getElementById('wastageRowsContainer').appendChild(tr);
        wastageIdx++;
    }

    // 5. Add Distribution Row
    function addDistributionRow(data = {}) {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td>
                <select name="distributions[${distributionIdx}][item_id]" class="form-select form-select-sm" required>
                    <option value="">Select Item</option>
                    ${itemsCatalog.map(i => `<option value="${i.id}" ${data.item_id == i.id ? 'selected' : ''}>${escapeHtml(i.name)}</option>`).join('')}
                </select>
            </td>
            <td>
                <select name="distributions[${distributionIdx}][destination_godown_id]" class="form-select form-select-sm" required>
                    <option value="">Select Destination</option>
                    ${godownsCatalog.map(g => `<option value="${g.id}" ${data.destination_godown_id == g.id ? 'selected' : ''}>${escapeHtml(g.name)} (${g.location_type || 'warehouse'})</option>`).join('')}
                </select>
            </td>
            <td>
                <select name="distributions[${distributionIdx}][destination_type]" class="form-select form-select-sm">
                    <option value="factory_sales_counter">Factory Sales Counter</option>
                    <option value="head_office_warehouse">Head Office Warehouse</option>
                    <option value="delivery_van">Delivery Van</option>
                    <option value="branch_warehouse" selected>Branch Warehouse</option>
                    <option value="packing_warehouse">Packing Warehouse</option>
                    <option value="other">Other Staging Unit</option>
                </select>
            </td>
            <td>
                <input type="text" name="distributions[${distributionIdx}][batch_no]" class="form-control form-control-sm" value="${data.batch_no || 'DEFAULT'}">
            </td>
            <td>
                <input type="number" step="0.001" name="distributions[${distributionIdx}][quantity]" class="form-control form-control-sm" value="${data.quantity || ''}" placeholder="0.000" required>
            </td>
            <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-row"><i class="bi bi-trash"></i></button>
            </td>
        `;

        tr.querySelector('.btn-remove-row').addEventListener('click', function () {
            tr.remove();
        });

        document.getElementById('distributionRowsContainer').appendChild(tr);
        distributionIdx++;
    }

    // Attach button listeners
    document.getElementById('addEmployeeBtn').addEventListener('click', () => addEmployeeRow());
    document.getElementById('addMaterialBtn').addEventListener('click', () => addMaterialRow());
    document.getElementById('addOutputBtn').addEventListener('click', () => addOutputRow());
    document.getElementById('addWastageBtn').addEventListener('click', () => addWastageRow());
    document.getElementById('addDistributionBtn').addEventListener('click', () => addDistributionRow());

    // Initialize with 1 default employee, material, and output row
    addEmployeeRow();
    addMaterialRow();
    addOutputRow();
});
</script>
@endsection

