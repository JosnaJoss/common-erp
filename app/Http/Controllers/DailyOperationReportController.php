<?php

namespace App\Http\Controllers;

use App\Models\DailyOperation;
use App\Models\Department;
use App\Models\Godown;
use App\Models\Staff;
use App\Services\CompanyContextService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DailyOperationReportController extends Controller
{
    public function index(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        
        $dateFrom = $request->get('date_from', $request->get('date', now()->subDays(6)->toDateString()));
        $dateTo = $request->get('date_to', $request->get('date', now()->toDateString()));

        $query = DailyOperation::where('company_id', $companyId)
            ->whereDate('operation_date', '>=', $dateFrom)
            ->whereDate('operation_date', '<=', $dateTo)
            ->with([
                'department', 'branch', 'departmentHead', 'supervisor',
                'employees.staff', 'materials.item.unit', 'outputs.item.unit',
                'wastages', 'distributions.destinationGodown'
            ]);

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->get('department_id'));
        }
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->get('branch_id'));
        }
        if ($request->filled('supervisor_id')) {
            $query->where('supervisor_id', $request->get('supervisor_id'));
        }
        if ($request->filled('status')) {
            $query->where('approval_status', $request->get('status'));
        }
        if ($request->filled('process_name')) {
            $query->where('process_name', 'like', '%' . $request->get('process_name') . '%');
        }

        $operations = $query->orderBy('operation_date', 'desc')->get();

        // Calculate Executive Summary Metrics
        $totalOperations = $operations->count();
        $totalLaborHours = 0;
        $totalLaborCost = 0;
        $totalMaterialCost = 0;
        $totalWastageCost = 0;
        $totalConsumedQty = 0;
        $totalProducedQty = 0;

        foreach ($operations as $op) {
            $totalLaborCost += (float)$op->total_labor_cost;
            $totalMaterialCost += (float)$op->total_material_cost;
            $totalWastageCost += (float)$op->total_wastage_cost;

            foreach ($op->employees as $emp) {
                $totalLaborHours += (float)$emp->hours_worked;
            }
            foreach ($op->materials as $mat) {
                $totalConsumedQty += (float)$mat->consumed_quantity;
            }
            foreach ($op->outputs as $out) {
                $totalProducedQty += (float)$out->produced_quantity;
            }
        }

        // Department-wise grouping
        $departmentSummaries = $operations->groupBy('department_id')->map(function ($deptOps) {
            $dept = $deptOps->first()->department;
            $hours = 0;
            $outQty = 0;
            $wasteCost = 0;

            foreach ($deptOps as $op) {
                $wasteCost += (float)$op->total_wastage_cost;
                foreach ($op->employees as $e) $hours += (float)$e->hours_worked;
                foreach ($op->outputs as $o) $outQty += (float)$o->produced_quantity;
            }

            return [
                'department_name' => $dept ? $dept->name : 'Unassigned',
                'operations_count' => $deptOps->count(),
                'labor_hours' => $hours,
                'produced_quantity' => $outQty,
                'wastage_cost' => $wasteCost,
            ];
        });

        // CSV Export if requested
        if ($request->get('export') === 'csv') {
            return $this->exportCsv($operations, $dateFrom, $dateTo);
        }

        $departments = Department::where('company_id', $companyId)->where('status', 'active')->get();
        $supervisors = Staff::where('company_id', $companyId)->where('status', 'active')->get();

        return view('daily_operations.report', compact(
            'operations',
            'totalOperations',
            'totalLaborHours',
            'totalLaborCost',
            'totalMaterialCost',
            'totalWastageCost',
            'totalConsumedQty',
            'totalProducedQty',
            'departmentSummaries',
            'departments',
            'supervisors',
            'dateFrom',
            'dateTo'
        ));
    }

    protected function exportCsv($operations, $dateFrom, $dateTo)
    {
        $fileName = "Daily_Operations_Report_{$dateFrom}_to_{$dateTo}.csv";
        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename={$fileName}",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        ];

        $callback = function () use ($operations) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Operation #', 'Date', 'Department', 'Process / Activity',
                'Status', 'Labor Hours', 'Labor Cost', 'Material Cost',
                'Produced Qty', 'Wastage Cost', 'Remarks'
            ]);

            foreach ($operations as $op) {
                $hours = $op->employees->sum('hours_worked');
                $produced = $op->outputs->sum('produced_quantity');

                fputcsv($file, [
                    $op->operation_number,
                    $op->operation_date->format('Y-m-d'),
                    $op->department?->name ?? 'N/A',
                    $op->process_name,
                    strtoupper(str_replace('_', ' ', $op->approval_status)),
                    number_format($hours, 2),
                    number_format((float)$op->total_labor_cost, 2),
                    number_format((float)$op->total_material_cost, 2),
                    number_format($produced, 3),
                    number_format((float)$op->total_wastage_cost, 2),
                    $op->work_description,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}

