<?php

namespace App\Http\Controllers;

use App\Models\AccountGroup;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Ledger;
use App\Models\Location;
use App\Models\Route;
use App\Services\AccountingService;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $query = Customer::where('company_id', $companyId)->with(['ledger', 'route', 'location']);

        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%")
                  ->orWhere('shop_name', 'like', "%{$s}%")
                  ->orWhere('phone', 'like', "%{$s}%")
                  ->orWhere('gst_number', 'like', "%{$s}%");
            });
        }

        if ($request->filled('price_level')) {
            $query->where('price_level', $request->get('price_level'));
        }

        $customers = $query->latest()->paginate(15);
        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $routes = Route::where('company_id', $companyId)->where('status', 'active')->get();
        $locations = Location::where('company_id', $companyId)->where('status', 'active')->get();
        return view('customers.create', compact('routes', 'locations'));
    }

    public function store(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'shop_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'pincode' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'territory' => 'nullable|string|max:100',
            'route_id' => 'nullable|exists:routes,id',
            'location_id' => 'nullable|exists:locations,id',
            'gst_number' => 'nullable|string|max:50',
            'pan_number' => 'nullable|string|max:50',
            'price_level' => 'required|in:minimum,normal,maximum',
            'opening_balance' => 'required|numeric|min:0',
            'opening_balance_date' => 'nullable|date',
            'account_type' => 'required|in:debit,credit',
            'status' => 'required|in:active,inactive',
        ]);

        // Auto-link to Sundry Debtors Ledger
        $accountingService = new AccountingService();
        $debtorGroup = AccountGroup::firstOrCreate(
            ['company_id' => $companyId, 'name' => 'Sundry Debtors (Customers)'],
            ['nature' => 'Assets', 'code' => 'SD-01']
        );

        $ledger = Ledger::firstOrCreate(
            ['company_id' => $companyId, 'name' => $data['name']],
            [
                'account_group_id' => $debtorGroup->id,
                'opening_balance' => $data['opening_balance'],
                'balance_type' => $data['account_type'],
                'current_balance' => $data['opening_balance'],
                'is_bank_or_cash' => false,
                'status' => 'active',
            ]
        );

        $data['company_id'] = $companyId;
        $data['account_group_id'] = $debtorGroup->id;
        $data['ledger_id'] = $ledger->id;
        $data['current_balance'] = $data['opening_balance'];

        $customer = Customer::create($data);

        AuditLog::log('customer', 'create', "Created customer {$customer->name} ({$customer->code})");
        return redirect()->route('customers.index')->with('success', 'Customer created and accounting ledger mapped successfully.');
    }

    public function show(Customer $customer)
    {
        $customer->load(['ledger.journalLines.entry', 'salesInvoices', 'collections']);
        return view('customers.show', compact('customer'));
    }

    public function edit(Customer $customer)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $routes = Route::where('company_id', $companyId)->where('status', 'active')->get();
        $locations = Location::where('company_id', $companyId)->where('status', 'active')->get();
        return view('customers.edit', compact('customer', 'routes', 'locations'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'shop_name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'address' => 'nullable|string',
            'pincode' => 'nullable|string|max:20',
            'city' => 'nullable|string|max:100',
            'territory' => 'nullable|string|max:100',
            'route_id' => 'nullable|exists:routes,id',
            'location_id' => 'nullable|exists:locations,id',
            'gst_number' => 'nullable|string|max:50',
            'pan_number' => 'nullable|string|max:50',
            'price_level' => 'required|in:minimum,normal,maximum',
            'status' => 'required|in:active,inactive',
        ]);

        $customer->update($data);

        if ($customer->ledger) {
            $customer->ledger->update(['name' => $data['name']]);
        }

        AuditLog::log('customer', 'update', "Updated customer {$customer->name}");
        return redirect()->route('customers.index')->with('success', 'Customer updated successfully.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        AuditLog::log('customer', 'delete', "Deleted customer {$customer->name}");
        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }
}

