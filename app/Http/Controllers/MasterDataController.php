<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Category;
use App\Models\City;
use App\Models\Department;
use App\Models\Godown;
use App\Models\Item;
use App\Models\Location;
use App\Models\Product;
use App\Models\Route;
use App\Models\Subcategory;
use App\Models\Unit;
use App\Models\Vehicle;
use App\Services\CompanyContextService;
use Illuminate\Http\Request;

class MasterDataController extends Controller
{
    // ==================== DEPARTMENTS ====================
    public function departments()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $departments = Department::where('company_id', $companyId)->with('branch')->paginate(15);
        $branches = Branch::where('company_id', $companyId)->where('status', 'active')->get();
        return view('master.departments.index', compact('departments', 'branches'));
    }

    public function departmentStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'address' => 'nullable|string',
            'postal_code' => 'nullable|string|max:20',
            'department_head' => 'nullable|string|max:100',
            'responsible_staff' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        $data['company_id'] = $companyId;
        Department::create($data);

        AuditLog::log('master', 'create', "Created department {$data['name']}");
        return back()->with('success', 'Department created successfully.');
    }

    public function departmentUpdate(Request $request, Department $department)
    {
        $data = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'address' => 'nullable|string',
            'postal_code' => 'nullable|string|max:20',
            'department_head' => 'nullable|string|max:100',
            'responsible_staff' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        $department->update($data);
        AuditLog::log('master', 'update', "Updated department {$department->name}");
        return back()->with('success', 'Department updated successfully.');
    }

    public function departmentDestroy(Department $department)
    {
        $department->delete();
        AuditLog::log('master', 'delete', "Deleted department {$department->name}");
        return back()->with('success', 'Department deleted successfully.');
    }

    // ==================== GODOWNS / WAREHOUSES ====================
    public function godowns()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $godowns = Godown::where('company_id', $companyId)->with('branch')->paginate(15);
        $branches = Branch::where('company_id', $companyId)->where('status', 'active')->get();
        return view('master.godowns.index', compact('godowns', 'branches'));
    }

    public function godownStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'address' => 'nullable|string',
            'postal_code' => 'nullable|string|max:20',
            'responsible_staff' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        $data['company_id'] = $companyId;
        Godown::create($data);

        AuditLog::log('master', 'create', "Created godown {$data['name']}");
        return back()->with('success', 'Godown created successfully.');
    }

    public function godownUpdate(Request $request, Godown $godown)
    {
        $data = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'address' => 'nullable|string',
            'postal_code' => 'nullable|string|max:20',
            'responsible_staff' => 'nullable|string|max:100',
            'status' => 'required|in:active,inactive',
        ]);

        $godown->update($data);
        AuditLog::log('master', 'update', "Updated godown {$godown->name}");
        return back()->with('success', 'Godown updated successfully.');
    }

    public function godownDestroy(Godown $godown)
    {
        $godown->delete();
        AuditLog::log('master', 'delete', "Deleted godown {$godown->name}");
        return back()->with('success', 'Godown deleted successfully.');
    }

    // ==================== UNITS ====================
    public function units()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $units = Unit::where('company_id', $companyId)->paginate(15);
        return view('master.units.index', compact('units'));
    }

    public function unitStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:20',
            'unit_type' => 'required|in:weight,quantity,length,volume,other',
            'conversion_factor' => 'required|numeric|min:0.0001',
            'status' => 'required|in:active,inactive',
        ]);

        $data['company_id'] = $companyId;
        Unit::create($data);

        AuditLog::log('master', 'create', "Created unit {$data['symbol']}");
        return back()->with('success', 'Unit created successfully.');
    }

    public function unitUpdate(Request $request, Unit $unit)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'symbol' => 'required|string|max:20',
            'unit_type' => 'required|in:weight,quantity,length,volume,other',
            'conversion_factor' => 'required|numeric|min:0.0001',
            'status' => 'required|in:active,inactive',
        ]);

        $unit->update($data);
        AuditLog::log('master', 'update', "Updated unit {$unit->symbol}");
        return back()->with('success', 'Unit updated successfully.');
    }

    // ==================== CATEGORIES & PRODUCTS ====================
    public function categories()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $categories = Category::where('company_id', $companyId)->with('subcategories')->paginate(15);
        return view('master.categories.index', compact('categories'));
    }

    public function categoryStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $data['company_id'] = $companyId;
        Category::create($data);

        AuditLog::log('master', 'create', "Created category {$data['name']}");
        return back()->with('success', 'Category created successfully.');
    }

    public function subcategoryStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $data['company_id'] = $companyId;
        Subcategory::create($data);

        AuditLog::log('master', 'create', "Created subcategory {$data['name']}");
        return back()->with('success', 'Subcategory created successfully.');
    }

    public function products()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $products = Product::where('company_id', $companyId)->with(['category', 'subcategory'])->paginate(15);
        $categories = Category::where('company_id', $companyId)->where('status', 'active')->get();
        $subcategories = Subcategory::where('company_id', $companyId)->where('status', 'active')->get();
        return view('master.products.index', compact('products', 'categories', 'subcategories'));
    }

    public function productStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $data['company_id'] = $companyId;
        Product::create($data);

        AuditLog::log('master', 'create', "Created product {$data['name']}");
        return back()->with('success', 'Product created successfully.');
    }

    // ==================== ITEMS ====================
    public function items(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $query = Item::where('company_id', $companyId)->with(['category', 'subcategory', 'product', 'unit', 'batches']);

        if ($request->filled('search')) {
            $s = $request->get('search');
            $query->where(function ($q) use ($s) {
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%")
                  ->orWhere('hsn_code', 'like', "%{$s}%");
            });
        }

        if ($request->filled('item_type')) {
            $query->where('item_type', $request->get('item_type'));
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->get('category_id'));
        }

        $items = $query->paginate(15);

        $categories = Category::where('company_id', $companyId)->where('status', 'active')->get();
        $subcategories = Subcategory::where('company_id', $companyId)->where('status', 'active')->get();
        $products = Product::where('company_id', $companyId)->where('status', 'active')->get();
        $units = Unit::where('company_id', $companyId)->where('status', 'active')->get();

        return view('master.items.index', compact('items', 'categories', 'subcategories', 'products', 'units'));
    }

    public function itemCreate()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $categories = Category::where('company_id', $companyId)->where('status', 'active')->get();
        $subcategories = Subcategory::where('company_id', $companyId)->where('status', 'active')->get();
        $products = Product::where('company_id', $companyId)->where('status', 'active')->get();
        $units = Unit::where('company_id', $companyId)->where('status', 'active')->get();
        return view('master.items.create', compact('categories', 'subcategories', 'products', 'units'));
    }

    public function itemStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'product_id' => 'nullable|exists:products,id',
            'unit_id' => 'nullable|exists:units,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'item_type' => 'required|in:sales_item,raw_material,both',
            'weight' => 'nullable|numeric|min:0',
            'min_price' => 'required|numeric|min:0',
            'normal_price' => 'required|numeric|min:0',
            'max_price' => 'required|numeric|min:0',
            'gst_percentage' => 'required|numeric|min:0|max:100',
            'hsn_code' => 'nullable|string|max:50',
            'reorder_quantity' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $data['company_id'] = $companyId;
        Item::create($data);

        AuditLog::log('master', 'create', "Created Item {$data['name']} ({$data['code']})");
        return redirect()->route('master.items.index')->with('success', 'Item created successfully.');
    }

    public function itemEdit(Item $item)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $categories = Category::where('company_id', $companyId)->where('status', 'active')->get();
        $subcategories = Subcategory::where('company_id', $companyId)->where('status', 'active')->get();
        $products = Product::where('company_id', $companyId)->where('status', 'active')->get();
        $units = Unit::where('company_id', $companyId)->where('status', 'active')->get();
        return view('master.items.edit', compact('item', 'categories', 'subcategories', 'products', 'units'));
    }

    public function itemUpdate(Request $request, Item $item)
    {
        $data = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'product_id' => 'nullable|exists:products,id',
            'unit_id' => 'nullable|exists:units,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50',
            'item_type' => 'required|in:sales_item,raw_material,both',
            'weight' => 'nullable|numeric|min:0',
            'min_price' => 'required|numeric|min:0',
            'normal_price' => 'required|numeric|min:0',
            'max_price' => 'required|numeric|min:0',
            'gst_percentage' => 'required|numeric|min:0|max:100',
            'hsn_code' => 'nullable|string|max:50',
            'reorder_quantity' => 'required|numeric|min:0',
            'status' => 'required|in:active,inactive',
        ]);

        $item->update($data);
        AuditLog::log('master', 'update', "Updated Item {$item->name}");
        return redirect()->route('master.items.index')->with('success', 'Item updated successfully.');
    }

    public function itemDestroy(Item $item)
    {
        $item->delete();
        AuditLog::log('master', 'delete', "Deleted Item {$item->name}");
        return redirect()->route('master.items.index')->with('success', 'Item deleted successfully.');
    }

    // ==================== VEHICLES & ROUTES ====================
    public function vehicles()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $vehicles = Vehicle::where('company_id', $companyId)->with('branch')->paginate(15);
        $branches = Branch::where('company_id', $companyId)->where('status', 'active')->get();
        return view('master.vehicles.index', compact('vehicles', 'branches'));
    }

    public function vehicleStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'vehicle_name' => 'required|string|max:255',
            'vehicle_type' => 'required|string|max:50',
            'registration_number' => 'required|string|max:50',
            'capacity' => 'required|numeric|min:0',
            'assigned_driver' => 'nullable|string|max:100',
            'insurance_expiry_date' => 'nullable|date',
            'fitness_certificate_expiry_date' => 'nullable|date',
            'status' => 'required|in:active,maintenance,inactive',
        ]);

        $data['company_id'] = $companyId;
        Vehicle::create($data);

        AuditLog::log('master', 'create', "Created vehicle {$data['registration_number']}");
        return back()->with('success', 'Vehicle registered successfully.');
    }

    public function routes()
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $routes = Route::where('company_id', $companyId)->with(['vehicle', 'cities', 'locations'])->paginate(15);
        $vehicles = Vehicle::where('company_id', $companyId)->where('status', 'active')->get();
        $cities = City::where('company_id', $companyId)->where('status', 'active')->get();
        $locations = Location::where('company_id', $companyId)->where('status', 'active')->get();
        return view('master.routes.index', compact('routes', 'vehicles', 'cities', 'locations'));
    }

    public function routeStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'route_name' => 'required|string|max:255',
            'vehicle_id' => 'nullable|exists:vehicles,id',
            'assigned_driver' => 'nullable|string|max:100',
            'cities' => 'nullable|array',
            'cities.*' => 'exists:cities,id',
            'locations' => 'nullable|array',
            'locations.*' => 'exists:locations,id',
            'status' => 'required|in:active,inactive',
        ]);

        $data['company_id'] = $companyId;
        $route = Route::create($data);

        if ($request->has('cities')) {
            $route->cities()->sync($request->input('cities'));
        }
        if ($request->has('locations')) {
            $route->locations()->sync($request->input('locations'));
        }

        AuditLog::log('master', 'create', "Created route {$data['route_name']}");
        return back()->with('success', 'Delivery route created successfully.');
    }
}

