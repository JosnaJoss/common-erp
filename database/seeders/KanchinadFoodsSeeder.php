<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\City;
use App\Models\Company;
use App\Models\CrmLead;
use App\Models\Department;
use App\Models\Godown;
use App\Models\Holiday;
use App\Models\Item;
use App\Models\Location;
use App\Models\Product;
use App\Models\Role;
use App\Models\Route;
use App\Models\Shift;
use App\Models\Staff;
use App\Models\Subcategory;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DailyOperationService;
use Illuminate\Support\Facades\Hash;

class KanchinadFoodsSeeder extends BaseIndustrySeeder
{
    public function run(): void
    {
        // 1. Company Profile
        $company = $this->initCompany('KANCHINAD-FOODS', [
            'name' => 'Kanchinad Foods',
            'registration_no' => 'U15400KL2020PTC062345',
            'gst_no' => '32AABCK3344M1Z2',
            'pan_no' => 'AABCK3344M',
            'address' => 'Plot 15, Spices Industrial Park, Puthencruz, Kochi, Kerala 682308',
            'phone' => '+91 484 275 8899',
            'email' => 'contact@kanchinadfoods.com',
            'website' => 'https://kanchinadfoods.example.com',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'financial_year_start' => '2026-04-01',
            'financial_year_end' => '2027-03-31',
            'business_type' => 'manufacturing',
            'status' => 'active',
        ], ['staff_hr', 'inventory', 'purchase', 'sales', 'accounts', 'production', 'vehicle_route', 'quality', 'crm']);

        // 2. Chart of Accounts & Financial Setup
        $ledgers = $this->initChartOfAccounts(
            $company,
            'Federal Bank Ltd (Current A/c 10020100456789)',
            '10020100456789',
            'FDRL0001002'
        );

        // 3. Branches
        $branchKochi = Branch::firstOrCreate(['company_id' => $company->id, 'code' => 'KF-KOC-01'], [
            'name' => 'Central Spice Milling & Processing Complex',
            'address' => 'Plot 15, Spices Industrial Park, Puthencruz',
            'city' => 'Kochi',
            'state' => 'Kerala',
            'postal_code' => '682308',
            'contact_number' => '+91 484 275 8891',
            'email' => 'kochi@kanchinadfoods.com',
            'branch_manager' => 'K. V. Ramakrishnan Nair',
            'is_main_branch' => true,
            'status' => 'active',
        ]);

        $branchCalicut = Branch::firstOrCreate(['company_id' => $company->id, 'code' => 'KF-CLT-02'], [
            'name' => 'Malabar Regional Depot & Logistics Hub',
            'address' => 'Kallai Road, Near Goods Shed',
            'city' => 'Kozhikode',
            'state' => 'Kerala',
            'postal_code' => '673003',
            'contact_number' => '+91 495 232 4411',
            'email' => 'calicut@kanchinadfoods.com',
            'branch_manager' => 'M. C. Shabeer Ali',
            'is_main_branch' => false,
            'status' => 'active',
        ]);

        $branchThrissur = Branch::firstOrCreate(['company_id' => $company->id, 'code' => 'KF-TCR-03'], [
            'name' => 'Central Kerala Packaging & Distribution Hub',
            'address' => 'Industrial Estate, Ollur, Thrissur',
            'city' => 'Thrissur',
            'state' => 'Kerala',
            'postal_code' => '680306',
            'contact_number' => '+91 487 235 6622',
            'email' => 'thrissur@kanchinadfoods.com',
            'branch_manager' => 'Thomas Varghese',
            'is_main_branch' => false,
            'status' => 'active',
        ]);

        // 4. Godowns
        $godownRaw = Godown::firstOrCreate(['company_id' => $company->id, 'code' => 'WH-KF-RAW'], [
            'branch_id' => $branchKochi->id,
            'name' => 'Raw Whole Spices Warehouse',
            'address' => 'Shed A, Puthencruz Mill',
            'postal_code' => '682308',
            'responsible_staff' => 'Suresh Babu',
            'status' => 'active',
        ]);

        $godownMilling = Godown::firstOrCreate(['company_id' => $company->id, 'code' => 'WH-KF-MIL'], [
            'branch_id' => $branchKochi->id,
            'name' => 'Grinding & Roasting Floor Storage',
            'address' => 'Processing Bay 2, Puthencruz',
            'postal_code' => '682308',
            'responsible_staff' => 'P. Radhakrishnan',
            'status' => 'active',
        ]);

        $godownPkg = Godown::firstOrCreate(['company_id' => $company->id, 'code' => 'WH-KF-PKG'], [
            'branch_id' => $branchKochi->id,
            'name' => 'Packaging Materials & Cartons Godown',
            'address' => 'Shed C, Puthencruz',
            'postal_code' => '682308',
            'responsible_staff' => 'Biju Kurian',
            'status' => 'active',
        ]);

        $godownFinished = Godown::firstOrCreate(['company_id' => $company->id, 'code' => 'WH-KF-FND'], [
            'branch_id' => $branchKochi->id,
            'name' => 'Finished Masalas Central Warehouse',
            'address' => 'Dispatch Bay, Puthencruz Complex',
            'postal_code' => '682308',
            'responsible_staff' => 'Anil Kumar',
            'status' => 'active',
        ]);

        // 5. Departments
        $deptProc = Department::firstOrCreate(['company_id' => $company->id, 'code' => 'DEPT-KF-PROC'], [
            'branch_id' => $branchKochi->id, 'name' => 'Spice Procurement & Mandi Sorting', 'department_head' => 'K. V. Ramakrishnan Nair', 'status' => 'active',
        ]);
        $deptMill = Department::firstOrCreate(['company_id' => $company->id, 'code' => 'DEPT-KF-MILL'], [
            'branch_id' => $branchKochi->id, 'name' => 'Milling, Roasting & Blending Division', 'department_head' => 'Er. Madhavan Kutty', 'status' => 'active',
        ]);
        $deptQC = Department::firstOrCreate(['company_id' => $company->id, 'code' => 'DEPT-KF-QC'], [
            'branch_id' => $branchKochi->id, 'name' => 'Food Quality Assurance & Lab', 'department_head' => 'Dr. Lakshmi Menon', 'status' => 'active',
        ]);
        $deptPkg = Department::firstOrCreate(['company_id' => $company->id, 'code' => 'DEPT-KF-PKG'], [
            'branch_id' => $branchKochi->id, 'name' => 'Automated Pouch Packing Division', 'department_head' => 'Joy Mathew', 'status' => 'active',
        ]);
        $deptDist = Department::firstOrCreate(['company_id' => $company->id, 'code' => 'DEPT-KF-DIST'], [
            'branch_id' => $branchKochi->id, 'name' => 'Logistics & Distributor Sales', 'department_head' => 'Faisal Mohammed', 'status' => 'active',
        ]);
        $deptAcc = Department::firstOrCreate(['company_id' => $company->id, 'code' => 'DEPT-KF-ACC'], [
            'branch_id' => $branchKochi->id, 'name' => 'Finance & Statutory Accounts', 'department_head' => 'Jayakumar Pillai', 'status' => 'active',
        ]);

        // 6. Territory, Cities, Locations & Vehicles
        $cityKochi = City::firstOrCreate(['company_id' => $company->id, 'name' => 'Kochi'], [
            'branch_id' => $branchKochi->id, 'status' => 'active',
        ]);
        $cityCalicut = City::firstOrCreate(['company_id' => $company->id, 'name' => 'Kozhikode'], [
            'branch_id' => $branchCalicut->id, 'status' => 'active',
        ]);
        $cityThrissur = City::firstOrCreate(['company_id' => $company->id, 'name' => 'Thrissur'], [
            'branch_id' => $branchThrissur->id, 'status' => 'active',
        ]);

        $locKakkanad = Location::firstOrCreate(['company_id' => $company->id, 'city_id' => $cityKochi->id, 'name' => 'Kakkanad'], [
            'pincode' => '682030', 'status' => 'active',
        ]);
        $locAluva = Location::firstOrCreate(['company_id' => $company->id, 'city_id' => $cityKochi->id, 'name' => 'Aluva Market'], [
            'pincode' => '683101', 'status' => 'active',
        ]);
        $locMananchira = Location::firstOrCreate(['company_id' => $company->id, 'city_id' => $cityCalicut->id, 'name' => 'Mananchira Square'], [
            'pincode' => '673001', 'status' => 'active',
        ]);

        $vehicle1 = Vehicle::firstOrCreate(['company_id' => $company->id, 'registration_number' => 'KL-07-KF-4400'], [
            'branch_id' => $branchKochi->id,
            'vehicle_name' => 'Eicher Pro 2049 Spice Truck',
            'vehicle_type' => 'Truck',
            'capacity' => 4500.00,
            'assigned_driver' => 'Maniyan Pillai',
            'status' => 'active',
        ]);

        $vehicle2 = Vehicle::firstOrCreate(['company_id' => $company->id, 'registration_number' => 'KL-07-KF-5500'], [
            'branch_id' => $branchKochi->id,
            'vehicle_name' => 'Mahindra Bolero Maxi Delivery Van',
            'vehicle_type' => 'Tempo',
            'capacity' => 1500.00,
            'assigned_driver' => 'Sathyan K.',
            'status' => 'active',
        ]);

        $route1 = Route::firstOrCreate(['company_id' => $company->id, 'route_name' => 'Greater Cochin Wholesalers Route'], [
            'branch_id' => $branchKochi->id,
            'vehicle_id' => $vehicle1->id,
            'assigned_driver' => 'Maniyan Pillai',
            'status' => 'active',
        ]);
        $route1->cities()->syncWithoutDetaching([$cityKochi->id]);
        $route1->locations()->syncWithoutDetaching([$locKakkanad->id, $locAluva->id]);

        // 7. Units of Measure
        $uKg = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'KG'], [
            'name' => 'Kilogram', 'unit_type' => 'weight', 'conversion_factor' => 1, 'status' => 'active',
        ]);
        $uGm = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'GM'], [
            'name' => 'Gram', 'unit_type' => 'weight', 'conversion_factor' => 0.001, 'status' => 'active',
        ]);
        $uPkt = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'PKT'], [
            'name' => 'Foil Packet', 'unit_type' => 'quantity', 'conversion_factor' => 1, 'status' => 'active',
        ]);
        $uBox = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'BOX'], [
            'name' => 'Master Box (24 Packets)', 'unit_type' => 'quantity', 'conversion_factor' => 24, 'status' => 'active',
        ]);

        // 8. Categories & Subcategories
        $catRaw = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Raw Whole Spices'], [
            'description' => 'Unprocessed whole dried spices sourced from farmers and mandis', 'status' => 'active',
        ]);
        $subHotSpices = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catRaw->id, 'name' => 'Hot & Pungent Spices'], [
            'description' => 'Dry red chilli and black pepper corns', 'status' => 'active',
        ]);
        $subSeedSpices = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catRaw->id, 'name' => 'Seed & Herb Spices'], [
            'description' => 'Coriander, cumin, fennel, fenugreek', 'status' => 'active',
        ]);
        $subAromatic = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catRaw->id, 'name' => 'Aromatic Masala Spices'], [
            'description' => 'Cardamom, cinnamon bark, and cloves', 'status' => 'active',
        ]);

        $catPkg = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Packaging Materials'], [
            'description' => 'Food grade printed pouches and corrugated outer cartons', 'status' => 'active',
        ]);
        $subPouches = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catPkg->id, 'name' => 'Printed Foil Pouches'], [
            'description' => 'Multi-layer barrier pouches (100g, 250g, 500g)', 'status' => 'active',
        ]);
        $subCartons = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catPkg->id, 'name' => 'Corrugated Cartons'], [
            'description' => 'Heavy duty 5-ply outer boxes', 'status' => 'active',
        ]);

        $catPurePowders = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Pure Ground Spices'], [
            'description' => 'Single ingredient finely ground aromatic spices', 'status' => 'active',
        ]);
        $subSinglePowder = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catPurePowders->id, 'name' => 'Pure Ground Powders'], [
            'description' => 'Chilli, coriander, turmeric, and black pepper powders', 'status' => 'active',
        ]);

        $catBlends = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Blended Curry Masalas'], [
            'description' => 'Traditional roasted and micro-blended recipe spices', 'status' => 'active',
        ]);
        $subNonVeg = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catBlends->id, 'name' => 'Non-Veg Curry Powders'], [
            'description' => 'Chicken masala, meat masala, fish curry masala, biryani blend', 'status' => 'active',
        ]);
        $subVeg = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catBlends->id, 'name' => 'Traditional Veg Masalas'], [
            'description' => 'Sambar powder, rasam powder, garam masala', 'status' => 'active',
        ]);

        // 9. Products & Items (Raw Materials, Packaging & Finished Products - 26 Items)
        $itemsCatalog = [];

        // Raw Materials
        $itemsCatalog['raw_chilli'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-RAW-CHL'], [
            'category_id' => $catRaw->id, 'subcategory_id' => $subHotSpices->id, 'unit_id' => $uKg->id,
            'name' => 'Guntur Dried Red Chilli Whole', 'item_type' => 'raw_material', 'weight' => 1.0,
            'min_price' => 180.00, 'normal_price' => 210.00, 'max_price' => 240.00, 'gst_percentage' => 5.00, 'hsn_code' => '0904', 'reorder_quantity' => 500, 'status' => 'active',
        ]);
        $itemsCatalog['raw_coriander'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-RAW-COR'], [
            'category_id' => $catRaw->id, 'subcategory_id' => $subSeedSpices->id, 'unit_id' => $uKg->id,
            'name' => 'Erode Whole Coriander Seeds', 'item_type' => 'raw_material', 'weight' => 1.0,
            'min_price' => 110.00, 'normal_price' => 135.00, 'max_price' => 155.00, 'gst_percentage' => 5.00, 'hsn_code' => '0909', 'reorder_quantity' => 600, 'status' => 'active',
        ]);
        $itemsCatalog['raw_turmeric'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-RAW-TUR'], [
            'category_id' => $catRaw->id, 'subcategory_id' => $subSeedSpices->id, 'unit_id' => $uKg->id,
            'name' => 'Salem Turmeric Whole Dried Fingers', 'item_type' => 'raw_material', 'weight' => 1.0,
            'min_price' => 140.00, 'normal_price' => 165.00, 'max_price' => 190.00, 'gst_percentage' => 5.00, 'hsn_code' => '0910', 'reorder_quantity' => 400, 'status' => 'active',
        ]);
        $itemsCatalog['raw_pepper'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-RAW-PEP'], [
            'category_id' => $catRaw->id, 'subcategory_id' => $subHotSpices->id, 'unit_id' => $uKg->id,
            'name' => 'Wayanad Malabar Black Pepper Whole', 'item_type' => 'raw_material', 'weight' => 1.0,
            'min_price' => 580.00, 'normal_price' => 640.00, 'max_price' => 700.00, 'gst_percentage' => 5.00, 'hsn_code' => '0904', 'reorder_quantity' => 200, 'status' => 'active',
        ]);
        $itemsCatalog['raw_cumin'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-RAW-CUM'], [
            'category_id' => $catRaw->id, 'subcategory_id' => $subSeedSpices->id, 'unit_id' => $uKg->id,
            'name' => 'Unjha Whole Cumin Seeds (Jeera)', 'item_type' => 'raw_material', 'weight' => 1.0,
            'min_price' => 320.00, 'normal_price' => 370.00, 'max_price' => 410.00, 'gst_percentage' => 5.00, 'hsn_code' => '0909', 'reorder_quantity' => 250, 'status' => 'active',
        ]);
        $itemsCatalog['raw_fennel'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-RAW-FEN'], [
            'category_id' => $catRaw->id, 'subcategory_id' => $subSeedSpices->id, 'unit_id' => $uKg->id,
            'name' => 'Whole Green Fennel Seeds (Saunf)', 'item_type' => 'raw_material', 'weight' => 1.0,
            'min_price' => 190.00, 'normal_price' => 230.00, 'max_price' => 260.00, 'gst_percentage' => 5.00, 'hsn_code' => '0909', 'reorder_quantity' => 150, 'status' => 'active',
        ]);
        $itemsCatalog['raw_cardamom'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-RAW-CAR'], [
            'category_id' => $catRaw->id, 'subcategory_id' => $subAromatic->id, 'unit_id' => $uKg->id,
            'name' => 'Idukki 8mm Bold Green Cardamom', 'item_type' => 'raw_material', 'weight' => 1.0,
            'min_price' => 2200.00, 'normal_price' => 2600.00, 'max_price' => 2900.00, 'gst_percentage' => 5.00, 'hsn_code' => '0908', 'reorder_quantity' => 50, 'status' => 'active',
        ]);
        $itemsCatalog['raw_cinnamon'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-RAW-CIN'], [
            'category_id' => $catRaw->id, 'subcategory_id' => $subAromatic->id, 'unit_id' => $uKg->id,
            'name' => 'Ceylon True Cinnamon Bark Strips', 'item_type' => 'raw_material', 'weight' => 1.0,
            'min_price' => 450.00, 'normal_price' => 520.00, 'max_price' => 580.00, 'gst_percentage' => 5.00, 'hsn_code' => '0906', 'reorder_quantity' => 80, 'status' => 'active',
        ]);
        $itemsCatalog['raw_cloves'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-RAW-CLV'], [
            'category_id' => $catRaw->id, 'subcategory_id' => $subAromatic->id, 'unit_id' => $uKg->id,
            'name' => 'Zanzibar Whole Dried Cloves', 'item_type' => 'raw_material', 'weight' => 1.0,
            'min_price' => 800.00, 'normal_price' => 920.00, 'max_price' => 1050.00, 'gst_percentage' => 5.00, 'hsn_code' => '0907', 'reorder_quantity' => 60, 'status' => 'active',
        ]);
        $itemsCatalog['raw_fenugreek'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-RAW-MET'], [
            'category_id' => $catRaw->id, 'subcategory_id' => $subSeedSpices->id, 'unit_id' => $uKg->id,
            'name' => 'Whole Small Fenugreek Seeds (Methi)', 'item_type' => 'raw_material', 'weight' => 1.0,
            'min_price' => 80.00, 'normal_price' => 95.00, 'max_price' => 110.00, 'gst_percentage' => 5.00, 'hsn_code' => '0910', 'reorder_quantity' => 100, 'status' => 'active',
        ]);

        // Packaging Materials
        $itemsCatalog['pkg_pouch_500'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-PKG-P500'], [
            'category_id' => $catPkg->id, 'subcategory_id' => $subPouches->id, 'unit_id' => $uPkt->id,
            'name' => 'Printed Metallic Foil Pouch 500g', 'item_type' => 'raw_material', 'weight' => 0.015,
            'min_price' => 2.50, 'normal_price' => 3.20, 'max_price' => 3.80, 'gst_percentage' => 18.00, 'hsn_code' => '3923', 'reorder_quantity' => 2000, 'status' => 'active',
        ]);
        $itemsCatalog['pkg_pouch_250'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-PKG-P250'], [
            'category_id' => $catPkg->id, 'subcategory_id' => $subPouches->id, 'unit_id' => $uPkt->id,
            'name' => 'Printed Metallic Foil Pouch 250g', 'item_type' => 'raw_material', 'weight' => 0.010,
            'min_price' => 1.80, 'normal_price' => 2.40, 'max_price' => 2.90, 'gst_percentage' => 18.00, 'hsn_code' => '3923', 'reorder_quantity' => 3000, 'status' => 'active',
        ]);
        $itemsCatalog['pkg_pouch_100'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-PKG-P100'], [
            'category_id' => $catPkg->id, 'subcategory_id' => $subPouches->id, 'unit_id' => $uPkt->id,
            'name' => 'Printed Metallic Foil Pouch 100g', 'item_type' => 'raw_material', 'weight' => 0.007,
            'min_price' => 1.20, 'normal_price' => 1.60, 'max_price' => 2.00, 'gst_percentage' => 18.00, 'hsn_code' => '3923', 'reorder_quantity' => 5000, 'status' => 'active',
        ]);
        $itemsCatalog['pkg_box_24'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-PKG-BOX24'], [
            'category_id' => $catPkg->id, 'subcategory_id' => $subCartons->id, 'unit_id' => $uPkt->id,
            'name' => 'Printed 5-Ply Corrugated Shipper Carton (24 Packets)', 'item_type' => 'raw_material', 'weight' => 0.450,
            'min_price' => 18.00, 'normal_price' => 24.00, 'max_price' => 28.00, 'gst_percentage' => 18.00, 'hsn_code' => '4819', 'reorder_quantity' => 500, 'status' => 'active',
        ]);

        // Finished Products (Products & Items)
        $pChilliPowder = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-KF-CHL'], [
            'category_id' => $catPurePowders->id, 'subcategory_id' => $subSinglePowder->id,
            'name' => 'Kanchinad Kashmiri Red Chilli Powder', 'description' => 'Rich natural red colour with balanced heat', 'status' => 'active',
        ]);
        $itemsCatalog['fnd_chilli_500'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-CHL-500'], [
            'category_id' => $catPurePowders->id, 'subcategory_id' => $subSinglePowder->id, 'product_id' => $pChilliPowder->id, 'unit_id' => $uPkt->id,
            'name' => 'Kanchinad Kashmiri Chilli Powder 500g', 'item_type' => 'sales_item', 'weight' => 0.50,
            'min_price' => 170.00, 'normal_price' => 210.00, 'max_price' => 235.00, 'gst_percentage' => 5.00, 'hsn_code' => '0904', 'reorder_quantity' => 200, 'status' => 'active',
        ]);
        $itemsCatalog['fnd_chilli_250'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-CHL-250'], [
            'category_id' => $catPurePowders->id, 'subcategory_id' => $subSinglePowder->id, 'product_id' => $pChilliPowder->id, 'unit_id' => $uPkt->id,
            'name' => 'Kanchinad Kashmiri Chilli Powder 250g', 'item_type' => 'sales_item', 'weight' => 0.25,
            'min_price' => 90.00, 'normal_price' => 115.00, 'max_price' => 130.00, 'gst_percentage' => 5.00, 'hsn_code' => '0904', 'reorder_quantity' => 300, 'status' => 'active',
        ]);

        $pCorianderPowder = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-KF-COR'], [
            'category_id' => $catPurePowders->id, 'subcategory_id' => $subSinglePowder->id,
            'name' => 'Kanchinad Roasted Coriander Powder', 'description' => 'Evenly slow-roasted ground coriander powder', 'status' => 'active',
        ]);
        $itemsCatalog['fnd_coriander_500'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-COR-500'], [
            'category_id' => $catPurePowders->id, 'subcategory_id' => $subSinglePowder->id, 'product_id' => $pCorianderPowder->id, 'unit_id' => $uPkt->id,
            'name' => 'Kanchinad Roasted Coriander Powder 500g', 'item_type' => 'sales_item', 'weight' => 0.50,
            'min_price' => 110.00, 'normal_price' => 140.00, 'max_price' => 160.00, 'gst_percentage' => 5.00, 'hsn_code' => '0909', 'reorder_quantity' => 200, 'status' => 'active',
        ]);
        $itemsCatalog['fnd_coriander_250'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-COR-250'], [
            'category_id' => $catPurePowders->id, 'subcategory_id' => $subSinglePowder->id, 'product_id' => $pCorianderPowder->id, 'unit_id' => $uPkt->id,
            'name' => 'Kanchinad Roasted Coriander Powder 250g', 'item_type' => 'sales_item', 'weight' => 0.25,
            'min_price' => 60.00, 'normal_price' => 78.00, 'max_price' => 90.00, 'gst_percentage' => 5.00, 'hsn_code' => '0909', 'reorder_quantity' => 300, 'status' => 'active',
        ]);

        $pTurmericPowder = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-KF-TUR'], [
            'category_id' => $catPurePowders->id, 'subcategory_id' => $subSinglePowder->id,
            'name' => 'Kanchinad Pure Golden Turmeric Powder', 'description' => 'High curcumin content pure turmeric powder', 'status' => 'active',
        ]);
        $itemsCatalog['fnd_turmeric_250'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-TUR-250'], [
            'category_id' => $catPurePowders->id, 'subcategory_id' => $subSinglePowder->id, 'product_id' => $pTurmericPowder->id, 'unit_id' => $uPkt->id,
            'name' => 'Kanchinad Golden Turmeric Powder 250g', 'item_type' => 'sales_item', 'weight' => 0.25,
            'min_price' => 70.00, 'normal_price' => 92.00, 'max_price' => 105.00, 'gst_percentage' => 5.00, 'hsn_code' => '0910', 'reorder_quantity' => 250, 'status' => 'active',
        ]);

        $pPepperPowder = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-KF-PEP'], [
            'category_id' => $catPurePowders->id, 'subcategory_id' => $subSinglePowder->id,
            'name' => 'Kanchinad Malabar Black Pepper Powder', 'description' => 'Freshly crushed high piperine black pepper', 'status' => 'active',
        ]);
        $itemsCatalog['fnd_pepper_100'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-PEP-100'], [
            'category_id' => $catPurePowders->id, 'subcategory_id' => $subSinglePowder->id, 'product_id' => $pPepperPowder->id, 'unit_id' => $uPkt->id,
            'name' => 'Kanchinad Malabar Pepper Powder 100g', 'item_type' => 'sales_item', 'weight' => 0.10,
            'min_price' => 95.00, 'normal_price' => 125.00, 'max_price' => 140.00, 'gst_percentage' => 5.00, 'hsn_code' => '0904', 'reorder_quantity' => 200, 'status' => 'active',
        ]);

        // Blended Masalas
        $pChickenMasala = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-KF-CHK'], [
            'category_id' => $catBlends->id, 'subcategory_id' => $subNonVeg->id,
            'name' => 'Kanchinad Kerala Chicken Curry Masala', 'description' => 'Aromatic traditional 14-spice roasted chicken masala', 'status' => 'active',
        ]);
        $itemsCatalog['fnd_chicken_100'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-CHK-100'], [
            'category_id' => $catBlends->id, 'subcategory_id' => $subNonVeg->id, 'product_id' => $pChickenMasala->id, 'unit_id' => $uPkt->id,
            'name' => 'Kanchinad Chicken Curry Masala 100g', 'item_type' => 'sales_item', 'weight' => 0.10,
            'min_price' => 52.00, 'normal_price' => 68.00, 'max_price' => 78.00, 'gst_percentage' => 5.00, 'hsn_code' => '0910', 'reorder_quantity' => 300, 'status' => 'active',
        ]);

        $pMeatMasala = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-KF-MET'], [
            'category_id' => $catBlends->id, 'subcategory_id' => $subNonVeg->id,
            'name' => 'Kanchinad Malabar Meat Roast Masala', 'description' => 'Spicy Kerala slow-roast mutton and beef masala', 'status' => 'active',
        ]);
        $itemsCatalog['fnd_meat_100'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-MET-100'], [
            'category_id' => $catBlends->id, 'subcategory_id' => $subNonVeg->id, 'product_id' => $pMeatMasala->id, 'unit_id' => $uPkt->id,
            'name' => 'Kanchinad Malabar Meat Masala 100g', 'item_type' => 'sales_item', 'weight' => 0.10,
            'min_price' => 55.00, 'normal_price' => 72.00, 'max_price' => 82.00, 'gst_percentage' => 5.00, 'hsn_code' => '0910', 'reorder_quantity' => 300, 'status' => 'active',
        ]);

        $pSambarPowder = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-KF-SAM'], [
            'category_id' => $catBlends->id, 'subcategory_id' => $subVeg->id,
            'name' => 'Kanchinad Traditional Sambar Powder', 'description' => 'Authentic Kerala Sadya style roasted sambar powder with hing', 'status' => 'active',
        ]);
        $itemsCatalog['fnd_sambar_250'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-SAM-250'], [
            'category_id' => $catBlends->id, 'subcategory_id' => $subVeg->id, 'product_id' => $pSambarPowder->id, 'unit_id' => $uPkt->id,
            'name' => 'Kanchinad Traditional Sambar Powder 250g', 'item_type' => 'sales_item', 'weight' => 0.25,
            'min_price' => 80.00, 'normal_price' => 105.00, 'max_price' => 120.00, 'gst_percentage' => 5.00, 'hsn_code' => '0910', 'reorder_quantity' => 250, 'status' => 'active',
        ]);

        $pGaramMasala = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-KF-GAR'], [
            'category_id' => $catBlends->id, 'subcategory_id' => $subVeg->id,
            'name' => 'Kanchinad Royal Malabar Garam Masala', 'description' => 'Shahi aromatic spice powder for curries and gravies', 'status' => 'active',
        ]);
        $itemsCatalog['fnd_garam_100'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-GAR-100'], [
            'category_id' => $catBlends->id, 'subcategory_id' => $subVeg->id, 'product_id' => $pGaramMasala->id, 'unit_id' => $uPkt->id,
            'name' => 'Kanchinad Royal Garam Masala 100g', 'item_type' => 'sales_item', 'weight' => 0.10,
            'min_price' => 85.00, 'normal_price' => 110.00, 'max_price' => 125.00, 'gst_percentage' => 5.00, 'hsn_code' => '0910', 'reorder_quantity' => 200, 'status' => 'active',
        ]);

        $pFishMasala = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-KF-FSH'], [
            'category_id' => $catBlends->id, 'subcategory_id' => $subNonVeg->id,
            'name' => 'Kanchinad Alleppey Fish Curry Masala', 'description' => 'Kudampuli friendly coastal fish curry masala', 'status' => 'active',
        ]);
        $itemsCatalog['fnd_fish_100'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-FSH-100'], [
            'category_id' => $catBlends->id, 'subcategory_id' => $subNonVeg->id, 'product_id' => $pFishMasala->id, 'unit_id' => $uPkt->id,
            'name' => 'Kanchinad Fish Curry Masala 100g', 'item_type' => 'sales_item', 'weight' => 0.10,
            'min_price' => 48.00, 'normal_price' => 64.00, 'max_price' => 74.00, 'gst_percentage' => 5.00, 'hsn_code' => '0910', 'reorder_quantity' => 250, 'status' => 'active',
        ]);

        $pBiryaniMasala = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-KF-BIR'], [
            'category_id' => $catBlends->id, 'subcategory_id' => $subNonVeg->id,
            'name' => 'Kanchinad Thalassery Biryani Masala', 'description' => 'Special Malabar dum biryani aromatic whole blend', 'status' => 'active',
        ]);
        $itemsCatalog['fnd_biryani_100'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-BIR-100'], [
            'category_id' => $catBlends->id, 'subcategory_id' => $subNonVeg->id, 'product_id' => $pBiryaniMasala->id, 'unit_id' => $uPkt->id,
            'name' => 'Kanchinad Thalassery Biryani Masala 100g', 'item_type' => 'sales_item', 'weight' => 0.10,
            'min_price' => 65.00, 'normal_price' => 85.00, 'max_price' => 98.00, 'gst_percentage' => 5.00, 'hsn_code' => '0910', 'reorder_quantity' => 200, 'status' => 'active',
        ]);

        // 10. Suppliers (7 Raw Spice Mandis & Packaging Makers)
        $suppliers = [
            $this->createSupplierWithLedger($company, $ledgers['creditorGroup'], [
                'code' => 'SUPP-KF-GUNTUR', 'name' => 'Guntur APMC Chilli Growers Consortium',
                'contact_person' => 'Subba Rao Garlapati', 'phone' => '+91 863 223 9911', 'email' => 'sales@gunturchilli.example.com',
                'address' => 'Mirchi Yard, Guntur, Andhra Pradesh 522004', 'pincode' => '522004', 'city' => 'Guntur',
                'gst_number' => '37AAACG1122G1Z3', 'pan_number' => 'AAACG1122G', 'opening_balance' => 65000.00,
            ]),
            $this->createSupplierWithLedger($company, $ledgers['creditorGroup'], [
                'code' => 'SUPP-KF-ERODE', 'name' => 'Erode Turmeric Farmers Producer Co Ltd',
                'contact_person' => 'K. Palanisamy', 'phone' => '+91 424 221 4455', 'email' => 'turmeric@erodeproducer.example.com',
                'address' => 'Semmampalayam Regulated Market, Erode, Tamil Nadu 638009', 'pincode' => '638009', 'city' => 'Erode',
                'gst_number' => '33AAACE3344E1Z4', 'pan_number' => 'AAACE3344E', 'opening_balance' => 45000.00,
            ]),
            $this->createSupplierWithLedger($company, $ledgers['creditorGroup'], [
                'code' => 'SUPP-KF-WAYANAD', 'name' => 'Wayanad Organic Pepper Cultivators Union',
                'contact_person' => 'Mathew Joseph', 'phone' => '+91 4936 202 881', 'email' => 'pepper@wayanadfarmers.example.com',
                'address' => 'Kalpetta Agro Market, Wayanad, Kerala 673121', 'pincode' => '673121', 'city' => 'Wayanad',
                'gst_number' => '32AAACW5566W1Z5', 'pan_number' => 'AAACW5566W', 'opening_balance' => 75000.00,
            ]),
            $this->createSupplierWithLedger($company, $ledgers['creditorGroup'], [
                'code' => 'SUPP-KF-UNIPACK', 'name' => 'Unipack Flexible Laminates & Pouches Ltd',
                'contact_person' => 'Rajeev Shenoy', 'phone' => '+91 484 254 7722', 'email' => 'sales@unipackindia.example.com',
                'address' => 'Eloor Industrial Area, Udyogamandal, Kochi 683501', 'pincode' => '683501', 'city' => 'Kochi',
                'gst_number' => '32AAACU7788U1Z6', 'pan_number' => 'AAACU7788U', 'opening_balance' => 35000.00,
            ]),
            $this->createSupplierWithLedger($company, $ledgers['creditorGroup'], [
                'code' => 'SUPP-KF-TRAVANCORE', 'name' => 'Travancore Corrugated Packaging Box Makers',
                'contact_person' => 'V. N. Sreekumar', 'phone' => '+91 481 256 3399', 'email' => 'boxes@travancorepkg.example.com',
                'address' => 'Kottayam Industrial Estate, Kerala 686004', 'pincode' => '686004', 'city' => 'Kottayam',
                'gst_number' => '32AAACT8899T1Z7', 'pan_number' => 'AAACT8899T', 'opening_balance' => 25000.00,
            ]),
            $this->createSupplierWithLedger($company, $ledgers['creditorGroup'], [
                'code' => 'SUPP-KF-IDUKKI', 'name' => 'Idukki Spices & Cardamom Auctioneers',
                'contact_person' => 'Babu Abraham', 'phone' => '+91 4868 272 100', 'email' => 'auction@idukkispices.example.com',
                'address' => 'Bodinayakanur Auction Center, Idukki 685565', 'pincode' => '685565', 'city' => 'Idukki',
                'gst_number' => '32AAACI4433I1Z8', 'pan_number' => 'AAACI4433I', 'opening_balance' => 85000.00,
            ]),
            $this->createSupplierWithLedger($company, $ledgers['creditorGroup'], [
                'code' => 'SUPP-KF-UNJHA', 'name' => 'Gujarat Cumin & Fennel Agro Traders',
                'contact_person' => 'Hasmukh Patel', 'phone' => '+91 2767 254 112', 'email' => 'jeera@unjhaagro.example.com',
                'address' => 'APMC Market Yard, Unjha, Gujarat 384170', 'pincode' => '384170', 'city' => 'Unjha',
                'gst_number' => '24AAACG6655G1Z9', 'pan_number' => 'AAACG6655G', 'opening_balance' => 40000.00,
            ]),
        ];

        // 11. Distributors & Customers (12 Wholesale & Retail Distributors)
        $customers = [
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-KF-001', 'name' => 'Cochin Spices Wholesale Distribution', 'shop_name' => 'Cochin Spice Hub',
                'phone' => '+91 484 222 5566', 'email' => 'orders@cochinspices.example.com', 'address' => 'Jew Town, Mattancherry, Kochi',
                'pincode' => '682002', 'city' => 'Kochi', 'territory' => 'West Kochi', 'route_id' => $route1->id, 'location_id' => $locKakkanad->id,
                'gst_number' => '32AABCC1122C1Z1', 'pan_number' => 'AABCC1122C', 'price_level' => 'minimum', 'opening_balance' => 85000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-KF-002', 'name' => 'Malabar Supermarket Chain Ltd', 'shop_name' => 'Malabar Hypermarket',
                'phone' => '+91 495 272 1100', 'email' => 'purchase@malabarsuper.example.com', 'address' => 'Mavoor Road, Kozhikode',
                'pincode' => '673004', 'city' => 'Kozhikode', 'territory' => 'Malabar Region', 'price_level' => 'minimum', 'opening_balance' => 120000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-KF-003', 'name' => 'Travancore Retail Provisions & Hypermarket', 'shop_name' => 'Travancore Mart',
                'phone' => '+91 471 247 8899', 'email' => 'provisions@travancoremart.example.com', 'address' => 'MG Road, Thiruvananthapuram',
                'pincode' => '695001', 'city' => 'Thiruvananthapuram', 'price_level' => 'normal', 'opening_balance' => 75000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-KF-004', 'name' => 'Calicut Provision Merchants Association', 'shop_name' => 'Calicut Central Mandi',
                'phone' => '+91 495 236 7788', 'email' => 'cpma@calicutmerchants.example.com', 'address' => 'Big Bazaar, Kozhikode',
                'pincode' => '673001', 'city' => 'Kozhikode', 'price_level' => 'minimum', 'opening_balance' => 95000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-KF-005', 'name' => 'Grand Fresh Hypermarket LLC', 'shop_name' => 'Grand Fresh Lulu Arcade',
                'phone' => '+91 484 288 9900', 'email' => 'procurement@grandfresh.example.com', 'address' => 'Edappally, Kochi',
                'pincode' => '682024', 'city' => 'Kochi', 'price_level' => 'normal', 'opening_balance' => 110000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-KF-006', 'name' => 'Paragon Restaurant & Catering Group', 'shop_name' => 'Paragon Central Kitchen',
                'phone' => '+91 495 276 7020', 'email' => 'kitchen@paragonhotels.example.com', 'address' => 'Kannur Road, Kozhikode',
                'pincode' => '673006', 'city' => 'Kozhikode', 'price_level' => 'minimum', 'opening_balance' => 60000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-KF-007', 'name' => 'Nilgiri Dairy & Grocery Stores', 'shop_name' => 'Nilgiris Kakkanad',
                'phone' => '+91 484 242 4433', 'email' => 'nilgiris.kochi@example.com', 'address' => 'Seaport-Airport Road, Kakkanad',
                'pincode' => '682030', 'city' => 'Kochi', 'price_level' => 'normal', 'opening_balance' => 45000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-KF-008', 'name' => 'Kannur Spices & General Agencies', 'shop_name' => 'Kannur Spice Corner',
                'phone' => '+91 497 270 3344', 'email' => 'kannur.agencies@example.com', 'address' => 'Fort Road, Kannur',
                'pincode' => '670001', 'city' => 'Kannur', 'price_level' => 'normal', 'opening_balance' => 38000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-KF-009', 'name' => 'Kottayam Food Mart & Superstore', 'shop_name' => 'Kottayam Food World',
                'phone' => '+91 481 258 1199', 'email' => 'foodmart@kottayam.example.com', 'address' => 'Collectorate Jn, Kottayam',
                'pincode' => '686002', 'city' => 'Kottayam', 'price_level' => 'normal', 'opening_balance' => 52000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-KF-010', 'name' => 'Ernakulam Central Departmental Store', 'shop_name' => 'Central Dept Stores',
                'phone' => '+91 484 235 6677', 'email' => 'centralstores.ekm@example.com', 'address' => 'Broadway, Kochi',
                'pincode' => '682031', 'city' => 'Kochi', 'price_level' => 'normal', 'opening_balance' => 35000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-KF-011', 'name' => 'Coastal Kerala Food Distributors', 'shop_name' => 'Coastal Provisions',
                'phone' => '+91 477 224 8811', 'email' => 'coastal.dist@example.com', 'address' => 'VCSB Road, Alappuzha',
                'pincode' => '688001', 'city' => 'Alappuzha', 'price_level' => 'normal', 'opening_balance' => 48000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-KF-012', 'name' => 'Palakkad Agro Provisions Agency', 'shop_name' => 'Palakkad Agro Mart',
                'phone' => '+91 491 253 4400', 'email' => 'palakkad.agro@example.com', 'address' => 'GB Road, Palakkad',
                'pincode' => '678001', 'city' => 'Palakkad', 'price_level' => 'normal', 'opening_balance' => 42000.00,
            ]),
        ];

        // 12. Staff & HR (17 Employees across Plant, QC & Logistics)
        $staffRoster = [
            ['first_name' => 'Ramakrishnan', 'last_name' => 'Nair', 'designation' => 'Factory General Manager', 'dept' => $deptProc, 'salary_amount' => 85000.00, 'emp_id' => 'EMP-KF-001', 'gender' => 'Male', 'branch' => $branchKochi],
            ['first_name' => 'Madhavan', 'last_name' => 'Kutty', 'designation' => 'Chief Milling & Plant Engineer', 'dept' => $deptMill, 'salary_amount' => 75000.00, 'emp_id' => 'EMP-KF-002', 'gender' => 'Male', 'branch' => $branchKochi],
            ['first_name' => 'Lakshmi', 'last_name' => 'Menon', 'designation' => 'Head of Quality Assurance & Lab', 'dept' => $deptQC, 'salary_amount' => 70000.00, 'emp_id' => 'EMP-KF-003', 'gender' => 'Female', 'branch' => $branchKochi],
            ['first_name' => 'Joy', 'last_name' => 'Mathew', 'designation' => 'Packaging Line Production Lead', 'dept' => $deptPkg, 'salary_amount' => 55000.00, 'emp_id' => 'EMP-KF-004', 'gender' => 'Male', 'branch' => $branchKochi],
            ['first_name' => 'Faisal', 'last_name' => 'Mohammed', 'designation' => 'Distributor Logistics Manager', 'dept' => $deptDist, 'salary_amount' => 60000.00, 'emp_id' => 'EMP-KF-005', 'gender' => 'Male', 'branch' => $branchKochi],
            ['first_name' => 'Jayakumar', 'last_name' => 'Pillai', 'designation' => 'Senior Finance Manager', 'dept' => $deptAcc, 'salary_amount' => 65000.00, 'emp_id' => 'EMP-KF-006', 'gender' => 'Male', 'branch' => $branchKochi],
            ['first_name' => 'Suresh', 'last_name' => 'Babu', 'designation' => 'Raw Spices Warehouse Supervisor', 'dept' => $deptProc, 'salary_amount' => 45000.00, 'emp_id' => 'EMP-KF-007', 'gender' => 'Male', 'branch' => $branchKochi],
            ['first_name' => 'Radhakrishnan', 'last_name' => 'P.', 'designation' => 'Spice Roasting Master Technician', 'dept' => $deptMill, 'salary_amount' => 48000.00, 'emp_id' => 'EMP-KF-008', 'gender' => 'Male', 'branch' => $branchKochi],
            ['first_name' => 'Asha', 'last_name' => 'Narayanan', 'designation' => 'Senior Food Chemist & QC Officer', 'dept' => $deptQC, 'salary_amount' => 46000.00, 'emp_id' => 'EMP-KF-009', 'gender' => 'Female', 'branch' => $branchKochi],
            ['first_name' => 'Biju', 'last_name' => 'Kurian', 'designation' => 'Form-Fill-Seal Machine Operator', 'dept' => $deptPkg, 'salary_amount' => 38000.00, 'emp_id' => 'EMP-KF-010', 'gender' => 'Male', 'branch' => $branchKochi],
            ['first_name' => 'Anil', 'last_name' => 'Kumar', 'designation' => 'Finished Goods Dispatch Officer', 'dept' => $deptDist, 'salary_amount' => 40000.00, 'emp_id' => 'EMP-KF-011', 'gender' => 'Male', 'branch' => $branchKochi],
            ['first_name' => 'Maniyan', 'last_name' => 'Pillai', 'designation' => 'Heavy Commercial Fleet Driver', 'dept' => $deptDist, 'salary_amount' => 32000.00, 'emp_id' => 'EMP-KF-012', 'gender' => 'Male', 'branch' => $branchKochi],
            ['first_name' => 'Shabeer', 'last_name' => 'Ali', 'designation' => 'Calicut Regional Hub Officer', 'dept' => $deptDist, 'salary_amount' => 52000.00, 'emp_id' => 'EMP-KF-013', 'gender' => 'Male', 'branch' => $branchCalicut],
            ['first_name' => 'Thomas', 'last_name' => 'Varghese', 'designation' => 'Thrissur Packaging Lead', 'dept' => $deptPkg, 'salary_amount' => 50000.00, 'emp_id' => 'EMP-KF-014', 'gender' => 'Male', 'branch' => $branchThrissur],
            ['first_name' => 'Reena', 'last_name' => 'George', 'designation' => 'Billing & Inventory Cashier', 'dept' => $deptAcc, 'salary_amount' => 35000.00, 'emp_id' => 'EMP-KF-015', 'gender' => 'Female', 'branch' => $branchKochi],
            ['first_name' => 'Sathyan', 'last_name' => 'K.', 'designation' => 'Logistics Van Driver', 'dept' => $deptDist, 'salary_amount' => 30000.00, 'emp_id' => 'EMP-KF-016', 'gender' => 'Male', 'branch' => $branchKochi],
            ['first_name' => 'Pradeep', 'last_name' => 'Chandran', 'designation' => 'Electrical Maintenance Technician', 'dept' => $deptMill, 'salary_amount' => 36000.00, 'emp_id' => 'EMP-KF-017', 'gender' => 'Male', 'branch' => $branchKochi],
        ];

        foreach ($staffRoster as $s) {
            $staff = $this->createStaff($company, $s['branch'], $s['dept'], [
                'employee_id' => $s['emp_id'],
                'first_name' => $s['first_name'],
                'last_name' => $s['last_name'],
                'email' => strtolower($s['first_name'] . '.' . substr($s['last_name'], 0, 1)) . '@kanchinadfoods.com',
                'designation' => $s['designation'],
                'salary_amount' => $s['salary_amount'],
                'gender' => $s['gender'],
            ]);
            $this->processStaffAttendanceAndPayroll($company, $staff, $ledgers);
        }

        // Shifts & Holidays
        Shift::firstOrCreate(['company_id' => $company->id, 'shift_name' => 'Milling Shift A (06:00 - 14:30)'], [
            'branch_id' => $branchKochi->id, 'start_time' => '06:00:00', 'end_time' => '14:30:00', 'break_duration_minutes' => 45, 'status' => 'active',
        ]);
        Holiday::firstOrCreate(['company_id' => $company->id, 'holiday_name' => 'Onam Festive Holiday'], [
            'branch_id' => $branchKochi->id, 'holiday_date' => '2026-09-05', 'holiday_type' => 'Festival', 'description' => 'Thiruvonam Celebration',
        ]);

        // 13. Opening Stock for Raw Spices, Pouches, and Finished Powders
        $this->recordOpeningStock($company, $branchKochi, $godownRaw, $itemsCatalog['raw_chilli'], 2500, 185.00, 'BATCH-RAW-CHL-01');
        $this->recordOpeningStock($company, $branchKochi, $godownRaw, $itemsCatalog['raw_coriander'], 3000, 120.00, 'BATCH-RAW-COR-01');
        $this->recordOpeningStock($company, $branchKochi, $godownRaw, $itemsCatalog['raw_turmeric'], 2000, 145.00, 'BATCH-RAW-TUR-01');
        $this->recordOpeningStock($company, $branchKochi, $godownRaw, $itemsCatalog['raw_pepper'], 1200, 600.00, 'BATCH-RAW-PEP-01');
        $this->recordOpeningStock($company, $branchKochi, $godownRaw, $itemsCatalog['raw_cumin'], 1500, 340.00, 'BATCH-RAW-CUM-01');
        $this->recordOpeningStock($company, $branchKochi, $godownRaw, $itemsCatalog['raw_fennel'], 800, 200.00, 'BATCH-RAW-FEN-01');
        $this->recordOpeningStock($company, $branchKochi, $godownRaw, $itemsCatalog['raw_cardamom'], 250, 2400.00, 'BATCH-RAW-CAR-01');
        $this->recordOpeningStock($company, $branchKochi, $godownRaw, $itemsCatalog['raw_cinnamon'], 400, 480.00, 'BATCH-RAW-CIN-01');
        $this->recordOpeningStock($company, $branchKochi, $godownRaw, $itemsCatalog['raw_cloves'], 300, 850.00, 'BATCH-RAW-CLV-01');
        $this->recordOpeningStock($company, $branchKochi, $godownRaw, $itemsCatalog['raw_fenugreek'], 500, 85.00, 'BATCH-RAW-MET-01');

        $this->recordOpeningStock($company, $branchKochi, $godownPkg, $itemsCatalog['pkg_pouch_500'], 15000, 2.80, 'BATCH-PKG-P500-01');
        $this->recordOpeningStock($company, $branchKochi, $godownPkg, $itemsCatalog['pkg_pouch_250'], 20000, 2.10, 'BATCH-PKG-P250-01');
        $this->recordOpeningStock($company, $branchKochi, $godownPkg, $itemsCatalog['pkg_pouch_100'], 30000, 1.40, 'BATCH-PKG-P100-01');
        $this->recordOpeningStock($company, $branchKochi, $godownPkg, $itemsCatalog['pkg_box_24'], 2500, 22.00, 'BATCH-PKG-BOX-01');

        // Opening stock for finished products in Finished Warehouse
        $this->recordOpeningStock($company, $branchKochi, $godownFinished, $itemsCatalog['fnd_chilli_500'], 800, 150.00, 'BATCH-KF-FND-OPN');
        $this->recordOpeningStock($company, $branchKochi, $godownFinished, $itemsCatalog['fnd_chilli_250'], 1200, 80.00, 'BATCH-KF-FND-OPN');
        $this->recordOpeningStock($company, $branchKochi, $godownFinished, $itemsCatalog['fnd_coriander_500'], 800, 95.00, 'BATCH-KF-FND-OPN');
        $this->recordOpeningStock($company, $branchKochi, $godownFinished, $itemsCatalog['fnd_chicken_100'], 1500, 48.00, 'BATCH-KF-FND-OPN');
        $this->recordOpeningStock($company, $branchKochi, $godownFinished, $itemsCatalog['fnd_meat_100'], 1200, 52.00, 'BATCH-KF-FND-OPN');
        $this->recordOpeningStock($company, $branchKochi, $godownFinished, $itemsCatalog['fnd_sambar_250'], 1000, 75.00, 'BATCH-KF-FND-OPN');

        // 14. Recipes / Bill of Materials (BOM)
        $bomChilli500 = $this->createBOM($company, $itemsCatalog['fnd_chilli_500'], 'BOM - Kashmiri Chilli Powder 500g', 1.0, [
            ['item' => $itemsCatalog['raw_chilli'], 'quantity' => 0.510],
            ['item' => $itemsCatalog['pkg_pouch_500'], 'quantity' => 1.0],
        ], 'Includes 2% milling moisture loss allowance.');

        $bomCoriander250 = $this->createBOM($company, $itemsCatalog['fnd_coriander_250'], 'BOM - Roasted Coriander Powder 250g', 1.0, [
            ['item' => $itemsCatalog['raw_coriander'], 'quantity' => 0.255],
            ['item' => $itemsCatalog['pkg_pouch_250'], 'quantity' => 1.0],
        ], 'Slow roasted coriander seed grinding recipe.');

        $bomChicken100 = $this->createBOM($company, $itemsCatalog['fnd_chicken_100'], 'BOM - Kerala Chicken Curry Masala 100g', 1.0, [
            ['item' => $itemsCatalog['raw_chilli'], 'quantity' => 0.035],
            ['item' => $itemsCatalog['raw_coriander'], 'quantity' => 0.025],
            ['item' => $itemsCatalog['raw_turmeric'], 'quantity' => 0.015],
            ['item' => $itemsCatalog['raw_pepper'], 'quantity' => 0.010],
            ['item' => $itemsCatalog['raw_cumin'], 'quantity' => 0.008],
            ['item' => $itemsCatalog['raw_cardamom'], 'quantity' => 0.003],
            ['item' => $itemsCatalog['raw_cinnamon'], 'quantity' => 0.004],
            ['item' => $itemsCatalog['pkg_pouch_100'], 'quantity' => 1.0],
        ], 'Secret 14-spice Malabar chicken curry roasted formulation.');

        $bomMeat100 = $this->createBOM($company, $itemsCatalog['fnd_meat_100'], 'BOM - Malabar Meat Roast Masala 100g', 1.0, [
            ['item' => $itemsCatalog['raw_chilli'], 'quantity' => 0.030],
            ['item' => $itemsCatalog['raw_coriander'], 'quantity' => 0.030],
            ['item' => $itemsCatalog['raw_pepper'], 'quantity' => 0.015],
            ['item' => $itemsCatalog['raw_fennel'], 'quantity' => 0.012],
            ['item' => $itemsCatalog['raw_cinnamon'], 'quantity' => 0.006],
            ['item' => $itemsCatalog['raw_cloves'], 'quantity' => 0.005],
            ['item' => $itemsCatalog['pkg_pouch_100'], 'quantity' => 1.0],
        ], 'Spicy Kerala slow-roast formulation.');

        $bomSambar250 = $this->createBOM($company, $itemsCatalog['fnd_sambar_250'], 'BOM - Traditional Sambar Powder 250g', 1.0, [
            ['item' => $itemsCatalog['raw_coriander'], 'quantity' => 0.100],
            ['item' => $itemsCatalog['raw_chilli'], 'quantity' => 0.080],
            ['item' => $itemsCatalog['raw_turmeric'], 'quantity' => 0.030],
            ['item' => $itemsCatalog['raw_fenugreek'], 'quantity' => 0.020],
            ['item' => $itemsCatalog['raw_cumin'], 'quantity' => 0.020],
            ['item' => $itemsCatalog['pkg_pouch_250'], 'quantity' => 1.0],
        ], 'Authentic Kerala Sadya style roasted sambar powder.');

        // 15. Production Orders & Finished Goods Generation
        // Batch 1: Chilli Powder 500g (Completed)
        $this->recordProductionBatch(
            $company, $branchKochi, $bomChilli500, $itemsCatalog['fnd_chilli_500'],
            $godownRaw, $godownFinished, 'PROD-KF-2026-0001', 'BATCH-CHL-OCT26',
            1000, 1000, '2026-10-10', 'completed',
            [
                ['item' => $itemsCatalog['raw_chilli'], 'quantity' => 510, 'batch_no' => 'BATCH-RAW-CHL-01'],
                ['item' => $itemsCatalog['pkg_pouch_500'], 'quantity' => 1000, 'batch_no' => 'BATCH-PKG-P500-01'],
            ], '2026-10-12'
        );

        // Batch 2: Coriander Powder 250g (Completed)
        $this->recordProductionBatch(
            $company, $branchKochi, $bomCoriander250, $itemsCatalog['fnd_coriander_250'],
            $godownRaw, $godownFinished, 'PROD-KF-2026-0002', 'BATCH-COR-OCT26',
            1500, 1500, '2026-10-14', 'completed',
            [
                ['item' => $itemsCatalog['raw_coriander'], 'quantity' => 382.5, 'batch_no' => 'BATCH-RAW-COR-01'],
                ['item' => $itemsCatalog['pkg_pouch_250'], 'quantity' => 1500, 'batch_no' => 'BATCH-PKG-P250-01'],
            ], '2026-10-16'
        );

        // Batch 3: Chicken Masala 100g (Completed)
        $this->recordProductionBatch(
            $company, $branchKochi, $bomChicken100, $itemsCatalog['fnd_chicken_100'],
            $godownRaw, $godownFinished, 'PROD-KF-2026-0003', 'BATCH-CHK-OCT26',
            2000, 2000, '2026-10-18', 'completed',
            [
                ['item' => $itemsCatalog['raw_chilli'], 'quantity' => 70, 'batch_no' => 'BATCH-RAW-CHL-01'],
                ['item' => $itemsCatalog['raw_coriander'], 'quantity' => 50, 'batch_no' => 'BATCH-RAW-COR-01'],
                ['item' => $itemsCatalog['raw_turmeric'], 'quantity' => 30, 'batch_no' => 'BATCH-RAW-TUR-01'],
                ['item' => $itemsCatalog['raw_pepper'], 'quantity' => 20, 'batch_no' => 'BATCH-RAW-PEP-01'],
                ['item' => $itemsCatalog['pkg_pouch_100'], 'quantity' => 2000, 'batch_no' => 'BATCH-PKG-P100-01'],
            ], '2026-10-20'
        );

        // Batch 4: Sambar Powder 250g (Completed)
        $this->recordProductionBatch(
            $company, $branchKochi, $bomSambar250, $itemsCatalog['fnd_sambar_250'],
            $godownRaw, $godownFinished, 'PROD-KF-2026-0004', 'BATCH-SAM-OCT26',
            1200, 1200, '2026-10-22', 'completed',
            [
                ['item' => $itemsCatalog['raw_coriander'], 'quantity' => 120, 'batch_no' => 'BATCH-RAW-COR-01'],
                ['item' => $itemsCatalog['raw_chilli'], 'quantity' => 96, 'batch_no' => 'BATCH-RAW-CHL-01'],
                ['item' => $itemsCatalog['raw_turmeric'], 'quantity' => 36, 'batch_no' => 'BATCH-RAW-TUR-01'],
                ['item' => $itemsCatalog['pkg_pouch_250'], 'quantity' => 1200, 'batch_no' => 'BATCH-PKG-P250-01'],
            ], '2026-10-24'
        );

        // Batch 5: Malabar Meat Masala 100g (Planned / In-Progress)
        $this->recordProductionBatch(
            $company, $branchKochi, $bomMeat100, $itemsCatalog['fnd_meat_100'],
            $godownRaw, $godownFinished, 'PROD-KF-2026-0005', 'BATCH-MET-NOV26',
            1500, 0, '2026-10-28', 'in_progress', []
        );

        // 16. Quality Checks (QC)
        $this->recordQualityCheck(
            $company, $branchKochi, $godownRaw, $itemsCatalog['raw_chilli'],
            'QC-KF-2026-0001', 'GRN', null, 'GRN-KF-2026-0001', '2026-08-12',
            'Usable', 1000.0, 0.0, 'Moisture content tested 9.2% (Standard < 10%). Aflatoxin test negative. Approved.'
        );

        $this->recordQualityCheck(
            $company, $branchKochi, $godownRaw, $itemsCatalog['raw_pepper'],
            'QC-KF-2026-0002', 'GRN', null, 'GRN-KF-2026-0002', '2026-08-20',
            'Usable', 400.0, 0.0, 'Bulk density 560 g/L. Piperine content 5.4%. Foreign matter nil. Approved.'
        );

        $this->recordQualityCheck(
            $company, $branchKochi, $godownFinished, $itemsCatalog['fnd_chilli_500'],
            'QC-KF-2026-0003', 'Production', null, 'PROD-KF-2026-0001', '2026-10-12',
            'Usable', 1000.0, 0.0, 'Mesh fineness 99.5% through 500 micron sieve. ASTA colour value 98. Approved for packaging.'
        );

        $this->recordQualityCheck(
            $company, $branchKochi, $godownFinished, $itemsCatalog['fnd_chicken_100'],
            'QC-KF-2026-0004', 'Production', null, 'PROD-KF-2026-0003', '2026-10-20',
            'Usable', 2000.0, 0.0, 'Sensory aroma index high. Microbial count well within FSSAI parameters. Approved.'
        );

        // 17. Real Purchases (10 Purchases of Whole Spices & Packaging Materials)
        $purchasePlan = [
            ['supp' => $suppliers[0], 'items' => [['item' => $itemsCatalog['raw_chilli'], 'qty' => 1200, 'rate' => 190.00, 'batch' => 'BATCH-CHL-P1']], 'inv' => 'GNT/26/401', 'date' => '2026-08-11', 'paid' => 200000.00],
            ['supp' => $suppliers[1], 'items' => [['item' => $itemsCatalog['raw_turmeric'], 'qty' => 1000, 'rate' => 150.00, 'batch' => 'BATCH-TUR-P1']], 'inv' => 'ERD/26/892', 'date' => '2026-08-19', 'paid' => 120000.00],
            ['supp' => $suppliers[2], 'items' => [['item' => $itemsCatalog['raw_pepper'], 'qty' => 400, 'rate' => 610.00, 'batch' => 'BATCH-PEP-P1']], 'inv' => 'WND/26/112', 'date' => '2026-08-27', 'paid' => 220000.00],
            ['supp' => $suppliers[3], 'items' => [['item' => $itemsCatalog['pkg_pouch_500'], 'qty' => 10000, 'rate' => 2.60, 'batch' => 'BATCH-PKG-500B'], ['item' => $itemsCatalog['pkg_pouch_250'], 'qty' => 10000, 'rate' => 1.90, 'batch' => 'BATCH-PKG-250B']], 'inv' => 'UNI/26/780', 'date' => '2026-09-03', 'paid' => 45000.00],
            ['supp' => $suppliers[4], 'items' => [['item' => $itemsCatalog['pkg_box_24'], 'qty' => 1500, 'rate' => 20.00, 'batch' => 'BATCH-BOX-P1']], 'inv' => 'TRV/26/502', 'date' => '2026-09-11', 'paid' => 30000.00],
            ['supp' => $suppliers[5], 'items' => [['item' => $itemsCatalog['raw_cardamom'], 'qty' => 80, 'rate' => 2350.00, 'batch' => 'BATCH-CAR-P1'], ['item' => $itemsCatalog['raw_cloves'], 'qty' => 100, 'rate' => 880.00, 'batch' => 'BATCH-CLV-P1']], 'inv' => 'IDK/26/331', 'date' => '2026-09-20', 'paid' => 250000.00],
            ['supp' => $suppliers[6], 'items' => [['item' => $itemsCatalog['raw_cumin'], 'qty' => 800, 'rate' => 350.00, 'batch' => 'BATCH-CUM-P1'], ['item' => $itemsCatalog['raw_fennel'], 'qty' => 500, 'rate' => 210.00, 'batch' => 'BATCH-FEN-P1']], 'inv' => 'UNJ/26/909', 'date' => '2026-09-29', 'paid' => 350000.00],
            ['supp' => $suppliers[0], 'items' => [['item' => $itemsCatalog['raw_chilli'], 'qty' => 1500, 'rate' => 192.00, 'batch' => 'BATCH-CHL-P2']], 'inv' => 'GNT/26/552', 'date' => '2026-10-06', 'paid' => 250000.00],
            ['supp' => $suppliers[1], 'items' => [['item' => $itemsCatalog['raw_coriander'], 'qty' => 2000, 'rate' => 125.00, 'batch' => 'BATCH-COR-P1']], 'inv' => 'ERD/26/991', 'date' => '2026-10-15', 'paid' => 220000.00],
            ['supp' => $suppliers[3], 'items' => [['item' => $itemsCatalog['pkg_pouch_100'], 'qty' => 20000, 'rate' => 1.30, 'batch' => 'BATCH-PKG-100B']], 'inv' => 'UNI/26/899', 'date' => '2026-10-22', 'paid' => 26000.00],
        ];

        $purchaseEntries = [];
        foreach ($purchasePlan as $idx => $p) {
            $num = sprintf('%04d', $idx + 1);
            $itemRows = [];
            foreach ($p['items'] as $it) {
                $itemRows[] = ['item' => $it['item'], 'quantity' => $it['qty'], 'rate' => $it['rate'], 'batch_no' => $it['batch'], 'tax_percent' => 5];
            }
            $pe = $this->recordPurchaseWorkflow($company, $branchKochi, $godownRaw, $p['supp'], $itemRows, [
                'po_number' => "PO-KF-2026-{$num}",
                'grn_number' => "GRN-KF-2026-{$num}",
                'purchase_number' => "PUR-KF-2026-{$num}",
                'invoice_number' => $p['inv'],
                'date' => $p['date'],
                'paid_amount' => $p['paid'],
                'payment_number' => "PAY-KF-2026-{$num}",
            ], $ledgers);
            $purchaseEntries[] = $pe;
        }

        // Moisture Defect Purchase Return
        if (!empty($purchaseEntries[8])) {
            $this->recordPurchaseReturn(
                $company, $branchKochi, $godownRaw, $suppliers[1], $purchaseEntries[8],
                [['item' => $itemsCatalog['raw_coriander'], 'quantity' => 150, 'rate' => 125.00, 'batch_no' => 'BATCH-COR-P1']],
                $ledgers, 'PRT-KF-2026-0001', '2026-10-18', 'Excess moisture in bottom coriander bags - Returned to Erode'
            );
        }

        // 18. Real Sales Transactions (16 Sales to Distributors & Supermarkets)
        $salesPlan = [
            ['cust' => $customers[0], 'items' => [['item' => $itemsCatalog['fnd_chilli_500'], 'qty' => 150, 'rate' => 210.00], ['item' => $itemsCatalog['fnd_coriander_500'], 'qty' => 150, 'rate' => 140.00]], 'date' => '2026-08-20', 'paid' => 45000.00],
            ['cust' => $customers[1], 'items' => [['item' => $itemsCatalog['fnd_chicken_100'], 'qty' => 300, 'rate' => 68.00], ['item' => $itemsCatalog['fnd_meat_100'], 'qty' => 250, 'rate' => 72.00]], 'date' => '2026-08-25', 'paid' => 35000.00],
            ['cust' => $customers[2], 'items' => [['item' => $itemsCatalog['fnd_sambar_250'], 'qty' => 200, 'rate' => 105.00], ['item' => $itemsCatalog['fnd_turmeric_250'], 'qty' => 200, 'rate' => 92.00]], 'date' => '2026-09-02', 'paid' => 35000.00],
            ['cust' => $customers[3], 'items' => [['item' => $itemsCatalog['fnd_chilli_250'], 'qty' => 250, 'rate' => 115.00], ['item' => $itemsCatalog['fnd_coriander_250'], 'qty' => 250, 'rate' => 78.00]], 'date' => '2026-09-08', 'paid' => 40000.00],
            ['cust' => $customers[4], 'items' => [['item' => $itemsCatalog['fnd_garam_100'], 'qty' => 150, 'rate' => 110.00], ['item' => $itemsCatalog['fnd_biryani_100'], 'qty' => 200, 'rate' => 85.00]], 'date' => '2026-09-15', 'paid' => 30000.00],
            ['cust' => $customers[5], 'items' => [['item' => $itemsCatalog['fnd_chicken_100'], 'qty' => 400, 'rate' => 68.00], ['item' => $itemsCatalog['fnd_meat_100'], 'qty' => 400, 'rate' => 72.00], ['item' => $itemsCatalog['fnd_biryani_100'], 'qty' => 300, 'rate' => 85.00]], 'date' => '2026-09-22', 'paid' => 75000.00],
            ['cust' => $customers[6], 'items' => [['item' => $itemsCatalog['fnd_fish_100'], 'qty' => 200, 'rate' => 64.00], ['item' => $itemsCatalog['fnd_sambar_250'], 'qty' => 150, 'rate' => 105.00]], 'date' => '2026-09-28', 'paid' => 25000.00],
            ['cust' => $customers[7], 'items' => [['item' => $itemsCatalog['fnd_pepper_100'], 'qty' => 150, 'rate' => 125.00], ['item' => $itemsCatalog['fnd_chilli_500'], 'qty' => 100, 'rate' => 210.00]], 'date' => '2026-10-02', 'paid' => 35000.00],
            ['cust' => $customers[8], 'items' => [['item' => $itemsCatalog['fnd_coriander_500'], 'qty' => 200, 'rate' => 140.00], ['item' => $itemsCatalog['fnd_turmeric_250'], 'qty' => 150, 'rate' => 92.00]], 'date' => '2026-10-07', 'paid' => 38000.00],
            ['cust' => $customers[9], 'items' => [['item' => $itemsCatalog['fnd_chicken_100'], 'qty' => 250, 'rate' => 68.00], ['item' => $itemsCatalog['fnd_sambar_250'], 'qty' => 150, 'rate' => 105.00]], 'date' => '2026-10-12', 'paid' => 30000.00],
            ['cust' => $customers[10], 'items' => [['item' => $itemsCatalog['fnd_fish_100'], 'qty' => 300, 'rate' => 64.00], ['item' => $itemsCatalog['fnd_meat_100'], 'qty' => 200, 'rate' => 72.00]], 'date' => '2026-10-17', 'paid' => 30000.00],
            ['cust' => $customers[11], 'items' => [['item' => $itemsCatalog['fnd_chilli_500'], 'qty' => 120, 'rate' => 210.00], ['item' => $itemsCatalog['fnd_coriander_500'], 'qty' => 120, 'rate' => 140.00]], 'date' => '2026-10-21', 'paid' => 38000.00],
            ['cust' => $customers[0], 'items' => [['item' => $itemsCatalog['fnd_chicken_100'], 'qty' => 200, 'rate' => 68.00], ['item' => $itemsCatalog['fnd_meat_100'], 'qty' => 200, 'rate' => 72.00]], 'date' => '2026-10-24', 'paid' => 25000.00],
            ['cust' => $customers[1], 'items' => [['item' => $itemsCatalog['fnd_chilli_250'], 'qty' => 300, 'rate' => 115.00], ['item' => $itemsCatalog['fnd_garam_100'], 'qty' => 150, 'rate' => 110.00]], 'date' => '2026-10-27', 'paid' => 45000.00],
            ['cust' => $customers[2], 'items' => [['item' => $itemsCatalog['fnd_sambar_250'], 'qty' => 180, 'rate' => 105.00], ['item' => $itemsCatalog['fnd_biryani_100'], 'qty' => 150, 'rate' => 85.00]], 'date' => '2026-10-29', 'paid' => 28000.00],
            ['cust' => $customers[3], 'items' => [['item' => $itemsCatalog['fnd_coriander_500'], 'qty' => 150, 'rate' => 140.00], ['item' => $itemsCatalog['fnd_chilli_500'], 'qty' => 100, 'rate' => 210.00]], 'date' => '2026-10-31', 'paid' => 38000.00],
        ];

        $salesInvoices = [];
        foreach ($salesPlan as $idx => $s) {
            $num = sprintf('%04d', $idx + 1);
            $itemRows = [];
            foreach ($s['items'] as $it) {
                $itemRows[] = ['item' => $it['item'], 'quantity' => $it['qty'], 'rate' => $it['rate'], 'batch_no' => 'BATCH-KF-FND-OPN', 'tax_percent' => 5];
            }
            $si = $this->recordSalesWorkflow($company, $branchKochi, $godownFinished, $s['cust'], $itemRows, [
                'order_number' => "SO-KF-2026-{$num}",
                'invoice_number' => "INV-KF-2026-{$num}",
                'delivery_number' => "DEL-KF-2026-{$num}",
                'date' => $s['date'],
                'paid_amount' => $s['paid'],
                'collection_number' => "COL-KF-2026-{$num}",
            ], $ledgers, $vehicle1, $route1);
            $salesInvoices[] = $si;
        }

        // Transit Carton Damage Sales Return
        if (!empty($salesInvoices[1])) {
            $this->recordSalesReturn(
                $company, $branchKochi, $godownFinished, $customers[1], $salesInvoices[1],
                [['item' => $itemsCatalog['fnd_chicken_100'], 'quantity' => 10, 'rate' => 68.00, 'batch_no' => 'BATCH-KF-FND-OPN']],
                $ledgers, 'SRT-KF-2026-0001', '2026-08-28', 'Pouch seal crush during transit delivery'
            );
        }

        // 18b. Daily Operations Management (Parts 3, 4, and 5)
        $dailyOpService = new DailyOperationService($this->inventoryService);

        $godownVan1 = $this->inventoryService->ensureMobileVanGodown($vehicle1);
        $godownCounter = Godown::firstOrCreate(['company_id' => $company->id, 'code' => 'WH-KF-COUNTER'], [
            'branch_id' => $branchKochi->id,
            'name' => 'Factory Retail Sales Counter',
            'location_type' => 'sales_outlet',
            'address' => 'Factory Gate Counter, Puthencruz',
            'status' => 'active',
        ]);
        $godownCalicut = Godown::firstOrCreate(['company_id' => $company->id, 'code' => 'WH-KF-CLT-01'], [
            'branch_id' => $branchCalicut->id,
            'name' => 'Calicut Regional Stock Depot',
            'location_type' => 'warehouse',
            'address' => 'Kallai Road Goods Shed',
            'postal_code' => '673003',
            'status' => 'active',
        ]);

        $workerRadhakrishnan = Staff::where('company_id', $company->id)->where('employee_id', 'EMP-KF-008')->first();
        $workerPradeep = Staff::where('company_id', $company->id)->where('employee_id', 'EMP-KF-017')->first();
        $leadJoy = Staff::where('company_id', $company->id)->where('employee_id', 'EMP-KF-004')->first();
        $opBiju = Staff::where('company_id', $company->id)->where('employee_id', 'EMP-KF-010')->first();
        $headMadhavan = Staff::where('company_id', $company->id)->where('employee_id', 'EMP-KF-002')->first();

        // 1. Part 4 Example: Production Department Daily Report - Chicken Masala Batch
        $dailyOpService->createOperation([
            'company_id' => $company->id,
            'branch_id' => $branchKochi->id,
            'department_id' => $deptMill->id,
            'operation_number' => 'DOP-KF-20261015-001',
            'operation_date' => '2026-10-15',
            'department_head_id' => $headMadhavan?->id,
            'supervisor_id' => $workerRadhakrishnan?->id,
            'work_order_ref' => 'WO-KF-CHICKEN-01',
            'process_name' => 'Formulation Spice Ribbon Blending',
            'work_description' => 'Precision dry blending and micro-roasting of premium Chicken Masala batch.',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'work_status' => 'completed',
            'approval_status' => 'department_head_approved',
            'remarks' => 'Batch aroma, granulation and volatile oil content fully meet FSSAI quality standards.',
        ], [
            ['staff_id' => $workerRadhakrishnan->id, 'employment_type' => 'salaried', 'hours_worked' => 8.0, 'units_completed' => 88],
            ['staff_id' => $workerPradeep->id, 'employment_type' => 'salaried', 'hours_worked' => 8.0, 'units_completed' => 88],
        ], [
            ['item_id' => $itemsCatalog['raw_chilli']->id, 'source_godown_id' => $godownRaw->id, 'batch_no' => 'BATCH-RAW-CHL-01', 'consumed_quantity' => 50.0, 'unit_cost' => 185.00],
            ['item_id' => $itemsCatalog['raw_coriander']->id, 'source_godown_id' => $godownRaw->id, 'batch_no' => 'BATCH-RAW-COR-01', 'consumed_quantity' => 30.0, 'unit_cost' => 120.00],
            ['item_id' => $itemsCatalog['raw_turmeric']->id, 'source_godown_id' => $godownRaw->id, 'batch_no' => 'BATCH-RAW-TUR-01', 'consumed_quantity' => 10.0, 'unit_cost' => 145.00],
            ['item_id' => $itemsCatalog['raw_pepper']->id, 'source_godown_id' => $godownRaw->id, 'batch_no' => 'BATCH-RAW-PEP-01', 'consumed_quantity' => 5.0, 'unit_cost' => 450.00],
        ], [
            ['item_id' => $itemsCatalog['fnd_chicken_100']->id, 'dest_godown_id' => $godownMilling->id, 'batch_number' => 'BAT-KF-CHK-261015', 'produced_quantity' => 88.0, 'unit_cost' => 48.00, 'quality_status' => 'passed'],
        ], [
            ['wastage_name' => 'Grinding Sieve Coarse Residue', 'wastage_type' => 'process_loss', 'quantity' => 4.0, 'unit' => 'KG', 'estimated_cost' => 650.0, 'recoverable_quantity' => 2.0, 'reason' => 'Coarse fibrous particles rejected at vibratory sieve'],
            ['wastage_name' => 'Cyclone Exhaust Micro-Dust', 'wastage_type' => 'process_loss', 'quantity' => 3.0, 'unit' => 'KG', 'estimated_cost' => 480.0, 'recoverable_quantity' => 0.0, 'reason' => 'Fine air suspension during pneumatic transfer'],
        ]);

        // 2. Part 5 Example: Packing Department Management & Stock Distribution
        $dailyOpService->createOperation([
            'company_id' => $company->id,
            'branch_id' => $branchKochi->id,
            'department_id' => $deptPkg->id,
            'operation_number' => 'DOP-KF-20261018-002',
            'operation_date' => '2026-10-18',
            'department_head_id' => $leadJoy?->id,
            'supervisor_id' => $opBiju?->id,
            'work_order_ref' => 'WO-KF-PCK-02',
            'process_name' => 'Pouch Packing & Carton Boxing',
            'work_description' => 'Automated nitrogen-flushed pouch packaging and outer carton boxing of ground powders.',
            'start_time' => '08:30:00',
            'end_time' => '17:30:00',
            'work_status' => 'completed',
            'approval_status' => 'department_head_approved',
            'remarks' => '150 kg processed into multi-layer moisture barrier foil pouches and distributed to locations.',
        ], [
            ['staff_id' => $leadJoy->id, 'employment_type' => 'salaried', 'hours_worked' => 8.5, 'units_completed' => 900],
            ['staff_id' => $opBiju->id, 'employment_type' => 'salaried', 'hours_worked' => 8.5, 'units_completed' => 900],
        ], [
            ['item_id' => $itemsCatalog['pkg_pouch_100']->id, 'source_godown_id' => $godownPkg->id, 'batch_no' => 'BATCH-PKG-P100-01', 'consumed_quantity' => 500.0, 'unit_cost' => 1.60],
            ['item_id' => $itemsCatalog['pkg_pouch_250']->id, 'source_godown_id' => $godownPkg->id, 'batch_no' => 'BATCH-PKG-P250-01', 'consumed_quantity' => 300.0, 'unit_cost' => 2.40],
            ['item_id' => $itemsCatalog['pkg_pouch_500']->id, 'source_godown_id' => $godownPkg->id, 'batch_no' => 'BATCH-PKG-P500-01', 'consumed_quantity' => 100.0, 'unit_cost' => 3.20],
        ], [
            ['item_id' => $itemsCatalog['fnd_chicken_100']->id, 'dest_godown_id' => $godownFinished->id, 'batch_number' => 'BAT-KF-PCK-261018', 'produced_quantity' => 900.0, 'unit_cost' => 52.00, 'quality_status' => 'passed'],
        ], [
            ['wastage_name' => 'FFS Machine Foil Jam & Misprints', 'wastage_type' => 'packaging_damage', 'quantity' => 12.0, 'unit' => 'PKT', 'estimated_cost' => 28.0, 'recoverable_quantity' => 0.0, 'reason' => 'Roll calibration seal error'],
        ], [
            // Exact Distribution Destinations from User Prompt Part 5!
            ['item_id' => $itemsCatalog['fnd_chicken_100']->id, 'destination_godown_id' => $godownCounter->id, 'destination_type' => 'factory_sales_counter', 'batch_no' => 'BAT-KF-PCK-261018', 'quantity' => 100.0],
            ['item_id' => $itemsCatalog['fnd_chicken_100']->id, 'destination_godown_id' => $godownFinished->id, 'destination_type' => 'head_office_warehouse', 'batch_no' => 'BAT-KF-PCK-261018', 'quantity' => 300.0],
            ['item_id' => $itemsCatalog['fnd_chicken_100']->id, 'destination_godown_id' => $godownVan1->id, 'destination_type' => 'delivery_van', 'batch_no' => 'BAT-KF-PCK-261018', 'quantity' => 200.0],
            ['item_id' => $itemsCatalog['fnd_chicken_100']->id, 'destination_godown_id' => $godownCalicut->id, 'destination_type' => 'branch_warehouse', 'batch_no' => 'BAT-KF-PCK-261018', 'quantity' => 150.0],
        ]);

        // 19. Operating Expenses
        $this->recordExpenseVoucher($company, $branchKochi, $ledgers['rentLedger'], $ledgers['bankLedger'], 140000.00, '2026-09-01', 'September Puthencruz Mill & Processing Facility Lease');
        $this->recordExpenseVoucher($company, $branchKochi, $ledgers['rentLedger'], $ledgers['bankLedger'], 140000.00, '2026-10-01', 'October Puthencruz Mill & Processing Facility Lease');
        $this->recordExpenseVoucher($company, $branchKochi, $ledgers['utilityLedger'], $ledgers['bankLedger'], 68000.00, '2026-10-08', 'KSEB Industrial Heavy Milling High-Tension Power Bill');
        $this->recordExpenseVoucher($company, $branchKochi, $ledgers['freightLedger'], $ledgers['bankLedger'], 42000.00, '2026-10-15', 'Inter-Branch Stock Transfer Freight to Calicut & Thrissur Depots');

        // 20. CRM Leads
        CrmLead::firstOrCreate(['company_id' => $company->id, 'name' => 'Gulf Food Supermarkets LLC'], [
            'company_name' => 'Gulf Food Supermarkets',
            'phone' => '+971 4 332 9988',
            'email' => 'import@gulffood.example.com',
            'source' => 'Cochin Spices Export Expo 2026',
            'status' => 'Qualified',
            'notes' => 'Bulk private label inquiry for 5 containers of curry powders.',
        ]);

        CrmLead::firstOrCreate(['company_id' => $company->id, 'name' => 'Udupi Hotels Association'], [
            'company_name' => 'Udupi Hotel Canteens',
            'phone' => '+91 9845221199',
            'email' => 'udupi.caterers@example.com',
            'source' => 'Trade Referral',
            'status' => 'Contacted',
            'notes' => 'Wholesale sambar powder and rasam powder annual tender.',
        ]);

        // 21. User Accounts
        $compAdminRole = Role::where('slug', 'company-admin')->first();
        $accountantRole = Role::where('slug', 'accounts-manager')->first();

        $compAdminUser = User::firstOrCreate(['email' => 'admin@kanchinadfoods.com'], [
            'name' => 'Ramakrishnan Nair (Kanchinad Admin)',
            'password' => Hash::make('password123'),
            'company_id' => $company->id,
            'branch_id' => $branchKochi->id,
            'role_id' => $compAdminRole?->id,
            'phone' => '+91 9847112233',
            'is_active' => true,
        ]);
        $compAdminUser->companies()->syncWithoutDetaching([$company->id => ['branch_id' => $branchKochi->id]]);

        $accountsUser = User::firstOrCreate(['email' => 'accounts@kanchinadfoods.com'], [
            'name' => 'Jayakumar Pillai (Kanchinad Accountant)',
            'password' => Hash::make('password123'),
            'company_id' => $company->id,
            'branch_id' => $branchKochi->id,
            'role_id' => $accountantRole?->id,
            'phone' => '+91 9847223344',
            'is_active' => true,
        ]);
        $accountsUser->companies()->syncWithoutDetaching([$company->id => ['branch_id' => $branchKochi->id]]);

        // Attach Super Admin
        $superAdmin = User::where('email', 'admin@erp.com')->first();
        if ($superAdmin) {
            $superAdmin->companies()->syncWithoutDetaching([$company->id => ['branch_id' => $branchKochi->id]]);
        }
    }
}

