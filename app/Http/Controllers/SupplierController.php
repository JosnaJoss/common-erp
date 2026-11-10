<?php

namespace App\Http\Controllers;

use App\Models\AccountGroup;
use App\Models\AuditLog;
use App\Models\Ledger;
use App\Models\Supplier;
use App\Services\AccountingService;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $query = Supplier::where('company_id', $companyId)->with('ledger');

        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('gst_number', 'like', "%{$s}%");
            });
        }

        $suppliers = $query->latest()->paginate(15);
        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'pincode' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'gst_number' => 'nullable|string|max:50',
            'pan_number' => 'nullable|string|max:50',
            'opening_balance' => 'required|numeric|min:0',
            'opening_balance_date' => 'nullable|date',
            'account_type' => 'required|in:debit,credit',
            'status' => 'required|in:active,inactive',
        ]);

        // Auto-link to Sundry Creditors Ledger
        $accountingService = new AccountingService();
        $creditorGroup = AccountGroup::firstOrCreate(
            ['company_id' => $companyId, 'name' => 'Sundry Creditors (Suppliers)'],
            ['nature' => 'Liabilities', 'code' => 'SC-01']
        );

        $ledger = Ledger::firstOrCreate(
            ['company_id' => $companyId, 'name' => $data['name']],
            [
                'account_group_id' => $creditorGroup->id,
                'opening_balance' => $data['opening_balance'],
                'balance_type' => $data['account_type'],
                'current_balance' => $data['opening_balance'],
                'is_bank_or_cash' => false,
                'status' => 'active',
            ]
        );

        $data['company_id'] = $companyId;
        $data['account_group_id'] = $creditorGroup->id;
        $data['ledger_id'] = $ledger->id;
        $data['current_balance'] = $data['opening_balance'];

        $supplier = Supplier::create($data);

        AuditLog::log('supplier', 'create', "Created supplier {$supplier->name} ({$supplier->code})");
        return redirect()->route('suppliers.index')->with('success', 'Supplier created and accounting ledger mapped successfully.');
    }

    public function show(Supplier $supplier)
    {
        $supplier->load(['ledger.journalLines.entry', 'purchaseEntries', 'payments']);
        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'contact_person' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'pincode' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'gst_number' => 'nullable|string|max:50',
            'pan_number' => 'nullable|string|max:50',
            'status' => 'required|in:active,inactive',
        ]);

        $supplier->update($data);

        if ($supplier->ledger) {
            $supplier->ledger->update(['name' => $data['name']]);
        }

        AuditLog::log('supplier', 'update', "Updated supplier {$supplier->name}");
        return redirect()->route('suppliers.index')->with('success', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        AuditLog::log('supplier', 'delete', "Deleted supplier {$supplier->name}");
        return redirect()->route('suppliers.index')->with('success', 'Supplier deleted successfully.');
    }
}

