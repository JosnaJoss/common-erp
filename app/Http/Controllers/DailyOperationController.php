<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\DailyOperation;
use App\Models\Department;
use App\Models\Godown;
use App\Models\IndustryProcessTemplate;
use App\Models\Item;
use App\Models\Staff;
use App\Models\StockBatch;
use App\Services\CompanyContextService;
use App\Services\DailyOperationService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DailyOperationController extends Controller
{
    protected DailyOperationService $operationService;

    public function __construct(DailyOperationService $operationService)
    {
        $this->operationService = $operationService;
    }

    /**
     * List daily operational records with filters.
     */
    public function index(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $query = DailyOperation::where('company_id', $companyId)
            ->with(['department', 'departmentHead', 'supervisor', 'outputs.item', 'employees.staff']);

        if ($request->filled('date')) {
            $query->whereDate('operation_date', $request->get('date'));
        }
        if ($request->filled('from_date')) {
            $query->whereDate('operation_date', '>=', $request->get('from_date'));
        }
        if ($request->filled('to_date')) {
            $query->whereDate('operation_date', '<=', $request->get('to_date'));
        }
        if ($request->filled('department_id')) {
            $query->where('department_id', $request->get('department_id'));
        }
        if ($request->filled('status')) {
            $query->where('approval_status', $request->get('status'));
        }

        $operations = $query->latest('operation_date')->latest('id')->paginate(15);
        $departments = Department::where('company_id', $companyId)->where('status', 'active')->get();

        return view('daily_operations.index', compact('operations', 'departments'));
    }

    /**
     * Render the Daily Operation creation form.
     */
    public function create()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $departments = Department::where('company_id', $companyId)->where('status', 'active')->get();
        $godowns = Godown::where('company_id', $companyId)->where('status', 'active')->get();
        $staff = Staff::where('company_id', $companyId)->where('status', 'active')->get();
        $items = Item::where('company_id', $companyId)->where('status', 'active')->with('unit')->get();

        $activeCompany = CompanyContextService::getActiveCompany();
        $templates = IndustryProcessTemplate::where(function ($q) use ($companyId, $activeCompany) {
            $q->whereNull('company_id');
            if ($activeCompany && $activeCompany->business_type) {
                $q->orWhere('industry_type', $activeCompany->business_type);
            }
        })->where('status', 'active')->get();

        return view('daily_operations.create', compact('departments', 'godowns', 'staff', 'items', 'templates'));
    }

    /**
     * Store a new daily operation.
     */
    public function store(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $activeBranchId = CompanyContextService::getActiveBranchId();

        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'operation_date' => 'required|date',
            'process_name' => 'required|string|max:100',
            'work_order_ref' => 'nullable|string|max:100',
            'department_head_id' => 'nullable|exists:staff,id',
            'supervisor_id' => 'nullable|exists:staff,id',
            'work_description' => 'nullable|string',
            'start_time' => 'nullable',
            'end_time' => 'nullable',
            'work_status' => 'required|in:draft,in_progress,completed,paused',
            'approval_status' => 'required|in:draft,submitted,supervisor_approved,department_head_approved,admin_approved',
            'remarks' => 'nullable|string',

            // Employees
            'employees' => 'nullable|array',
            'employees.*.staff_id' => 'required|exists:staff,id',
            'employees.*.employment_type' => 'required|in:salaried,daily_wage,hourly,contract,piece_rate',
            'employees.*.hours_worked' => 'required|numeric|min:0',
            'employees.*.units_completed' => 'nullable|numeric|min:0',
            'employees.*.rate_per_unit' => 'nullable|numeric|min:0',
            'employees.*.remarks' => 'nullable|string',

            // Materials consumed
            'materials' => 'nullable|array',
            'materials.*.item_id' => 'required|exists:items,id',
            'materials.*.source_godown_id' => 'required|exists:godowns,id',
            'materials.*.batch_no' => 'nullable|string',
            'materials.*.opening_quantity' => 'nullable|numeric|min:0',
            'materials.*.issued_quantity' => 'nullable|numeric|min:0',
            'materials.*.consumed_quantity' => 'required|numeric|min:0.001',
            'materials.*.returned_quantity' => 'nullable|numeric|min:0',
            'materials.*.wastage_quantity' => 'nullable|numeric|min:0',
            'materials.*.remaining_quantity' => 'nullable|numeric|min:0',
            'materials.*.unit_cost' => 'nullable|numeric|min:0',

            // Outputs produced
            'outputs' => 'nullable|array',
            'outputs.*.item_id' => 'required|exists:items,id',
            'outputs.*.dest_godown_id' => 'required|exists:godowns,id',
            'outputs.*.batch_number' => 'nullable|string',
            'outputs.*.produced_quantity' => 'required|numeric|min:0.001',
            'outputs.*.rejected_quantity' => 'nullable|numeric|min:0',
            'outputs.*.wastage_quantity' => 'nullable|numeric|min:0',
            'outputs.*.quality_status' => 'nullable|in:pending,passed,failed,conditional',
            'outputs.*.unit_cost' => 'nullable|numeric|min:0',
            'outputs.*.production_date' => 'nullable|date',
            'outputs.*.expiry_date' => 'nullable|date',
            'outputs.*.remarks' => 'nullable|string',

            // Wastages
            'wastages' => 'nullable|array',
            'wastages.*.wastage_name' => 'required|string|max:100',
            'wastages.*.wastage_type' => 'required|in:process_loss,scrap_trimming,spillage,burnt_defective,packaging_damage,handling_loss,other',
            'wastages.*.quantity' => 'required|numeric|min:0.001',
            'wastages.*.unit' => 'required|string|max:20',
            'wastages.*.estimated_cost' => 'nullable|numeric|min:0',
            'wastages.*.recoverable_quantity' => 'nullable|numeric|min:0',
            'wastages.*.reason' => 'nullable|string',

            // Distributions
            'distributions' => 'nullable|array',
            'distributions.*.item_id' => 'required|exists:items,id',
            'distributions.*.destination_godown_id' => 'required|exists:godowns,id',
            'distributions.*.destination_type' => 'required|in:factory_sales_counter,head_office_warehouse,delivery_van,branch_warehouse,packing_warehouse,other',
            'distributions.*.batch_no' => 'nullable|string',
            'distributions.*.quantity' => 'required|numeric|min:0.001',
            'distributions.*.notes' => 'nullable|string',
        ]);

        $validated['company_id'] = $companyId;
        $validated['branch_id'] = $activeBranchId;

        $employees = $request->input('employees', []);
        $materials = $request->input('materials', []);
        $outputs = $request->input('outputs', []);
        $wastages = $request->input('wastages', []);
        $distributions = $request->input('distributions', []);

        try {
            $operation = $this->operationService->createOperation(
                $validated,
                $employees,
                $materials,
                $outputs,
                $wastages,
                $distributions,
                auth()->id() ?? 1
            );

            AuditLog::log('daily_operations', 'create', "Created Daily Operation #{$operation->operation_number} for {$operation->process_name}");

            return redirect()->route('daily_operations.show', $operation->id)
                ->with('success', "Daily Operation {$operation->operation_number} recorded successfully.");
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Show detailed operational record.
     */
    public function show(DailyOperation $daily_operation)
    {
        $daily_operation->load([
            'company', 'branch', 'department', 'departmentHead', 'supervisor', 'approver', 'creator',
            'employees.staff',
            'materials.item.unit', 'materials.sourceGodown',
            'outputs.item.unit', 'outputs.destGodown',
            'wastages.item',
            'distributions.item.unit', 'distributions.destinationGodown',
            'stockMovements.item.unit', 'stockMovements.godown'
        ]);

        return view('daily_operations.show', compact('daily_operation'));
    }

    /**
     * Submit operation for approval.
     */
    public function submit(DailyOperation $daily_operation)
    {
        try {
            $this->operationService->submitOperation($daily_operation, auth()->id() ?? 1);
            AuditLog::log('daily_operations', 'submit', "Submitted Daily Operation #{$daily_operation->operation_number}");
            return back()->with('success', "Operation {$daily_operation->operation_number} submitted for approval.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Approve operation.
     */
    public function approve(Request $request, DailyOperation $daily_operation)
    {
        $level = $request->input('target_status', 'department_head_approved');
        $remarks = $request->input('approval_remarks');

        try {
            $this->operationService->approveOperation($daily_operation, $level, auth()->id() ?? 1, $remarks);
            AuditLog::log('daily_operations', 'approve', "Approved Daily Operation #{$daily_operation->operation_number} as {$level}");
            return back()->with('success', "Operation {$daily_operation->operation_number} approved successfully.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Reject operation.
     */
    public function reject(Request $request, DailyOperation $daily_operation)
    {
        $reason = $request->input('rejection_reason', 'Not specified');

        try {
            $this->operationService->rejectOperation($daily_operation, auth()->id() ?? 1, $reason);
            AuditLog::log('daily_operations', 'reject', "Rejected Daily Operation #{$daily_operation->operation_number}: {$reason}");
            return back()->with('warning', "Operation {$daily_operation->operation_number} rejected.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Helper AJAX to get department staff.
     */
    public function getDepartmentStaff(Department $department)
    {
        $staff = Staff::where('department_id', $department->id)
            ->where('status', 'active')
            ->select('id', 'first_name', 'last_name', 'employee_id', 'employee_type', 'hourly_rate', 'piece_rate', 'salary_amount')
            ->get();

        return response()->json([
            'head' => $department->headStaff ? [
                'id' => $department->headStaff->id,
                'name' => $department->headStaff->full_name,
            ] : null,
            'supervisor' => $department->supervisorStaff ? [
                'id' => $department->supervisorStaff->id,
                'name' => $department->supervisorStaff->full_name,
            ] : null,
            'staff' => $staff,
        ]);
    }

    /**
     * Helper AJAX to get batch stock for an item in a godown.
     */
    public function getItemStock(Request $request)
    {
        $itemId = $request->get('item_id');
        $godownId = $request->get('godown_id');

        $batches = StockBatch::where('godown_id', $godownId)
            ->where('item_id', $itemId)
            ->where('quantity', '>', 0)
            ->get(['id', 'batch_no', 'quantity', 'cost_price']);

        $totalAvailable = $batches->sum('quantity');

        return response()->json([
            'batches' => $batches,
            'total_available' => $totalAvailable,
        ]);
    }
}

