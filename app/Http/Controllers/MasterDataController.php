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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

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
        $allCategories = Category::where('company_id', $companyId)->where('status', 'active')->get();
        return view('master.categories.index', compact('categories', 'allCategories'));
    }

    public function categoryStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where('company_id', $companyId),
            ],
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $data['company_id'] = $companyId;
        Category::create($data);

        AuditLog::log('master', 'create', "Created category {$data['name']}");
        return back()->with('success', 'Category created successfully.');
    }

    public function categoryEdit(Category $category = null)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if (!$category || !$category->exists) {
            $category = Category::where('company_id', $companyId)->findOrFail(request()->route('category'));
        }
        if ($category->company_id != $companyId) {
            abort(403);
        }
        return view('master.categories.edit', compact('category'));
    }

    public function categoryUpdate(Request $request, Category $category = null)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if (!$category || !$category->exists) {
            $category = Category::where('company_id', $companyId)->findOrFail($request->route('category'));
        }
        if ($category->company_id != $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where('company_id', $companyId)->ignore($category->id),
            ],
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $category->update($data);

        AuditLog::log('master', 'update', "Updated category {$category->name}");
        return redirect()->route('master.categories.index')->with('success', 'Category updated successfully.');
    }

    public function subcategoryStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subcategories', 'name')
                    ->where('company_id', $companyId)
                    ->where('category_id', $request->input('category_id')),
            ],
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $data['company_id'] = $companyId;
        Subcategory::create($data);

        AuditLog::log('master', 'create', "Created subcategory {$data['name']}");
        return back()->with('success', 'Subcategory created successfully.');
    }

    public function subcategoryEdit(Subcategory $subcategory = null)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if (!$subcategory || !$subcategory->exists) {
            $subcategory = Subcategory::where('company_id', $companyId)->findOrFail(request()->route('subcategory'));
        }
        if ($subcategory->company_id != $companyId) {
            abort(403);
        }
        $categories = Category::where('company_id', $companyId)->where('status', 'active')->get();
        return view('master.subcategories.edit', compact('subcategory', 'categories'));
    }

    public function subcategoryUpdate(Request $request, Subcategory $subcategory = null)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if (!$subcategory || !$subcategory->exists) {
            $subcategory = Subcategory::where('company_id', $companyId)->findOrFail($request->route('subcategory'));
        }
        if ($subcategory->company_id != $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('subcategories', 'name')
                    ->where('company_id', $companyId)
                    ->where('category_id', $request->input('category_id'))
                    ->ignore($subcategory->id),
            ],
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $subcategory->update($data);

        AuditLog::log('master', 'update', "Updated subcategory {$subcategory->name}");
        return redirect()->route('master.categories.index')->with('success', 'Subcategory updated successfully.');
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
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'code')->where('company_id', $companyId),
            ],
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $data['company_id'] = $companyId;
        Product::create($data);

        AuditLog::log('master', 'create', "Created product {$data['name']}");
        return back()->with('success', 'Product created successfully.');
    }

    public function productEdit(Product $product = null)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if (!$product || !$product->exists) {
            $product = Product::where('company_id', $companyId)->findOrFail(request()->route('product'));
        }
        if ($product->company_id != $companyId) {
            abort(403);
        }
        $categories = Category::where('company_id', $companyId)->where('status', 'active')->get();
        $subcategories = Subcategory::where('company_id', $companyId)->where('status', 'active')->get();
        return view('master.products.edit', compact('product', 'categories', 'subcategories'));
    }

    public function productUpdate(Request $request, Product $product = null)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if (!$product || !$product->exists) {
            $product = Product::where('company_id', $companyId)->findOrFail($request->route('product'));
        }
        if ($product->company_id != $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'category_id' => 'nullable|exists:categories,id',
            'subcategory_id' => 'nullable|exists:subcategories,id',
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('products', 'code')->where('company_id', $companyId)->ignore($product->id),
            ],
            'description' => 'nullable|string',
            'status' => 'required|in:active,inactive',
        ]);

        $product->update($data);

        AuditLog::log('master', 'update', "Updated product {$product->name} ({$product->code})");
        return redirect()->route('master.products.index')->with('success', 'Product updated successfully.');
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

    public function routes(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        $activeTab = $request->get('tab');
        if (!$activeTab) {
            if ($request->routeIs('master.cities.*')) {
                $activeTab = 'cities';
            } elseif ($request->routeIs('master.locations.*')) {
                $activeTab = 'locations';
            } else {
                $activeTab = 'routes';
            }
        }

        $branches = Branch::where('company_id', $companyId)->where('status', 'active')->orderBy('name')->get();
        $vehicles = Vehicle::where('company_id', $companyId)->where('status', 'active')->orderBy('vehicle_name')->get();

        $routes = Route::where('company_id', $companyId)
            ->with(['branch', 'vehicle', 'cities', 'locations'])
            ->latest()
            ->paginate(15, ['*'], 'routes_page');

        $cities = City::where('company_id', $companyId)
            ->with(['branch', 'locations'])
            ->orderBy('name')
            ->paginate(15, ['*'], 'cities_page');

        $locations = Location::where('company_id', $companyId)
            ->with(['city.branch'])
            ->orderBy('name')
            ->paginate(15, ['*'], 'locations_page');

        $allCities = City::where('company_id', $companyId)
            ->with(['branch', 'locations'])
            ->orderBy('name')
            ->get();

        $allLocations = Location::where('company_id', $companyId)
            ->with(['city.branch'])
            ->orderBy('name')
            ->get();

        $metrics = [
            'total_branches' => $branches->count(),
            'total_cities' => $allCities->count(),
            'total_locations' => $allLocations->count(),
            'total_routes' => Route::where('company_id', $companyId)->count(),
        ];

        return view('master.routes.index', compact(
            'routes', 'vehicles', 'cities', 'locations', 'branches',
            'allCities', 'allLocations', 'activeTab', 'metrics'
        ));
    }

    public function routeStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        $data = $request->validate([
            'branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where('company_id', $companyId),
            ],
            'route_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('routes', 'route_name')->where('company_id', $companyId),
            ],
            'vehicle_id' => [
                'nullable',
                Rule::exists('vehicles', 'id')->where('company_id', $companyId),
            ],
            'assigned_driver' => 'nullable|string|max:100',
            'cities' => 'nullable|array',
            'cities.*' => [
                Rule::exists('cities', 'id')->where('company_id', $companyId),
            ],
            'locations' => 'nullable|array',
            'locations.*' => [
                Rule::exists('locations', 'id')->where('company_id', $companyId),
            ],
            'status' => 'required|in:active,inactive',
        ]);

        $branchId = $data['branch_id'];

        if (!empty($data['cities'])) {
            $foreignCities = City::whereIn('id', $data['cities'])
                ->where('branch_id', '!=', $branchId)
                ->pluck('name')
                ->toArray();
            if (!empty($foreignCities)) {
                return back()->withInput()->withErrors([
                    'cities' => 'The following cities do not belong to the selected branch: ' . implode(', ', $foreignCities),
                ]);
            }
        }

        if (!empty($data['locations'])) {
            $foreignLocations = Location::whereIn('id', $data['locations'])
                ->whereHas('city', function ($q) use ($branchId) {
                    $q->where('branch_id', '!=', $branchId);
                })
                ->pluck('name')
                ->toArray();
            if (!empty($foreignLocations)) {
                return back()->withInput()->withErrors([
                    'locations' => 'The following locations do not belong to cities of the selected branch: ' . implode(', ', $foreignLocations),
                ]);
            }
        }

        $data['company_id'] = $companyId;
        $route = Route::create($data);

        if (!empty($data['cities'])) {
            $route->cities()->sync($data['cities']);
        }
        if (!empty($data['locations'])) {
            $route->locations()->sync($data['locations']);
        }

        AuditLog::log('master', 'create', "Created route {$route->route_name} for branch #{$route->branch_id}");
        return redirect()->route('master.routes.index', ['tab' => 'routes'])->with('success', 'Delivery route created successfully.');
    }

    public function routeUpdate(Request $request, Route $route = null)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if (!$route || !$route->exists) {
            $route = Route::where('company_id', $companyId)->findOrFail($request->route('route'));
        }
        if ($route->company_id != $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where('company_id', $companyId),
            ],
            'route_name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('routes', 'route_name')->where('company_id', $companyId)->ignore($route->id),
            ],
            'vehicle_id' => [
                'nullable',
                Rule::exists('vehicles', 'id')->where('company_id', $companyId),
            ],
            'assigned_driver' => 'nullable|string|max:100',
            'cities' => 'nullable|array',
            'cities.*' => [
                Rule::exists('cities', 'id')->where('company_id', $companyId),
            ],
            'locations' => 'nullable|array',
            'locations.*' => [
                Rule::exists('locations', 'id')->where('company_id', $companyId),
            ],
            'status' => 'required|in:active,inactive',
        ]);

        $branchId = $data['branch_id'];

        if (!empty($data['cities'])) {
            $foreignCities = City::whereIn('id', $data['cities'])
                ->where('branch_id', '!=', $branchId)
                ->pluck('name')
                ->toArray();
            if (!empty($foreignCities)) {
                return back()->withInput()->withErrors([
                    'cities' => 'The following cities do not belong to the selected branch: ' . implode(', ', $foreignCities),
                ]);
            }
        }

        if (!empty($data['locations'])) {
            $foreignLocations = Location::whereIn('id', $data['locations'])
                ->whereHas('city', function ($q) use ($branchId) {
                    $q->where('branch_id', '!=', $branchId);
                })
                ->pluck('name')
                ->toArray();
            if (!empty($foreignLocations)) {
                return back()->withInput()->withErrors([
                    'locations' => 'The following locations do not belong to cities of the selected branch: ' . implode(', ', $foreignLocations),
                ]);
            }
        }

        $route->update($data);
        $route->cities()->sync($data['cities'] ?? []);
        $route->locations()->sync($data['locations'] ?? []);

        AuditLog::log('master', 'update', "Updated route {$route->route_name}");
        return redirect()->route('master.routes.index', ['tab' => 'routes'])->with('success', 'Delivery route updated successfully.');
    }

    public function routeDestroy(Route $route = null)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if (!$route || !$route->exists) {
            $route = Route::where('company_id', $companyId)->findOrFail(request()->route('route'));
        }
        if ($route->company_id != $companyId) {
            abort(403);
        }

        $name = $route->route_name;
        $route->cities()->detach();
        $route->locations()->detach();
        $route->delete();

        AuditLog::log('master', 'delete', "Deleted route {$name}");
        return redirect()->route('master.routes.index', ['tab' => 'routes'])->with('success', 'Delivery route deleted successfully.');
    }

    public function cityStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        // 1. Bulk Add Multiple Cities
        if ($request->has('cities') && is_array($request->input('cities'))) {
            $data = $request->validate([
                'branch_id' => [
                    'required',
                    Rule::exists('branches', 'id')->where('company_id', $companyId),
                ],
                'cities' => 'required|array|min:1',
                'cities.*.name' => 'required|string|max:255',
                'cities.*.status' => 'required|in:active,inactive',
            ], [
                'cities.*.name.required' => 'All city names are required.',
                'cities.*.name.max' => 'City name may not exceed 255 characters.',
            ]);

            $branch = Branch::where('company_id', $companyId)->findOrFail($data['branch_id']);
            $submittedNames = collect($data['cities'])->pluck('name')->map(fn($n) => trim($n))->filter();

            if ($submittedNames->isEmpty()) {
                return back()->withInput()->withErrors(['cities' => 'Please provide at least one city name.']);
            }

            // Check for duplicate city names within the submitted batch
            if ($submittedNames->count() !== $submittedNames->unique(fn($n) => strtolower($n))->count()) {
                return back()->withInput()->withErrors([
                    'cities' => 'Duplicate city names found in your submission. Each city name must be unique.',
                ]);
            }

            // Check for duplicate city names already existing in the company
            $existingCities = City::where('company_id', $companyId)
                ->whereIn('name', $submittedNames->toArray())
                ->pluck('name')
                ->toArray();

            if (!empty($existingCities)) {
                return back()->withInput()->withErrors([
                    'cities' => 'The following cities already exist: ' . implode(', ', $existingCities),
                ]);
            }

            $createdCount = 0;
            DB::transaction(function () use ($data, $companyId, &$createdCount) {
                foreach ($data['cities'] as $cityRow) {
                    $name = trim($cityRow['name'] ?? '');
                    if (empty($name)) continue;

                    City::create([
                        'company_id' => $companyId,
                        'branch_id' => $data['branch_id'],
                        'name' => $name,
                        'status' => $cityRow['status'] ?? 'active',
                    ]);
                    $createdCount++;
                }
            });

            AuditLog::log('master', 'create', "Bulk added {$createdCount} cities under branch {$branch->name}");
            return redirect()->route('master.routes.index', ['tab' => 'cities'])
                ->with('success', "Successfully added {$createdCount} cities under {$branch->name}.");
        }

        // 2. Single City Add (Backwards Compatibility)
        $data = $request->validate([
            'branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where('company_id', $companyId),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cities', 'name')->where('company_id', $companyId),
            ],
            'status' => 'required|in:active,inactive',
        ]);

        $data['company_id'] = $companyId;
        $city = City::create($data);

        AuditLog::log('master', 'create', "Created city {$city->name} under branch #{$city->branch_id}");
        return redirect()->route('master.routes.index', ['tab' => 'cities'])->with('success', 'City created successfully.');
    }

    public function cityUpdate(Request $request, City $city = null)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if (!$city || !$city->exists) {
            $city = City::where('company_id', $companyId)->findOrFail($request->route('city'));
        }
        if ($city->company_id != $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'branch_id' => [
                'required',
                Rule::exists('branches', 'id')->where('company_id', $companyId),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('cities', 'name')->where('company_id', $companyId)->ignore($city->id),
            ],
            'status' => 'required|in:active,inactive',
        ]);

        $city->update($data);

        AuditLog::log('master', 'update', "Updated city {$city->name}");
        return redirect()->route('master.routes.index', ['tab' => 'cities'])->with('success', 'City updated successfully.');
    }

    public function cityDestroy(City $city = null)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if (!$city || !$city->exists) {
            $city = City::where('company_id', $companyId)->findOrFail(request()->route('city'));
        }
        if ($city->company_id != $companyId) {
            abort(403);
        }

        if ($city->locations()->count() > 0) {
            return redirect()->route('master.routes.index', ['tab' => 'cities'])
                ->with('error', "Cannot delete city '{$city->name}' because it contains {$city->locations()->count()} location(s). Please delete or reassign them first.");
        }

        if ($city->routes()->count() > 0) {
            return redirect()->route('master.routes.index', ['tab' => 'cities'])
                ->with('error', "Cannot delete city '{$city->name}' because it is assigned to delivery routes.");
        }

        $name = $city->name;
        $city->delete();

        AuditLog::log('master', 'delete', "Deleted city {$name}");
        return redirect()->route('master.routes.index', ['tab' => 'cities'])->with('success', 'City deleted successfully.');
    }

    public function locationStore(Request $request)
    {
        $companyId = CompanyContextService::getActiveCompanyId();

        // 1. Bulk Add Multiple Locations
        if ($request->has('locations') && is_array($request->input('locations'))) {
            $data = $request->validate([
                'city_id' => [
                    'required',
                    Rule::exists('cities', 'id')->where('company_id', $companyId),
                ],
                'locations' => 'required|array|min:1',
                'locations.*.name' => 'required|string|max:255',
                'locations.*.pincode' => 'nullable|string|max:20',
                'locations.*.status' => 'required|in:active,inactive',
            ], [
                'locations.*.name.required' => 'All location names are required.',
                'locations.*.name.max' => 'Location name may not exceed 255 characters.',
            ]);

            $city = City::where('company_id', $companyId)->findOrFail($data['city_id']);
            $submittedNames = collect($data['locations'])->pluck('name')->map(fn($n) => trim($n))->filter();

            if ($submittedNames->isEmpty()) {
                return back()->withInput()->withErrors(['locations' => 'Please provide at least one location name.']);
            }

            // Check for duplicate location names within the submitted batch for this city
            if ($submittedNames->count() !== $submittedNames->unique(fn($n) => strtolower($n))->count()) {
                return back()->withInput()->withErrors([
                    'locations' => 'Duplicate location names found in your submission for this city.',
                ]);
            }

            // Check for duplicate location names already existing under this city
            $existingLocations = Location::where('company_id', $companyId)
                ->where('city_id', $city->id)
                ->whereIn('name', $submittedNames->toArray())
                ->pluck('name')
                ->toArray();

            if (!empty($existingLocations)) {
                return back()->withInput()->withErrors([
                    'locations' => 'The following locations already exist in ' . $city->name . ': ' . implode(', ', $existingLocations),
                ]);
            }

            $createdCount = 0;
            DB::transaction(function () use ($data, $companyId, $city, &$createdCount) {
                foreach ($data['locations'] as $locRow) {
                    $name = trim($locRow['name'] ?? '');
                    if (empty($name)) continue;

                    Location::create([
                        'company_id' => $companyId,
                        'city_id' => $city->id,
                        'name' => $name,
                        'pincode' => !empty($locRow['pincode']) ? trim($locRow['pincode']) : null,
                        'status' => $locRow['status'] ?? 'active',
                    ]);
                    $createdCount++;
                }
            });

            AuditLog::log('master', 'create', "Bulk added {$createdCount} locations under city {$city->name}");
            return redirect()->route('master.routes.index', ['tab' => 'locations'])
                ->with('success', "Successfully added {$createdCount} locations under {$city->name}.");
        }

        // 2. Single Location Add (Backwards Compatibility)
        $data = $request->validate([
            'city_id' => [
                'required',
                Rule::exists('cities', 'id')->where('company_id', $companyId),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('locations', 'name')
                    ->where('company_id', $companyId)
                    ->where('city_id', $request->input('city_id')),
            ],
            'pincode' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive',
        ]);

        $data['company_id'] = $companyId;
        $location = Location::create($data);

        AuditLog::log('master', 'create', "Created location {$location->name} in city #{$location->city_id}");
        return redirect()->route('master.routes.index', ['tab' => 'locations'])->with('success', 'Location created successfully.');
    }

    public function locationUpdate(Request $request, Location $location = null)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if (!$location || !$location->exists) {
            $location = Location::where('company_id', $companyId)->findOrFail($request->route('location'));
        }
        if ($location->company_id != $companyId) {
            abort(403);
        }

        $data = $request->validate([
            'city_id' => [
                'required',
                Rule::exists('cities', 'id')->where('company_id', $companyId),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('locations', 'name')
                    ->where('company_id', $companyId)
                    ->where('city_id', $request->input('city_id'))
                    ->ignore($location->id),
            ],
            'pincode' => 'nullable|string|max:20',
            'status' => 'required|in:active,inactive',
        ]);

        $location->update($data);

        AuditLog::log('master', 'update', "Updated location {$location->name}");
        return redirect()->route('master.routes.index', ['tab' => 'locations'])->with('success', 'Location updated successfully.');
    }

    public function locationDestroy(Location $location = null)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if (!$location || !$location->exists) {
            $location = Location::where('company_id', $companyId)->findOrFail(request()->route('location'));
        }
        if ($location->company_id != $companyId) {
            abort(403);
        }

        if ($location->routes()->count() > 0) {
            return redirect()->route('master.routes.index', ['tab' => 'locations'])
                ->with('error', "Cannot delete location '{$location->name}' because it is assigned to delivery routes.");
        }

        $name = $location->name;
        $location->delete();

        AuditLog::log('master', 'delete', "Deleted location {$name}");
        return redirect()->route('master.routes.index', ['tab' => 'locations'])->with('success', 'Location deleted successfully.');
    }

    public function getCitiesByBranch(Request $request, Branch $branch)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($branch->company_id != $companyId) {
            return response()->json([], 403);
        }

        $cities = City::where('branch_id', $branch->id)
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($cities);
    }

    public function getLocationsByCity(Request $request, City $city)
    {
        $companyId = CompanyContextService::getActiveCompanyId();
        if ($city->company_id != $companyId) {
            return response()->json([], 403);
        }

        $locations = Location::where('city_id', $city->id)
            ->where('company_id', $companyId)
            ->where('status', 'active')
            ->orderBy('name')
            ->get(['id', 'name', 'pincode']);

        return response()->json($locations);
    }
}

