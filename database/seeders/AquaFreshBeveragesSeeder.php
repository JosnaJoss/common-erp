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

class AquaFreshBeveragesSeeder extends BaseIndustrySeeder
{
    public function run(): void
    {
        // 1. Company Profile
        $company = $this->initCompany('AQUAFRESH-BEV', [
            'name' => 'AquaFresh Beverages',
            'registration_no' => 'U15549UP2019PTC118920',
            'gst_no' => '09AABCA4455N1Z1',
            'pan_no' => 'AABCA4455N',
            'address' => 'Plot 88, Ecotech Extension, Greater Noida, Uttar Pradesh 201306',
            'phone' => '+91 120 297 3344',
            'email' => 'contact@aquafreshbev.com',
            'website' => 'https://aquafreshbev.example.com',
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
            'Axis Bank Ltd (Current A/c 92102005432198)',
            '92102005432198',
            'UTIB0000210'
        );

        // 3. Branches
        $branchPlant = Branch::firstOrCreate(['company_id' => $company->id, 'code' => 'AQ-PLT-01'], [
            'name' => 'Greater Noida RO & Beverage Bottling Plant',
            'address' => 'Plot 88, Ecotech Extension',
            'city' => 'Greater Noida',
            'state' => 'Uttar Pradesh',
            'postal_code' => '201306',
            'contact_number' => '+91 120 297 3345',
            'email' => 'plant@aquafreshbev.com',
            'branch_manager' => 'Sanjay Mathur',
            'is_main_branch' => true,
            'status' => 'active',
        ]);

        $branchDelhi = Branch::firstOrCreate(['company_id' => $company->id, 'code' => 'AQ-DEL-02'], [
            'name' => 'Delhi South Logistics & Can Depot',
            'address' => 'Plot 45, Okhla Industrial Area Phase II',
            'city' => 'New Delhi',
            'state' => 'Delhi',
            'postal_code' => '110020',
            'contact_number' => '+91 11 4160 8822',
            'email' => 'delhi@aquafreshbev.com',
            'branch_manager' => 'Harish Chander Rawat',
            'is_main_branch' => false,
            'status' => 'active',
        ]);

        $branchGurgaon = Branch::firstOrCreate(['company_id' => $company->id, 'code' => 'AQ-GGN-03'], [
            'name' => 'Gurgaon Corporate Supply Depot',
            'address' => 'Udyog Vihar Phase IV',
            'city' => 'Gurgaon',
            'state' => 'Haryana',
            'postal_code' => '122016',
            'contact_number' => '+91 124 405 7711',
            'email' => 'gurgaon@aquafreshbev.com',
            'branch_manager' => 'Deepak Ahlawat',
            'is_main_branch' => false,
            'status' => 'active',
        ]);

        // 4. Godowns
        $godownRaw = Godown::firstOrCreate(['company_id' => $company->id, 'code' => 'WH-AQ-RAW'], [
            'branch_id' => $branchPlant->id,
            'name' => 'Water Treatment & Ingredients Storage',
            'address' => 'Bldg 1, Ecotech Plant',
            'postal_code' => '201306',
            'responsible_staff' => 'Mahesh Pandey',
            'status' => 'active',
        ]);

        $godownPkg = Godown::firstOrCreate(['company_id' => $company->id, 'code' => 'WH-AQ-PKG'], [
            'branch_id' => $branchPlant->id,
            'name' => 'PET Preforms & Packaging Depot',
            'address' => 'Bldg 2, Ecotech Plant',
            'postal_code' => '201306',
            'responsible_staff' => 'Vinod Mehra',
            'status' => 'active',
        ]);

        $godownFinished = Godown::firstOrCreate(['company_id' => $company->id, 'code' => 'WH-AQ-FND'], [
            'branch_id' => $branchPlant->id,
            'name' => 'Finished Bottled Beverages Warehouse',
            'address' => 'Dispatch Bay, Ecotech Plant',
            'postal_code' => '201306',
            'responsible_staff' => 'Kuldeep Singh',
            'status' => 'active',
        ]);

        $godownCans = Godown::firstOrCreate(['company_id' => $company->id, 'code' => 'WH-AQ-20L'], [
            'branch_id' => $branchDelhi->id,
            'name' => '20L Commercial Can Refill & Exchange Yard',
            'address' => 'Okhla Phase 2 Depot',
            'postal_code' => '110020',
            'responsible_staff' => 'Rakesh Tyagi',
            'status' => 'active',
        ]);

        // 5. Departments
        $deptRO = Department::firstOrCreate(['company_id' => $company->id, 'code' => 'DEPT-AQ-RO'], [
            'branch_id' => $branchPlant->id, 'name' => 'Water Purification & RO Treatment', 'department_head' => 'Er. Naveen Bhatnagar', 'status' => 'active',
        ]);
        $deptBottling = Department::firstOrCreate(['company_id' => $company->id, 'code' => 'DEPT-AQ-BTL'], [
            'branch_id' => $branchPlant->id, 'name' => 'Automated Blow Moulding & Bottling', 'department_head' => 'Pramod Kushwaha', 'status' => 'active',
        ]);
        $deptSyrup = Department::firstOrCreate(['company_id' => $company->id, 'code' => 'DEPT-AQ-SYR'], [
            'branch_id' => $branchPlant->id, 'name' => 'Beverage Formulation & Syrup Room', 'department_head' => 'Dr. Ananya Sen', 'status' => 'active',
        ]);
        $deptQA = Department::firstOrCreate(['company_id' => $company->id, 'code' => 'DEPT-AQ-QA'], [
            'branch_id' => $branchPlant->id, 'name' => 'Quality Assurance & Micro Lab', 'department_head' => 'Dr. Rajiv Srivastava', 'status' => 'active',
        ]);
        $deptLog = Department::firstOrCreate(['company_id' => $company->id, 'code' => 'DEPT-AQ-LOG'], [
            'branch_id' => $branchPlant->id, 'name' => 'Route Distribution & Fleet Logistics', 'department_head' => 'Sartaj Khan', 'status' => 'active',
        ]);
        $deptAcc = Department::firstOrCreate(['company_id' => $company->id, 'code' => 'DEPT-AQ-ACC'], [
            'branch_id' => $branchPlant->id, 'name' => 'Accounts & Commercial Billing', 'department_head' => 'Amitabh Saxena', 'status' => 'active',
        ]);

        // 6. Territory, Cities, Locations & Vehicles
        $cityGrNoida = City::firstOrCreate(['company_id' => $company->id, 'name' => 'Greater Noida'], [
            'branch_id' => $branchPlant->id, 'status' => 'active',
        ]);
        $cityDelhi = City::firstOrCreate(['company_id' => $company->id, 'name' => 'New Delhi'], [
            'branch_id' => $branchDelhi->id, 'status' => 'active',
        ]);
        $cityGurgaon = City::firstOrCreate(['company_id' => $company->id, 'name' => 'Gurgaon'], [
            'branch_id' => $branchGurgaon->id, 'status' => 'active',
        ]);

        $locOkhla = Location::firstOrCreate(['company_id' => $company->id, 'city_id' => $cityDelhi->id, 'name' => 'Okhla Phase 2'], [
            'pincode' => '110020', 'status' => 'active',
        ]);
        $locCP = Location::firstOrCreate(['company_id' => $company->id, 'city_id' => $cityDelhi->id, 'name' => 'Connaught Place'], [
            'pincode' => '110001', 'status' => 'active',
        ]);
        $locCyber = Location::firstOrCreate(['company_id' => $company->id, 'city_id' => $cityGurgaon->id, 'name' => 'DLF Cyber City'], [
            'pincode' => '122002', 'status' => 'active',
        ]);

        $vehicle1 = Vehicle::firstOrCreate(['company_id' => $company->id, 'registration_number' => 'UP-16-AQ-8800'], [
            'branch_id' => $branchPlant->id,
            'vehicle_name' => 'Tata 709 Heavy Water Delivery Truck',
            'vehicle_type' => 'Truck',
            'capacity' => 6000.00,
            'assigned_driver' => 'Ram Vilas Paswan',
            'status' => 'active',
        ]);

        $vehicle2 = Vehicle::firstOrCreate(['company_id' => $company->id, 'registration_number' => 'DL-1L-AQ-9900'], [
            'branch_id' => $branchDelhi->id,
            'vehicle_name' => 'Mahindra Bolero Maxi Can Carrier',
            'vehicle_type' => 'Tempo',
            'capacity' => 1800.00,
            'assigned_driver' => 'Balbir Singh',
            'status' => 'active',
        ]);

        $route1 = Route::firstOrCreate(['company_id' => $company->id, 'route_name' => 'Delhi Corporate 20L Can Daily Route'], [
            'branch_id' => $branchDelhi->id,
            'vehicle_id' => $vehicle2->id,
            'assigned_driver' => 'Balbir Singh',
            'status' => 'active',
        ]);
        $route1->cities()->syncWithoutDetaching([$cityDelhi->id]);
        $route1->locations()->syncWithoutDetaching([$locOkhla->id, $locCP->id]);

        // 7. Units of Measure
        $uLtr = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'LTR'], [
            'name' => 'Litre', 'unit_type' => 'volume', 'conversion_factor' => 1, 'status' => 'active',
        ]);
        $uBtl = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'BTL'], [
            'name' => 'Bottle', 'unit_type' => 'quantity', 'conversion_factor' => 1, 'status' => 'active',
        ]);
        $uCan = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'CAN'], [
            'name' => '20L Water Dispenser Jar', 'unit_type' => 'quantity', 'conversion_factor' => 20, 'status' => 'active',
        ]);
        $uCrate = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'CRATE'], [
            'name' => 'Crate (24 Bottles)', 'unit_type' => 'quantity', 'conversion_factor' => 24, 'status' => 'active',
        ]);
        $uKg = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'KG'], [
            'name' => 'Kilogram', 'unit_type' => 'weight', 'conversion_factor' => 1, 'status' => 'active',
        ]);
        $uPcs = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'PCS'], [
            'name' => 'Pieces / Caps', 'unit_type' => 'quantity', 'conversion_factor' => 1, 'status' => 'active',
        ]);

        // 8. Categories & Subcategories
        $catPack = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Packaging Materials & PET'], [
            'description' => 'Preforms, caps, bottles, labels and 20L polycarbonate jars', 'status' => 'active',
        ]);
        $subPreforms = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catPack->id, 'name' => 'PET Preforms & Bottles'], [
            'description' => 'Injection moulded PET preforms for 500ml, 1L, 2L bottles', 'status' => 'active',
        ]);
        $subCaps = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catPack->id, 'name' => 'Closures, Caps & Seals'], [
            'description' => '28mm short neck caps, 55mm non-spill can caps and labels', 'status' => 'active',
        ]);

        $catWaterTrt = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Purification & Ingredients'], [
            'description' => 'Mineral blends, CO2, flavour emulsions and filtration media', 'status' => 'active',
        ]);
        $subMinerals = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catWaterTrt->id, 'name' => 'Minerals & Carbonation'], [
            'description' => 'Food grade minerals, liquid carbon dioxide, filters', 'status' => 'active',
        ]);
        $subFlavours = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catWaterTrt->id, 'name' => 'Syrups & Flavour Emulsions'], [
            'description' => 'Lemon, orange, cola concentrates and invert sugar', 'status' => 'active',
        ]);

        $catWater = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Packaged Drinking Water'], [
            'description' => 'Purified, ozonated, and mineral-fortified drinking water', 'status' => 'active',
        ]);
        $subRetailWater = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catWater->id, 'name' => 'Retail Packaged Water'], [
            'description' => '250ml, 500ml, 1L, and 2L PET bottled water', 'status' => 'active',
        ]);
        $subBulkWater = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catWater->id, 'name' => 'Bulk Institutional Water'], [
            'description' => '20 Litre returnable dispenser water cans', 'status' => 'active',
        ]);

        $catBeverage = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Carbonated Beverages & Sodas'], [
            'description' => 'Sparkling club sodas and carbonated fruit beverages', 'status' => 'active',
        ]);
        $subClubSoda = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catBeverage->id, 'name' => 'Sparkling Club Sodas'], [
            'description' => 'Extra high-pressure carbonated soda water', 'status' => 'active',
        ]);
        $subFruitFizz = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catBeverage->id, 'name' => 'Flavoured Fruit Fizzes'], [
            'description' => 'Lemon spark, zesty orange, classic cola, and masala jeera', 'status' => 'active',
        ]);

        // 9. Products & Items (26 Items: Raw Materials, Packaging & Finished Goods)
        $itemsCatalog = [];

        // Raw & Packaging Materials
        $itemsCatalog['preform_20g'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-PRE-20G'], [
            'category_id' => $catPack->id, 'subcategory_id' => $subPreforms->id, 'unit_id' => $uPcs->id,
            'name' => 'PET Preform 20g (for 500ml Bottle)', 'item_type' => 'raw_material', 'weight' => 0.020,
            'min_price' => 1.20, 'normal_price' => 1.50, 'max_price' => 1.80, 'gst_percentage' => 18.00, 'hsn_code' => '3923', 'reorder_quantity' => 10000, 'status' => 'active',
        ]);
        $itemsCatalog['preform_32g'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-PRE-32G'], [
            'category_id' => $catPack->id, 'subcategory_id' => $subPreforms->id, 'unit_id' => $uPcs->id,
            'name' => 'PET Preform 32g (for 1L Bottle)', 'item_type' => 'raw_material', 'weight' => 0.032,
            'min_price' => 1.80, 'normal_price' => 2.20, 'max_price' => 2.60, 'gst_percentage' => 18.00, 'hsn_code' => '3923', 'reorder_quantity' => 10000, 'status' => 'active',
        ]);
        $itemsCatalog['jar_20l_empty'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-JAR-20L'], [
            'category_id' => $catPack->id, 'subcategory_id' => $subPreforms->id, 'unit_id' => $uPcs->id,
            'name' => 'Heavy Duty 20L Polycarbonate Water Jar', 'item_type' => 'raw_material', 'weight' => 0.850,
            'min_price' => 135.00, 'normal_price' => 165.00, 'max_price' => 190.00, 'gst_percentage' => 18.00, 'hsn_code' => '3923', 'reorder_quantity' => 500, 'status' => 'active',
        ]);
        $itemsCatalog['cap_28mm'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-CAP-28MM'], [
            'category_id' => $catPack->id, 'subcategory_id' => $subCaps->id, 'unit_id' => $uPcs->id,
            'name' => '28mm Short Neck HDPE Bottle Cap', 'item_type' => 'raw_material', 'weight' => 0.002,
            'min_price' => 0.35, 'normal_price' => 0.50, 'max_price' => 0.65, 'gst_percentage' => 18.00, 'hsn_code' => '3923', 'reorder_quantity' => 15000, 'status' => 'active',
        ]);
        $itemsCatalog['cap_55mm'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-CAP-55MM'], [
            'category_id' => $catPack->id, 'subcategory_id' => $subCaps->id, 'unit_id' => $uPcs->id,
            'name' => '55mm Spill-Proof 20L Dispenser Cap', 'item_type' => 'raw_material', 'weight' => 0.012,
            'min_price' => 1.80, 'normal_price' => 2.40, 'max_price' => 2.90, 'gst_percentage' => 18.00, 'hsn_code' => '3923', 'reorder_quantity' => 3000, 'status' => 'active',
        ]);
        $itemsCatalog['label_1l'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-LBL-1L'], [
            'category_id' => $catPack->id, 'subcategory_id' => $subCaps->id, 'unit_id' => $uPcs->id,
            'name' => 'BOPP Printed Shrink Wrap Label Roll 1L', 'item_type' => 'raw_material', 'weight' => 0.001,
            'min_price' => 0.20, 'normal_price' => 0.32, 'max_price' => 0.45, 'gst_percentage' => 18.00, 'hsn_code' => '3919', 'reorder_quantity' => 20000, 'status' => 'active',
        ]);
        $itemsCatalog['co2_gas'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-ING-CO2'], [
            'category_id' => $catWaterTrt->id, 'subcategory_id' => $subMinerals->id, 'unit_id' => $uKg->id,
            'name' => 'Liquid Food Grade Carbon Dioxide (CO2)', 'item_type' => 'raw_material', 'weight' => 1.000,
            'min_price' => 25.00, 'normal_price' => 32.00, 'max_price' => 38.00, 'gst_percentage' => 18.00, 'hsn_code' => '2811', 'reorder_quantity' => 1000, 'status' => 'active',
        ]);
        $itemsCatalog['minerals_salt'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-ING-MIN'], [
            'category_id' => $catWaterTrt->id, 'subcategory_id' => $subMinerals->id, 'unit_id' => $uKg->id,
            'name' => 'Mineral Fortification Salt Blend (Mg, K, Na)', 'item_type' => 'raw_material', 'weight' => 1.000,
            'min_price' => 150.00, 'normal_price' => 185.00, 'max_price' => 210.00, 'gst_percentage' => 18.00, 'hsn_code' => '2501', 'reorder_quantity' => 200, 'status' => 'active',
        ]);
        $itemsCatalog['lemon_flavour'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-ING-LMN'], [
            'category_id' => $catWaterTrt->id, 'subcategory_id' => $subFlavours->id, 'unit_id' => $uKg->id,
            'name' => 'Natural Lemon Beverage Emulsion Concentrate', 'item_type' => 'raw_material', 'weight' => 1.000,
            'min_price' => 650.00, 'normal_price' => 780.00, 'max_price' => 880.00, 'gst_percentage' => 18.00, 'hsn_code' => '3302', 'reorder_quantity' => 80, 'status' => 'active',
        ]);
        $itemsCatalog['sugar_syrup'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-ING-SGR'], [
            'category_id' => $catWaterTrt->id, 'subcategory_id' => $subFlavours->id, 'unit_id' => $uKg->id,
            'name' => 'Refined Liquid Invert Sugar Syrup 70% Brix', 'item_type' => 'raw_material', 'weight' => 1.000,
            'min_price' => 45.00, 'normal_price' => 54.00, 'max_price' => 62.00, 'gst_percentage' => 5.00, 'hsn_code' => '1702', 'reorder_quantity' => 2000, 'status' => 'active',
        ]);
        $itemsCatalog['ro_filter'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FLT-5MIC'], [
            'category_id' => $catWaterTrt->id, 'subcategory_id' => $subMinerals->id, 'unit_id' => $uPcs->id,
            'name' => '5 Micron Polypropylene RO Cartridge Filter', 'item_type' => 'raw_material', 'weight' => 0.400,
            'min_price' => 180.00, 'normal_price' => 240.00, 'max_price' => 280.00, 'gst_percentage' => 18.00, 'hsn_code' => '8421', 'reorder_quantity' => 50, 'status' => 'active',
        ]);

        // Finished Products (Packaged Water & Carbonated Beverages)
        $pWater1L = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-AQ-WTR-1L'], [
            'category_id' => $catWater->id, 'subcategory_id' => $subRetailWater->id,
            'name' => 'AquaFresh Pure Mineral Water 1 Litre', 'description' => 'Reverse osmosis purified with added essential minerals', 'status' => 'active',
        ]);
        $itemsCatalog['fnd_water_1l'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-WTR-1L'], [
            'category_id' => $catWater->id, 'subcategory_id' => $subRetailWater->id, 'product_id' => $pWater1L->id, 'unit_id' => $uBtl->id,
            'name' => 'AquaFresh Pure Mineral Water 1 Litre', 'item_type' => 'sales_item', 'weight' => 1.03,
            'min_price' => 12.00, 'normal_price' => 16.00, 'max_price' => 20.00, 'gst_percentage' => 18.00, 'hsn_code' => '2201', 'reorder_quantity' => 1500, 'status' => 'active',
        ]);

        $pWater500 = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-AQ-WTR-500'], [
            'category_id' => $catWater->id, 'subcategory_id' => $subRetailWater->id,
            'name' => 'AquaFresh Packaged Drinking Water 500ml', 'description' => 'Compact grab-and-go mineral water bottle', 'status' => 'active',
        ]);
        $itemsCatalog['fnd_water_500'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-WTR-500'], [
            'category_id' => $catWater->id, 'subcategory_id' => $subRetailWater->id, 'product_id' => $pWater500->id, 'unit_id' => $uBtl->id,
            'name' => 'AquaFresh Drinking Water 500ml', 'item_type' => 'sales_item', 'weight' => 0.52,
            'min_price' => 6.50, 'normal_price' => 8.50, 'max_price' => 10.00, 'gst_percentage' => 18.00, 'hsn_code' => '2201', 'reorder_quantity' => 2500, 'status' => 'active',
        ]);

        $pWater20L = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-AQ-CAN-20L'], [
            'category_id' => $catWater->id, 'subcategory_id' => $subBulkWater->id,
            'name' => 'AquaFresh 20-Litre Pure Water Dispenser Can', 'description' => 'Commercial and home water dispenser refill jar', 'status' => 'active',
        ]);
        $itemsCatalog['fnd_can_20l'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-CAN-20L'], [
            'category_id' => $catWater->id, 'subcategory_id' => $subBulkWater->id, 'product_id' => $pWater20L->id, 'unit_id' => $uCan->id,
            'name' => 'AquaFresh 20-Litre Water Dispenser Can', 'item_type' => 'sales_item', 'weight' => 20.85,
            'min_price' => 60.00, 'normal_price' => 80.00, 'max_price' => 95.00, 'gst_percentage' => 18.00, 'hsn_code' => '2201', 'reorder_quantity' => 300, 'status' => 'active',
        ]);

        $pWater250 = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-AQ-WTR-250'], [
            'category_id' => $catWater->id, 'subcategory_id' => $subRetailWater->id,
            'name' => 'AquaFresh Party Pack Water 250ml', 'description' => 'Meeting and conference cup bottle', 'status' => 'active',
        ]);
        $itemsCatalog['fnd_water_250'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-WTR-250'], [
            'category_id' => $catWater->id, 'subcategory_id' => $subRetailWater->id, 'product_id' => $pWater250->id, 'unit_id' => $uBtl->id,
            'name' => 'AquaFresh Party Pack Water 250ml', 'item_type' => 'sales_item', 'weight' => 0.27,
            'min_price' => 4.00, 'normal_price' => 5.50, 'max_price' => 6.00, 'gst_percentage' => 18.00, 'hsn_code' => '2201', 'reorder_quantity' => 2000, 'status' => 'active',
        ]);

        $pWater2L = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-AQ-WTR-2L'], [
            'category_id' => $catWater->id, 'subcategory_id' => $subRetailWater->id,
            'name' => 'AquaFresh Family Pack Water 2 Litre', 'description' => 'Large travel and home mineral water bottle', 'status' => 'active',
        ]);
        $itemsCatalog['fnd_water_2l'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-WTR-2L'], [
            'category_id' => $catWater->id, 'subcategory_id' => $subRetailWater->id, 'product_id' => $pWater2L->id, 'unit_id' => $uBtl->id,
            'name' => 'AquaFresh Family Pack Water 2 Litre', 'item_type' => 'sales_item', 'weight' => 2.05,
            'min_price' => 22.00, 'normal_price' => 28.00, 'max_price' => 35.00, 'gst_percentage' => 18.00, 'hsn_code' => '2201', 'reorder_quantity' => 1000, 'status' => 'active',
        ]);

        $pAlkaline = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-AQ-ALK-1L'], [
            'category_id' => $catWater->id, 'subcategory_id' => $subRetailWater->id,
            'name' => 'AquaFresh Premium Alkaline Water 1L (pH 8.5+)', 'description' => 'Ionized alkaline hydration water with enhanced electrolytes', 'status' => 'active',
        ]);
        $itemsCatalog['fnd_alkaline_1l'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-ALK-1L'], [
            'category_id' => $catWater->id, 'subcategory_id' => $subRetailWater->id, 'product_id' => $pAlkaline->id, 'unit_id' => $uBtl->id,
            'name' => 'AquaFresh Premium Alkaline Water 1L', 'item_type' => 'sales_item', 'weight' => 1.04,
            'min_price' => 45.00, 'normal_price' => 60.00, 'max_price' => 75.00, 'gst_percentage' => 18.00, 'hsn_code' => '2201', 'reorder_quantity' => 400, 'status' => 'active',
        ]);

        // Carbonated Sodas & Beverages
        $pSoda750 = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-AQ-SDA-750'], [
            'category_id' => $catBeverage->id, 'subcategory_id' => $subClubSoda->id,
            'name' => 'AquaFresh Sparkling Club Soda 750ml', 'description' => 'Crisp high fizz sparkling club soda', 'status' => 'active',
        ]);
        $itemsCatalog['fnd_soda_750'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-SDA-750'], [
            'category_id' => $catBeverage->id, 'subcategory_id' => $subClubSoda->id, 'product_id' => $pSoda750->id, 'unit_id' => $uBtl->id,
            'name' => 'AquaFresh Sparkling Club Soda 750ml', 'item_type' => 'sales_item', 'weight' => 0.78,
            'min_price' => 15.00, 'normal_price' => 20.00, 'max_price' => 25.00, 'gst_percentage' => 28.00, 'hsn_code' => '2202', 'reorder_quantity' => 1200, 'status' => 'active',
        ]);

        $pSoda500 = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-AQ-SDA-500'], [
            'category_id' => $catBeverage->id, 'subcategory_id' => $subClubSoda->id,
            'name' => 'AquaFresh Extra Fizz Club Soda 500ml', 'description' => 'Heavy carbonation premium soda water', 'status' => 'active',
        ]);
        $itemsCatalog['fnd_soda_500'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-SDA-500'], [
            'category_id' => $catBeverage->id, 'subcategory_id' => $subClubSoda->id, 'product_id' => $pSoda500->id, 'unit_id' => $uBtl->id,
            'name' => 'AquaFresh Extra Fizz Soda 500ml', 'item_type' => 'sales_item', 'weight' => 0.53,
            'min_price' => 11.00, 'normal_price' => 15.00, 'max_price' => 18.00, 'gst_percentage' => 28.00, 'hsn_code' => '2202', 'reorder_quantity' => 1500, 'status' => 'active',
        ]);

        $pLemonFizz = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-AQ-LMN-500'], [
            'category_id' => $catBeverage->id, 'subcategory_id' => $subFruitFizz->id,
            'name' => 'AquaFresh Lemon Spark Fruit Fizz 500ml', 'description' => 'Tangy real lemon juice based sparkling fruit beverage', 'status' => 'active',
        ]);
        $itemsCatalog['fnd_lemon_500'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-LMN-500'], [
            'category_id' => $catBeverage->id, 'subcategory_id' => $subFruitFizz->id, 'product_id' => $pLemonFizz->id, 'unit_id' => $uBtl->id,
            'name' => 'AquaFresh Lemon Spark Fizz 500ml', 'item_type' => 'sales_item', 'weight' => 0.54,
            'min_price' => 24.00, 'normal_price' => 32.00, 'max_price' => 38.00, 'gst_percentage' => 28.00, 'hsn_code' => '2202', 'reorder_quantity' => 800, 'status' => 'active',
        ]);

        $pOrangeFizz = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-AQ-ORG-500'], [
            'category_id' => $catBeverage->id, 'subcategory_id' => $subFruitFizz->id,
            'name' => 'AquaFresh Zesty Orange Fizz 500ml', 'description' => 'Refreshing citrus sparkling beverage', 'status' => 'active',
        ]);
        $itemsCatalog['fnd_orange_500'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-ORG-500'], [
            'category_id' => $catBeverage->id, 'subcategory_id' => $subFruitFizz->id, 'product_id' => $pOrangeFizz->id, 'unit_id' => $uBtl->id,
            'name' => 'AquaFresh Zesty Orange Fizz 500ml', 'item_type' => 'sales_item', 'weight' => 0.54,
            'min_price' => 24.00, 'normal_price' => 32.00, 'max_price' => 38.00, 'gst_percentage' => 28.00, 'hsn_code' => '2202', 'reorder_quantity' => 800, 'status' => 'active',
        ]);

        $pCola = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-AQ-COL-500'], [
            'category_id' => $catBeverage->id, 'subcategory_id' => $subFruitFizz->id,
            'name' => 'AquaFresh Classic Cola Chill 500ml', 'description' => 'Classic caramel cola sparkling soda', 'status' => 'active',
        ]);
        $itemsCatalog['fnd_cola_500'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-COL-500'], [
            'category_id' => $catBeverage->id, 'subcategory_id' => $subFruitFizz->id, 'product_id' => $pCola->id, 'unit_id' => $uBtl->id,
            'name' => 'AquaFresh Classic Cola Chill 500ml', 'item_type' => 'sales_item', 'weight' => 0.54,
            'min_price' => 24.00, 'normal_price' => 32.00, 'max_price' => 38.00, 'gst_percentage' => 28.00, 'hsn_code' => '2202', 'reorder_quantity' => 800, 'status' => 'active',
        ]);

        $pJeera = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-AQ-JRA-300'], [
            'category_id' => $catBeverage->id, 'subcategory_id' => $subFruitFizz->id,
            'name' => 'AquaFresh Masala Jeera Digest Soda 300ml', 'description' => 'Digestive cumin roasted spiced soda', 'status' => 'active',
        ]);
        $itemsCatalog['fnd_jeera_300'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-JRA-300'], [
            'category_id' => $catBeverage->id, 'subcategory_id' => $subFruitFizz->id, 'product_id' => $pJeera->id, 'unit_id' => $uBtl->id,
            'name' => 'AquaFresh Masala Jeera Soda 300ml', 'item_type' => 'sales_item', 'weight' => 0.33,
            'min_price' => 14.00, 'normal_price' => 18.00, 'max_price' => 20.00, 'gst_percentage' => 28.00, 'hsn_code' => '2202', 'reorder_quantity' => 1000, 'status' => 'active',
        ]);

        $itemsCatalog['crate_water_1l'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-CRT-WTR-1L'], [
            'category_id' => $catWater->id, 'subcategory_id' => $subRetailWater->id, 'unit_id' => $uCrate->id,
            'name' => 'AquaFresh 1L Bottled Water Crate (Pack of 12)', 'item_type' => 'sales_item', 'weight' => 12.50,
            'min_price' => 135.00, 'normal_price' => 175.00, 'max_price' => 210.00, 'gst_percentage' => 18.00, 'hsn_code' => '2201', 'reorder_quantity' => 200, 'status' => 'active',
        ]);
        $itemsCatalog['crate_water_500'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-CRT-WTR-500'], [
            'category_id' => $catWater->id, 'subcategory_id' => $subRetailWater->id, 'unit_id' => $uCrate->id,
            'name' => 'AquaFresh 500ml Bottled Water Crate (Pack of 24)', 'item_type' => 'sales_item', 'weight' => 12.80,
            'min_price' => 145.00, 'normal_price' => 190.00, 'max_price' => 220.00, 'gst_percentage' => 18.00, 'hsn_code' => '2201', 'reorder_quantity' => 250, 'status' => 'active',
        ]);
        $itemsCatalog['emp_jar_exchange'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-EMP-JAR-20L'], [
            'category_id' => $catPack->id, 'subcategory_id' => $subPreforms->id, 'unit_id' => $uPcs->id,
            'name' => 'AquaFresh 20L Empty Can Return & Deposit', 'item_type' => 'sales_item', 'weight' => 0.85,
            'min_price' => 100.00, 'normal_price' => 150.00, 'max_price' => 150.00, 'gst_percentage' => 18.00, 'hsn_code' => '3923', 'reorder_quantity' => 200, 'status' => 'active',
        ]);

        // 10. Suppliers (7 Raw Material, Preform & Mineral Suppliers)
        $suppliers = [
            $this->createSupplierWithLedger($company, $ledgers['creditorGroup'], [
                'code' => 'SUPP-AQ-RELIANCE', 'name' => 'Reliance Polymers & PET Preforms Ltd',
                'contact_person' => 'Kapil Dev Sharma', 'phone' => '+91 22 4477 8899', 'email' => 'pet.sales@ril.example.com',
                'address' => 'Reliance Corporate Park, Ghansoli, Navi Mumbai 400701', 'pincode' => '400701', 'city' => 'Navi Mumbai',
                'gst_number' => '27AAACR7788R1Z2', 'pan_number' => 'AAACR7788R', 'opening_balance' => 85000.00,
            ]),
            $this->createSupplierWithLedger($company, $ledgers['creditorGroup'], [
                'code' => 'SUPP-AQ-BERICAP', 'name' => 'Bericap Closures & Packaging Systems Ltd',
                'contact_person' => 'Anurag Kashyap', 'phone' => '+91 20 6711 2200', 'email' => 'caps@bericap.example.com',
                'address' => 'Chakan MIDC Phase II, Pune, Maharashtra 410501', 'pincode' => '410501', 'city' => 'Pune',
                'gst_number' => '27AAACB5566B1Z3', 'pan_number' => 'AAACB5566B', 'opening_balance' => 45000.00,
            ]),
            $this->createSupplierWithLedger($company, $ledgers['creditorGroup'], [
                'code' => 'SUPP-AQ-PRAXAIR', 'name' => 'Praxair Industrial & Food Grade Gases Ltd',
                'contact_person' => 'Alok Srivastava', 'phone' => '+91 120 405 9900', 'email' => 'co2@praxair.example.com',
                'address' => 'Surajpur Industrial Area, Greater Noida 201306', 'pincode' => '201306', 'city' => 'Greater Noida',
                'gst_number' => '09AAACP4433P1Z4', 'pan_number' => 'AAACP4433P', 'opening_balance' => 55000.00,
            ]),
            $this->createSupplierWithLedger($company, $ledgers['creditorGroup'], [
                'code' => 'SUPP-AQ-HUHTAMAKI', 'name' => 'Huhtamaki Shrink Sleeves & Labels India',
                'contact_person' => 'Girish Menon', 'phone' => '+91 22 2854 1122', 'email' => 'labels@huhtamaki.example.com',
                'address' => 'Lal Bahadur Shastri Marg, Majiwada, Thane 400601', 'pincode' => '400601', 'city' => 'Thane',
                'gst_number' => '27AAACH8899H1Z5', 'pan_number' => 'AAACH8899H', 'opening_balance' => 30000.00,
            ]),
            $this->createSupplierWithLedger($company, $ledgers['creditorGroup'], [
                'code' => 'SUPP-AQ-IONEXCHANGE', 'name' => 'Ion Exchange Water Treatment Media & Resins',
                'contact_person' => 'Dr. B. K. Raman', 'phone' => '+91 22 6231 2000', 'email' => 'resins@ionexchange.example.com',
                'address' => 'Tiecicon House, Dr. E. Moses Road, Mahalaxmi, Mumbai 400011', 'pincode' => '400011', 'city' => 'Mumbai',
                'gst_number' => '27AAACI1122I1Z6', 'pan_number' => 'AAACI1122I', 'opening_balance' => 60000.00,
            ]),
            $this->createSupplierWithLedger($company, $ledgers['creditorGroup'], [
                'code' => 'SUPP-AQ-DHAMPUR', 'name' => 'Dhampur Green Invert Sugar Syrups Ltd',
                'contact_person' => 'Sudhir Goel', 'phone' => '+91 11 4166 7788', 'email' => 'syrups@dhampur.example.com',
                'address' => 'Barakhamba Road, Connaught Place, New Delhi 110001', 'pincode' => '110001', 'city' => 'New Delhi',
                'gst_number' => '07AAACD9988D1Z7', 'pan_number' => 'AAACD9988D', 'opening_balance' => 40000.00,
            ]),
            $this->createSupplierWithLedger($company, $ledgers['creditorGroup'], [
                'code' => 'SUPP-AQ-CHEMPLAST', 'name' => 'Chemplast Sanmar Mineral Compounds Ltd',
                'contact_person' => 'R. Natarajan', 'phone' => '+91 44 2812 8500', 'email' => 'minerals@chemplast.example.com',
                'address' => 'Cathedral Road, Chennai, Tamil Nadu 600086', 'pincode' => '600086', 'city' => 'Chennai',
                'gst_number' => '33AAACC2211C1Z8', 'pan_number' => 'AAACC2211C', 'opening_balance' => 35000.00,
            ]),
        ];

        // 11. Customers & Corporate Accounts (13 Corporate Offices, Hotel Chains, Gyms & Retailers)
        $customers = [
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-AQ-001', 'name' => 'DLF CyberCity Corporate Towers Facility', 'shop_name' => 'CyberCity Facilities',
                'phone' => '+91 124 456 7890', 'email' => 'facilities@cybercity.example.com', 'address' => 'Tower 10, DLF Cyber City, Gurgaon',
                'pincode' => '122002', 'city' => 'Gurgaon', 'territory' => 'Gurgaon Corporate Hub', 'price_level' => 'minimum', 'opening_balance' => 110000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-AQ-002', 'name' => 'Taj Palace & Convention Hotel', 'shop_name' => 'Taj Palace F&B Dept',
                'phone' => '+91 11 2611 0202', 'email' => 'fb.tajdelhi@example.com', 'address' => 'Sardar Patel Marg, Diplomatic Enclave, New Delhi',
                'pincode' => '110021', 'city' => 'New Delhi', 'price_level' => 'normal', 'opening_balance' => 95000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-AQ-003', 'name' => 'Golds Gym & Fitness Centres North India', 'shop_name' => 'Golds Gym NCR Hub',
                'phone' => '+91 11 4155 6677', 'email' => 'procurement@goldsgym.example.com', 'address' => 'Greater Kailash II, New Delhi',
                'pincode' => '110048', 'city' => 'New Delhi', 'price_level' => 'normal', 'opening_balance' => 65000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-AQ-004', 'name' => 'Delhi Metro Rail Retail Kiosks (DMRC)', 'shop_name' => 'DMRC Commercial Operations',
                'phone' => '+91 11 2341 7910', 'email' => 'dmrc.retail@example.com', 'address' => 'Metro Bhawan, Fire Brigade Lane, New Delhi',
                'pincode' => '110001', 'city' => 'New Delhi', 'route_id' => $route1->id, 'location_id' => $locCP->id, 'price_level' => 'minimum', 'opening_balance' => 140000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-AQ-005', 'name' => 'Infosys Development Centre Greater Noida', 'shop_name' => 'Infosys Facility Desk',
                'phone' => '+91 120 405 1100', 'email' => 'noida.facilities@infosys.example.com', 'address' => 'Plot 1, Knowledge Park III, Greater Noida',
                'pincode' => '201306', 'city' => 'Greater Noida', 'price_level' => 'minimum', 'opening_balance' => 85000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-AQ-006', 'name' => 'Marriott Executive Apartments Gurgaon', 'shop_name' => 'Marriott Gurgaon F&B',
                'phone' => '+91 124 499 2000', 'email' => 'marriott.ggn@example.com', 'address' => 'Golf Course Road, Gurgaon',
                'pincode' => '122002', 'city' => 'Gurgaon', 'price_level' => 'normal', 'opening_balance' => 75000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-AQ-007', 'name' => 'Haldiram Snacks & QSR Outlets', 'shop_name' => 'Haldirams Central Supply',
                'phone' => '+91 120 478 8800', 'email' => 'beverages@haldirams.example.com', 'address' => 'Sector 62, Noida',
                'pincode' => '201309', 'city' => 'Noida', 'price_level' => 'minimum', 'opening_balance' => 125000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-AQ-008', 'name' => 'PVR INOX Cinemas Beverage Procurement', 'shop_name' => 'PVR Concessions Desk',
                'phone' => '+91 124 470 8100', 'email' => 'cinemas.bev@pvr.example.com', 'address' => 'Building 9A, Cyber City, Gurgaon',
                'pincode' => '122002', 'city' => 'Gurgaon', 'price_level' => 'minimum', 'opening_balance' => 135000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-AQ-009', 'name' => 'Max Healthcare Hospitals Cafeteria', 'shop_name' => 'Max Saket Dietary Services',
                'phone' => '+91 11 2651 5050', 'email' => 'max.dietary@example.com', 'address' => '1, Press Enclave Road, Saket, New Delhi',
                'pincode' => '110017', 'city' => 'New Delhi', 'route_id' => $route1->id, 'location_id' => $locOkhla->id, 'price_level' => 'normal', 'opening_balance' => 70000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-AQ-010', 'name' => 'Radisson Blu Hotel Greater Noida', 'shop_name' => 'Radisson Blu Banquet',
                'phone' => '+91 120 450 0000', 'email' => 'banquets@radissongrnoida.example.com', 'address' => 'Plot C-8, Site-IV, Greater Noida',
                'pincode' => '201310', 'city' => 'Greater Noida', 'price_level' => 'normal', 'opening_balance' => 60000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-AQ-011', 'name' => 'Chaayos Tea Cafes NCR Distribution', 'shop_name' => 'Chaayos Beverage Store',
                'phone' => '+91 11 4987 6600', 'email' => 'stores@chaayos.example.com', 'address' => 'Connaught Place Outer Circle, New Delhi',
                'pincode' => '110001', 'city' => 'New Delhi', 'route_id' => $route1->id, 'location_id' => $locCP->id, 'price_level' => 'normal', 'opening_balance' => 45000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-AQ-012', 'name' => 'Blue Tokai Coffee Roasters Retail Chain', 'shop_name' => 'Blue Tokai Stores',
                'phone' => '+91 11 4123 9988', 'email' => 'retail@bluetokai.example.com', 'address' => 'Saidulajab, Saket, New Delhi',
                'pincode' => '110030', 'city' => 'New Delhi', 'price_level' => 'normal', 'opening_balance' => 35000.00,
            ]),
            $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
                'code' => 'CUST-AQ-013', 'name' => 'Okhla Provisioners & Beverage Wholesale', 'shop_name' => 'Okhla Cold Storage Mart',
                'phone' => '+91 11 2681 4455', 'email' => 'okhla.bev@example.com', 'address' => 'Phase I, Okhla Industrial Area, New Delhi',
                'pincode' => '110020', 'city' => 'New Delhi', 'route_id' => $route1->id, 'location_id' => $locOkhla->id, 'price_level' => 'minimum', 'opening_balance' => 50000.00,
            ]),
        ];

        // 12. Staff & HR (18 Employees across Plant, Microbiology & Fleet)
        $staffRoster = [
            ['first_name' => 'Sanjay', 'last_name' => 'Mathur', 'designation' => 'Director of Bottling Operations', 'dept' => $deptRO, 'salary_amount' => 95000.00, 'emp_id' => 'EMP-AQ-001', 'gender' => 'Male', 'branch' => $branchPlant],
            ['first_name' => 'Naveen', 'last_name' => 'Bhatnagar', 'designation' => 'Lead RO & Water Treatment Engineer', 'dept' => $deptRO, 'salary_amount' => 80000.00, 'emp_id' => 'EMP-AQ-002', 'gender' => 'Male', 'branch' => $branchPlant],
            ['first_name' => 'Rajiv', 'last_name' => 'Srivastava', 'designation' => 'Chief Microbiologist & QA Head', 'dept' => $deptQA, 'salary_amount' => 75000.00, 'emp_id' => 'EMP-AQ-003', 'gender' => 'Male', 'branch' => $branchPlant],
            ['first_name' => 'Pramod', 'last_name' => 'Kushwaha', 'designation' => 'Blow Moulding & Line Supervisor', 'dept' => $deptBottling, 'salary_amount' => 58000.00, 'emp_id' => 'EMP-AQ-004', 'gender' => 'Male', 'branch' => $branchPlant],
            ['first_name' => 'Ananya', 'last_name' => 'Sen', 'designation' => 'Beverage Flavour Formulator', 'dept' => $deptSyrup, 'salary_amount' => 62000.00, 'emp_id' => 'EMP-AQ-005', 'gender' => 'Female', 'branch' => $branchPlant],
            ['first_name' => 'Sartaj', 'last_name' => 'Khan', 'designation' => 'Fleet Logistics & Dispatch Head', 'dept' => $deptLog, 'salary_amount' => 60000.00, 'emp_id' => 'EMP-AQ-006', 'gender' => 'Male', 'branch' => $branchPlant],
            ['first_name' => 'Amitabh', 'last_name' => 'Saxena', 'designation' => 'Finance & Commercial Accountant', 'dept' => $deptAcc, 'salary_amount' => 65000.00, 'emp_id' => 'EMP-AQ-007', 'gender' => 'Male', 'branch' => $branchPlant],
            ['first_name' => 'Mahesh', 'last_name' => 'Pandey', 'designation' => 'Purification Chemicals Custodian', 'dept' => $deptRO, 'salary_amount' => 42000.00, 'emp_id' => 'EMP-AQ-008', 'gender' => 'Male', 'branch' => $branchPlant],
            ['first_name' => 'Vinod', 'last_name' => 'Mehra', 'designation' => 'PET Preform Storage Officer', 'dept' => $deptBottling, 'salary_amount' => 40000.00, 'emp_id' => 'EMP-AQ-009', 'gender' => 'Male', 'branch' => $branchPlant],
            ['first_name' => 'Kuldeep', 'last_name' => 'Singh', 'designation' => 'Bottled Beverage Warehouse Officer', 'dept' => $deptLog, 'salary_amount' => 45000.00, 'emp_id' => 'EMP-AQ-010', 'gender' => 'Male', 'branch' => $branchPlant],
            ['first_name' => 'Harish', 'last_name' => 'Rawat', 'designation' => 'Delhi Okhla Depot Incharge', 'dept' => $deptLog, 'salary_amount' => 54000.00, 'emp_id' => 'EMP-AQ-011', 'gender' => 'Male', 'branch' => $branchDelhi],
            ['first_name' => 'Deepak', 'last_name' => 'Ahlawat', 'designation' => 'Gurgaon Supply Lead', 'dept' => $deptLog, 'salary_amount' => 52000.00, 'emp_id' => 'EMP-AQ-012', 'gender' => 'Male', 'branch' => $branchGurgaon],
            ['first_name' => 'Rakesh', 'last_name' => 'Tyagi', 'designation' => '20L Can Washing & Refill Master', 'dept' => $deptLog, 'salary_amount' => 36000.00, 'emp_id' => 'EMP-AQ-013', 'gender' => 'Male', 'branch' => $branchDelhi],
            ['first_name' => 'Balbir', 'last_name' => 'Singh', 'designation' => 'Commercial Delivery Van Driver', 'dept' => $deptLog, 'salary_amount' => 32000.00, 'emp_id' => 'EMP-AQ-014', 'gender' => 'Male', 'branch' => $branchDelhi],
            ['first_name' => 'Ram Vilas', 'last_name' => 'Paswan', 'designation' => 'Heavy Water Truck Driver', 'dept' => $deptLog, 'salary_amount' => 34000.00, 'emp_id' => 'EMP-AQ-015', 'gender' => 'Male', 'branch' => $branchPlant],
            ['first_name' => 'Sunita', 'last_name' => 'Sharma', 'designation' => 'Water Quality Lab Analyst', 'dept' => $deptQA, 'salary_amount' => 44000.00, 'emp_id' => 'EMP-AQ-016', 'gender' => 'Female', 'branch' => $branchPlant],
            ['first_name' => 'Pooja', 'last_name' => 'Verma', 'designation' => 'Billing & Delivery Route Cashier', 'dept' => $deptAcc, 'salary_amount' => 35000.00, 'emp_id' => 'EMP-AQ-017', 'gender' => 'Female', 'branch' => $branchPlant],
            ['first_name' => 'Dharmendra', 'last_name' => 'Yadav', 'designation' => 'Carbonation & Chilling Plant Operator', 'dept' => $deptSyrup, 'salary_amount' => 38000.00, 'emp_id' => 'EMP-AQ-018', 'gender' => 'Male', 'branch' => $branchPlant],
        ];

        foreach ($staffRoster as $s) {
            $staff = $this->createStaff($company, $s['branch'], $s['dept'], [
                'employee_id' => $s['emp_id'],
                'first_name' => $s['first_name'],
                'last_name' => $s['last_name'],
                'email' => strtolower(str_replace(' ', '', $s['first_name']) . '.' . substr($s['last_name'], 0, 1)) . '@aquafreshbev.com',
                'designation' => $s['designation'],
                'salary_amount' => $s['salary_amount'],
                'gender' => $s['gender'],
            ]);
            $this->processStaffAttendanceAndPayroll($company, $staff, $ledgers);
        }

        // Hierarchy & Roles config
        $sanjayStaff = Staff::where('company_id', $company->id)->where('employee_id', 'EMP-AQ-001')->first();
        $naveenStaff = Staff::where('company_id', $company->id)->where('employee_id', 'EMP-AQ-002')->first();
        $pramodStaff = Staff::where('company_id', $company->id)->where('employee_id', 'EMP-AQ-004')->first();
        $harishStaff = Staff::where('company_id', $company->id)->where('employee_id', 'EMP-AQ-011')->first();
        $rakeshStaff = Staff::where('company_id', $company->id)->where('employee_id', 'EMP-AQ-013')->first();

        if ($sanjayStaff) {
            $sanjayStaff->update(['role_position' => 'department_head']);
            $deptRO->update(['department_type' => 'production', 'head_staff_id' => $sanjayStaff->id]);
        }
        if ($naveenStaff) {
            $naveenStaff->update(['role_position' => 'supervisor']);
            $deptRO->update(['supervisor_staff_id' => $naveenStaff->id]);
        }
        if ($pramodStaff) {
            $pramodStaff->update(['role_position' => 'supervisor']);
            $deptBottling->update(['department_type' => 'production', 'supervisor_staff_id' => $pramodStaff->id]);
        }
        if ($harishStaff) {
            $harishStaff->update(['role_position' => 'supervisor']);
            $deptLog->update(['department_type' => 'sales', 'supervisor_staff_id' => $harishStaff->id]);
        }
        if ($rakeshStaff && $harishStaff) {
            $rakeshStaff->update([
                'role_position' => 'worker',
                'employee_type' => 'daily_wage',
                'hourly_rate' => 85.00,
                'supervisor_id' => $harishStaff->id,
            ]);
        }

        // Shifts & Holidays
        Shift::firstOrCreate(['company_id' => $company->id, 'shift_name' => 'RO Bottling Shift (07:00 - 15:30)'], [
            'branch_id' => $branchPlant->id, 'start_time' => '07:00:00', 'end_time' => '15:30:00', 'break_duration_minutes' => 45, 'status' => 'active',
        ]);
        Holiday::firstOrCreate(['company_id' => $company->id, 'holiday_name' => 'Gandhi Jayanti'], [
            'branch_id' => $branchPlant->id, 'holiday_date' => '2026-10-02', 'holiday_type' => 'National', 'description' => 'Mahatma Gandhi Birthday',
        ]);

        // 13. Opening Stock for Preforms, Caps, Jars, Minerals & Finished Waters
        $this->recordOpeningStock($company, $branchPlant, $godownPkg, $itemsCatalog['preform_32g'], 30000, 2.00, 'BATCH-PRE-32-OPN');
        $this->recordOpeningStock($company, $branchPlant, $godownPkg, $itemsCatalog['preform_20g'], 40000, 1.40, 'BATCH-PRE-20-OPN');
        $this->recordOpeningStock($company, $branchPlant, $godownPkg, $itemsCatalog['cap_28mm'], 80000, 0.45, 'BATCH-CAP-28-OPN');
        $this->recordOpeningStock($company, $branchPlant, $godownPkg, $itemsCatalog['cap_55mm'], 10000, 2.20, 'BATCH-CAP-55-OPN');
        $this->recordOpeningStock($company, $branchPlant, $godownPkg, $itemsCatalog['label_1l'], 50000, 0.30, 'BATCH-LBL-1L-OPN');
        $this->recordOpeningStock($company, $branchDelhi, $godownCans, $itemsCatalog['jar_20l_empty'], 1500, 150.00, 'BATCH-JAR-20L-OPN');

        $this->recordOpeningStock($company, $branchPlant, $godownRaw, $itemsCatalog['co2_gas'], 2500, 30.00, 'BATCH-CO2-OPN');
        $this->recordOpeningStock($company, $branchPlant, $godownRaw, $itemsCatalog['minerals_salt'], 800, 175.00, 'BATCH-MIN-OPN');
        $this->recordOpeningStock($company, $branchPlant, $godownRaw, $itemsCatalog['sugar_syrup'], 3500, 50.00, 'BATCH-SGR-OPN');
        $this->recordOpeningStock($company, $branchPlant, $godownRaw, $itemsCatalog['lemon_flavour'], 150, 750.00, 'BATCH-LMN-OPN');

        // Finished Goods in Warehouse
        $this->recordOpeningStock($company, $branchPlant, $godownFinished, $itemsCatalog['fnd_water_1l'], 4000, 10.00, 'BATCH-AQ-FND-OPN');
        $this->recordOpeningStock($company, $branchPlant, $godownFinished, $itemsCatalog['fnd_water_500'], 6000, 5.50, 'BATCH-AQ-FND-OPN');
        $this->recordOpeningStock($company, $branchDelhi, $godownCans, $itemsCatalog['fnd_can_20l'], 1200, 45.00, 'BATCH-AQ-CAN-OPN');
        $this->recordOpeningStock($company, $branchPlant, $godownFinished, $itemsCatalog['fnd_soda_750'], 2500, 12.00, 'BATCH-AQ-FND-OPN');
        $this->recordOpeningStock($company, $branchPlant, $godownFinished, $itemsCatalog['fnd_lemon_500'], 2000, 18.00, 'BATCH-AQ-FND-OPN');

        // 14. Recipes / Bill of Materials (BOM)
        $bomWater1L = $this->createBOM($company, $itemsCatalog['fnd_water_1l'], 'BOM - Pure Mineral Water 1 Litre', 1.0, [
            ['item' => $itemsCatalog['preform_32g'], 'quantity' => 1.0],
            ['item' => $itemsCatalog['cap_28mm'], 'quantity' => 1.0],
            ['item' => $itemsCatalog['label_1l'], 'quantity' => 1.0],
            ['item' => $itemsCatalog['minerals_salt'], 'quantity' => 0.002],
        ], 'Automated blow moulding, ozonation, mineral dosing & capper line.');

        $bomCan20L = $this->createBOM($company, $itemsCatalog['fnd_can_20l'], 'BOM - 20L Water Dispenser Can Refill', 1.0, [
            ['item' => $itemsCatalog['cap_55mm'], 'quantity' => 1.0],
            ['item' => $itemsCatalog['minerals_salt'], 'quantity' => 0.015],
        ], 'Includes 5-stage automated sanitization, ozone injection & 55mm non-spill cap seal.');

        $bomSoda750 = $this->createBOM($company, $itemsCatalog['fnd_soda_750'], 'BOM - Sparkling Club Soda 750ml', 1.0, [
            ['item' => $itemsCatalog['preform_32g'], 'quantity' => 1.0],
            ['item' => $itemsCatalog['cap_28mm'], 'quantity' => 1.0],
            ['item' => $itemsCatalog['co2_gas'], 'quantity' => 0.008],
        ], 'Chilled carbonation at 4.2 bar gas volume.');

        $bomLemon500 = $this->createBOM($company, $itemsCatalog['fnd_lemon_500'], 'BOM - Lemon Spark Fruit Fizz 500ml', 1.0, [
            ['item' => $itemsCatalog['preform_20g'], 'quantity' => 1.0],
            ['item' => $itemsCatalog['cap_28mm'], 'quantity' => 1.0],
            ['item' => $itemsCatalog['sugar_syrup'], 'quantity' => 0.045],
            ['item' => $itemsCatalog['lemon_flavour'], 'quantity' => 0.004],
            ['item' => $itemsCatalog['co2_gas'], 'quantity' => 0.006],
        ], 'Natural lemon emulsion carbonated sparkling beverage.');

        // 15. Production Orders & Finished Goods Generation
        // Batch 1: Mineral Water 1L (Completed)
        $this->recordProductionBatch(
            $company, $branchPlant, $bomWater1L, $itemsCatalog['fnd_water_1l'],
            $godownPkg, $godownFinished, 'PROD-AQ-2026-0001', 'BATCH-WTR-OCT26',
            5000, 5000, '2026-10-10', 'completed',
            [
                ['item' => $itemsCatalog['preform_32g'], 'quantity' => 5000, 'batch_no' => 'BATCH-PRE-32-OPN'],
                ['item' => $itemsCatalog['cap_28mm'], 'quantity' => 5000, 'batch_no' => 'BATCH-CAP-28-OPN'],
                ['item' => $itemsCatalog['label_1l'], 'quantity' => 5000, 'batch_no' => 'BATCH-LBL-1L-OPN'],
                ['item' => $itemsCatalog['minerals_salt'], 'quantity' => 10, 'batch_no' => 'BATCH-MIN-OPN'],
            ], '2026-10-11'
        );

        // Batch 2: 20L Water Can Refill (Completed)
        $this->recordProductionBatch(
            $company, $branchDelhi, $bomCan20L, $itemsCatalog['fnd_can_20l'],
            $godownPkg, $godownCans, 'PROD-AQ-2026-0002', 'BATCH-CAN-OCT26',
            1000, 1000, '2026-10-14', 'completed',
            [
                ['item' => $itemsCatalog['cap_55mm'], 'quantity' => 1000, 'batch_no' => 'BATCH-CAP-55-OPN'],
                ['item' => $itemsCatalog['minerals_salt'], 'quantity' => 15, 'batch_no' => 'BATCH-MIN-OPN'],
            ], '2026-10-15'
        );

        // Batch 3: Sparkling Club Soda 750ml (Completed)
        $this->recordProductionBatch(
            $company, $branchPlant, $bomSoda750, $itemsCatalog['fnd_soda_750'],
            $godownPkg, $godownFinished, 'PROD-AQ-2026-0003', 'BATCH-SDA-OCT26',
            3000, 3000, '2026-10-18', 'completed',
            [
                ['item' => $itemsCatalog['preform_32g'], 'quantity' => 3000, 'batch_no' => 'BATCH-PRE-32-OPN'],
                ['item' => $itemsCatalog['cap_28mm'], 'quantity' => 3000, 'batch_no' => 'BATCH-CAP-28-OPN'],
                ['item' => $itemsCatalog['co2_gas'], 'quantity' => 24, 'batch_no' => 'BATCH-CO2-OPN'],
            ], '2026-10-19'
        );

        // Batch 4: Lemon Spark Fruit Fizz 500ml (Completed)
        $this->recordProductionBatch(
            $company, $branchPlant, $bomLemon500, $itemsCatalog['fnd_lemon_500'],
            $godownPkg, $godownFinished, 'PROD-AQ-2026-0004', 'BATCH-LMN-OCT26',
            2500, 2500, '2026-10-22', 'completed',
            [
                ['item' => $itemsCatalog['preform_20g'], 'quantity' => 2500, 'batch_no' => 'BATCH-PRE-20-OPN'],
                ['item' => $itemsCatalog['cap_28mm'], 'quantity' => 2500, 'batch_no' => 'BATCH-CAP-28-OPN'],
                ['item' => $itemsCatalog['sugar_syrup'], 'quantity' => 112.5, 'batch_no' => 'BATCH-SGR-OPN'],
                ['item' => $itemsCatalog['lemon_flavour'], 'quantity' => 10, 'batch_no' => 'BATCH-LMN-OPN'],
                ['item' => $itemsCatalog['co2_gas'], 'quantity' => 15, 'batch_no' => 'BATCH-CO2-OPN'],
            ], '2026-10-24'
        );

        // Batch 5: Mineral Water 1L (Planned)
        $this->recordProductionBatch(
            $company, $branchPlant, $bomWater1L, $itemsCatalog['fnd_water_1l'],
            $godownPkg, $godownFinished, 'PROD-AQ-2026-0005', 'BATCH-WTR-NOV26',
            6000, 0, '2026-10-29', 'planned', []
        );

        // 16. Quality Checks (QC)
        $this->recordQualityCheck(
            $company, $branchPlant, $godownRaw, $itemsCatalog['minerals_salt'],
            'QC-AQ-2026-0001', 'GRN', null, 'GRN-AQ-2026-0001', '2026-08-14',
            'Usable', 500.0, 0.0, 'Purity 99.8%. Heavy metals non-detectable. Certificate of analysis verified. Approved.'
        );

        $this->recordQualityCheck(
            $company, $branchPlant, $godownPkg, $itemsCatalog['preform_32g'],
            'QC-AQ-2026-0002', 'GRN', null, 'GRN-AQ-2026-0002', '2026-08-22',
            'Usable', 20000.0, 0.0, 'Acetaldehyde level < 1.0 ppm. Wall thickness uniform. Blow test passed. Approved.'
        );

        $this->recordQualityCheck(
            $company, $branchPlant, $godownFinished, $itemsCatalog['fnd_water_1l'],
            'QC-AQ-2026-0003', 'Production', null, 'PROD-AQ-2026-0001', '2026-10-11',
            'Usable', 5000.0, 0.0, 'TDS: 118 ppm. pH: 7.35. Coliform / E.coli: Absent. Ozone residual: 0.15 ppm. Passed BIS IS:14543.'
        );

        $this->recordQualityCheck(
            $company, $branchDelhi, $godownCans, $itemsCatalog['fnd_can_20l'],
            'QC-AQ-2026-0004', 'Production', null, 'PROD-AQ-2026-0002', '2026-10-15',
            'Usable', 1000.0, 0.0, 'Can neck torque test passed. Zero leakage under 10 psi pressure test. Approved for corporate delivery.'
        );

        // 17. Real Purchases (10 Purchases of PET Preforms, Caps, CO2, Minerals)
        $purchasePlan = [
            ['supp' => $suppliers[0], 'items' => [['item' => $itemsCatalog['preform_32g'], 'qty' => 25000, 'rate' => 2.10, 'batch' => 'BATCH-PRE-32-P1']], 'inv' => 'RIL/26/890', 'date' => '2026-08-12', 'paid' => 45000.00],
            ['supp' => $suppliers[1], 'items' => [['item' => $itemsCatalog['cap_28mm'], 'qty' => 50000, 'rate' => 0.48, 'batch' => 'BATCH-CAP-28-P1']], 'inv' => 'BER/26/102', 'date' => '2026-08-19', 'paid' => 20000.00],
            ['supp' => $suppliers[2], 'items' => [['item' => $itemsCatalog['co2_gas'], 'qty' => 1500, 'rate' => 31.00, 'batch' => 'BATCH-CO2-P1']], 'inv' => 'PRAX/26/441', 'date' => '2026-08-28', 'paid' => 40000.00],
            ['supp' => $suppliers[3], 'items' => [['item' => $itemsCatalog['label_1l'], 'qty' => 30000, 'rate' => 0.31, 'batch' => 'BATCH-LBL-1L-P1']], 'inv' => 'HUH/26/771', 'date' => '2026-09-04', 'paid' => 8000.00],
            ['supp' => $suppliers[4], 'items' => [['item' => $itemsCatalog['ro_filter'], 'qty' => 30, 'rate' => 220.00, 'batch' => 'BATCH-FLT-P1']], 'inv' => 'ION/26/552', 'date' => '2026-09-12', 'paid' => 6000.00],
            ['supp' => $suppliers[5], 'items' => [['item' => $itemsCatalog['sugar_syrup'], 'qty' => 2000, 'rate' => 52.00, 'batch' => 'BATCH-SGR-P1']], 'inv' => 'DHM/26/331', 'date' => '2026-09-18', 'paid' => 90000.00],
            ['supp' => $suppliers[6], 'items' => [['item' => $itemsCatalog['minerals_salt'], 'qty' => 500, 'rate' => 180.00, 'batch' => 'BATCH-MIN-P1']], 'inv' => 'CHM/26/911', 'date' => '2026-09-26', 'paid' => 80000.00],
            ['supp' => $suppliers[0], 'items' => [['item' => $itemsCatalog['preform_20g'], 'qty' => 30000, 'rate' => 1.45, 'batch' => 'BATCH-PRE-20-P1']], 'inv' => 'RIL/26/955', 'date' => '2026-10-04', 'paid' => 40000.00],
            ['supp' => $suppliers[1], 'items' => [['item' => $itemsCatalog['cap_55mm'], 'qty' => 5000, 'rate' => 2.30, 'batch' => 'BATCH-CAP-55-P1']], 'inv' => 'BER/26/208', 'date' => '2026-10-12', 'paid' => 10000.00],
            ['supp' => $suppliers[2], 'items' => [['item' => $itemsCatalog['co2_gas'], 'qty' => 1800, 'rate' => 31.00, 'batch' => 'BATCH-CO2-P2']], 'inv' => 'PRAX/26/509', 'date' => '2026-10-21', 'paid' => 50000.00],
        ];

        $purchaseEntries = [];
        foreach ($purchasePlan as $idx => $p) {
            $num = sprintf('%04d', $idx + 1);
            $itemRows = [];
            foreach ($p['items'] as $it) {
                $itemRows[] = ['item' => $it['item'], 'quantity' => $it['qty'], 'rate' => $it['rate'], 'batch_no' => $it['batch'], 'tax_percent' => 18];
            }
            $pe = $this->recordPurchaseWorkflow($company, $branchPlant, $godownPkg, $p['supp'], $itemRows, [
                'po_number' => "PO-AQ-2026-{$num}",
                'grn_number' => "GRN-AQ-2026-{$num}",
                'purchase_number' => "PUR-AQ-2026-{$num}",
                'invoice_number' => $p['inv'],
                'date' => $p['date'],
                'paid_amount' => $p['paid'],
                'payment_number' => "PAY-AQ-2026-{$num}",
            ], $ledgers);
            $purchaseEntries[] = $pe;
        }

        // Defective Short-Neck Caps Purchase Return
        if (!empty($purchaseEntries[1])) {
            $this->recordPurchaseReturn(
                $company, $branchPlant, $godownPkg, $suppliers[1], $purchaseEntries[1],
                [['item' => $itemsCatalog['cap_28mm'], 'quantity' => 2000, 'rate' => 0.48, 'batch_no' => 'BATCH-CAP-28-P1']],
                $ledgers, 'PRT-AQ-2026-0001', '2026-08-25', 'Thread mismatch noticed during capper machine trial run'
            );
        }

        // 18. Real Sales Transactions (16 Sales to Corporate Tech Parks, Hotels, Metro & Gyms)
        $salesPlan = [
            ['cust' => $customers[0], 'items' => [['item' => $itemsCatalog['fnd_can_20l'], 'qty' => 250, 'rate' => 80.00], ['item' => $itemsCatalog['fnd_water_1l'], 'qty' => 500, 'rate' => 16.00]], 'date' => '2026-08-22', 'paid' => 25000.00],
            ['cust' => $customers[1], 'items' => [['item' => $itemsCatalog['fnd_water_1l'], 'qty' => 600, 'rate' => 16.00], ['item' => $itemsCatalog['fnd_soda_750'], 'qty' => 400, 'rate' => 20.00]], 'date' => '2026-08-27', 'paid' => 18000.00],
            ['cust' => $customers[2], 'items' => [['item' => $itemsCatalog['fnd_alkaline_1l'], 'qty' => 200, 'rate' => 60.00], ['item' => $itemsCatalog['fnd_water_500'], 'qty' => 500, 'rate' => 8.50]], 'date' => '2026-09-03', 'paid' => 15000.00],
            ['cust' => $customers[3], 'items' => [['item' => $itemsCatalog['fnd_water_500'], 'qty' => 1200, 'rate' => 8.50], ['item' => $itemsCatalog['fnd_lemon_500'], 'qty' => 400, 'rate' => 32.00]], 'date' => '2026-09-09', 'paid' => 20000.00],
            ['cust' => $customers[4], 'items' => [['item' => $itemsCatalog['fnd_can_20l'], 'qty' => 300, 'rate' => 80.00], ['item' => $itemsCatalog['fnd_water_250'], 'qty' => 800, 'rate' => 5.50]], 'date' => '2026-09-16', 'paid' => 28000.00],
            ['cust' => $customers[5], 'items' => [['item' => $itemsCatalog['fnd_soda_750'], 'qty' => 500, 'rate' => 20.00], ['item' => $itemsCatalog['fnd_soda_500'], 'qty' => 400, 'rate' => 15.00]], 'date' => '2026-09-23', 'paid' => 16000.00],
            ['cust' => $customers[6], 'items' => [['item' => $itemsCatalog['fnd_water_500'], 'qty' => 1000, 'rate' => 8.50], ['item' => $itemsCatalog['fnd_cola_500'], 'qty' => 400, 'rate' => 32.00], ['item' => $itemsCatalog['fnd_lemon_500'], 'qty' => 300, 'rate' => 32.00]], 'date' => '2026-09-29', 'paid' => 30000.00],
            ['cust' => $customers[7], 'items' => [['item' => $itemsCatalog['fnd_water_500'], 'qty' => 1500, 'rate' => 8.50], ['item' => $itemsCatalog['fnd_soda_500'], 'qty' => 500, 'rate' => 15.00]], 'date' => '2026-10-03', 'paid' => 20000.00],
            ['cust' => $customers[8], 'items' => [['item' => $itemsCatalog['fnd_water_1l'], 'qty' => 400, 'rate' => 16.00], ['item' => $itemsCatalog['fnd_can_20l'], 'qty' => 150, 'rate' => 80.00]], 'date' => '2026-10-08', 'paid' => 18000.00],
            ['cust' => $customers[9], 'items' => [['item' => $itemsCatalog['fnd_water_1l'], 'qty' => 500, 'rate' => 16.00], ['item' => $itemsCatalog['fnd_orange_500'], 'qty' => 300, 'rate' => 32.00]], 'date' => '2026-10-13', 'paid' => 17000.00],
            ['cust' => $customers[10], 'items' => [['item' => $itemsCatalog['fnd_water_500'], 'qty' => 600, 'rate' => 8.50], ['item' => $itemsCatalog['fnd_soda_500'], 'qty' => 300, 'rate' => 15.00]], 'date' => '2026-10-18', 'paid' => 9000.00],
            ['cust' => $customers[11], 'items' => [['item' => $itemsCatalog['fnd_water_500'], 'qty' => 400, 'rate' => 8.50], ['item' => $itemsCatalog['fnd_soda_750'], 'qty' => 200, 'rate' => 20.00]], 'date' => '2026-10-22', 'paid' => 7400.00],
            ['cust' => $customers[12], 'items' => [['item' => $itemsCatalog['fnd_can_20l'], 'qty' => 180, 'rate' => 80.00], ['item' => $itemsCatalog['fnd_water_1l'], 'qty' => 300, 'rate' => 16.00]], 'date' => '2026-10-25', 'paid' => 18000.00],
            ['cust' => $customers[0], 'items' => [['item' => $itemsCatalog['fnd_can_20l'], 'qty' => 200, 'rate' => 80.00], ['item' => $itemsCatalog['fnd_water_1l'], 'qty' => 400, 'rate' => 16.00]], 'date' => '2026-10-28', 'paid' => 22000.00],
            ['cust' => $customers[1], 'items' => [['item' => $itemsCatalog['fnd_soda_750'], 'qty' => 400, 'rate' => 20.00], ['item' => $itemsCatalog['fnd_water_1l'], 'qty' => 300, 'rate' => 16.00]], 'date' => '2026-10-30', 'paid' => 12000.00],
            ['cust' => $customers[2], 'items' => [['item' => $itemsCatalog['fnd_alkaline_1l'], 'qty' => 150, 'rate' => 60.00], ['item' => $itemsCatalog['fnd_water_500'], 'qty' => 300, 'rate' => 8.50]], 'date' => '2026-10-31', 'paid' => 11000.00],
        ];

        $salesInvoices = [];
        foreach ($salesPlan as $idx => $s) {
            $num = sprintf('%04d', $idx + 1);
            $itemRows = [];
            foreach ($s['items'] as $it) {
                $itemRows[] = ['item' => $it['item'], 'quantity' => $it['qty'], 'rate' => $it['rate'], 'batch_no' => 'BATCH-AQ-FND-OPN', 'tax_percent' => 18];
            }
            $si = $this->recordSalesWorkflow($company, $branchPlant, $godownFinished, $s['cust'], $itemRows, [
                'order_number' => "SO-AQ-2026-{$num}",
                'invoice_number' => "INV-AQ-2026-{$num}",
                'delivery_number' => "DEL-AQ-2026-{$num}",
                'date' => $s['date'],
                'paid_amount' => $s['paid'],
                'collection_number' => "COL-AQ-2026-{$num}",
            ], $ledgers, $vehicle1, $route1);
            $salesInvoices[] = $si;
        }

        // Can Leakage Sales Return
        if (!empty($salesInvoices[0])) {
            $this->recordSalesReturn(
                $company, $branchDelhi, $godownCans, $customers[0], $salesInvoices[0],
                [['item' => $itemsCatalog['fnd_can_20l'], 'quantity' => 5, 'rate' => 80.00, 'batch_no' => 'BATCH-AQ-CAN-OPN']],
                $ledgers, 'SRT-AQ-2026-0001', '2026-08-25', 'Can valve seal leak reported at reception tower'
            );
        }

        // 19. Operating Expenses
        $this->recordExpenseVoucher($company, $branchPlant, $ledgers['rentLedger'], $ledgers['bankLedger'], 160000.00, '2026-09-01', 'September Ecotech Bottling Plant Industrial Lease');
        $this->recordExpenseVoucher($company, $branchPlant, $ledgers['rentLedger'], $ledgers['bankLedger'], 160000.00, '2026-10-01', 'October Ecotech Bottling Plant Industrial Lease');
        $this->recordExpenseVoucher($company, $branchPlant, $ledgers['utilityLedger'], $ledgers['bankLedger'], 74000.00, '2026-10-06', 'UPPCL Heavy Industrial Electricity Bill (RO Pumps & Chilling Units)');
        $this->recordExpenseVoucher($company, $branchPlant, $ledgers['freightLedger'], $ledgers['bankLedger'], 38000.00, '2026-10-14', 'Commercial Fleet Diesel & CNG Refills - HPCL Card');

        // 20. CRM Leads
        CrmLead::firstOrCreate(['company_id' => $company->id, 'name' => 'IndiGo Airlines Catering Procurement'], [
            'company_name' => 'InterGlobe Aviation Ltd',
            'phone' => '+91 124 435 3000',
            'email' => 'catering@goindigo.example.com',
            'source' => 'Corporate Aviation Expo 2026',
            'status' => 'Qualified',
            'notes' => 'Tender for 250ml mini bottled water supply for domestic flights.',
        ]);

        CrmLead::firstOrCreate(['company_id' => $company->id, 'name' => 'Wipro Campus Greater Noida'], [
            'company_name' => 'Wipro Technologies SEZ',
            'phone' => '+91 120 405 8899',
            'email' => 'admin.noida@wipro.example.com',
            'source' => 'Direct B2B Tender',
            'status' => 'Contacted',
            'notes' => 'Requirement of 500 x 20L cans weekly with dispenser maintenance.',
        ]);

        // 20b. Daily Operations Management (Bottling & Can Refill with Mobile Van Delivery)
        $dailyOpService = new DailyOperationService($this->inventoryService);
        $godownTruck = $this->inventoryService->ensureMobileVanGodown($vehicle1);
        $godownVanCan = $this->inventoryService->ensureMobileVanGodown($vehicle2);

        // Daily Operation 1: Automated Bottle Blowing, Filling & Capping
        $dailyOpService->createOperation([
            'company_id' => $company->id,
            'branch_id' => $branchPlant->id,
            'department_id' => $deptBottling->id,
            'operation_number' => 'DOP-AQ-20261012-001',
            'operation_date' => '2026-10-12',
            'department_head_id' => $sanjayStaff?->id,
            'supervisor_id' => $pramodStaff?->id,
            'work_order_ref' => 'WO-AQ-BTL-01',
            'process_name' => 'High-Speed PET Blow Moulding, Ozonation & Bottling',
            'work_description' => 'Automated linear 8-cavity blow moulding, rinse, filling with ozonated mineralized water and capper line.',
            'start_time' => '07:30:00',
            'end_time' => '16:00:00',
            'work_status' => 'completed',
            'approval_status' => 'department_head_approved',
            'remarks' => 'Microbiological swab and ozone concentration (0.4 ppm) passed line lab inspection.',
        ], [
            ['staff_id' => $pramodStaff->id, 'employment_type' => 'salaried', 'hours_worked' => 8.0, 'units_completed' => 980],
            ['staff_id' => $naveenStaff->id, 'employment_type' => 'salaried', 'hours_worked' => 8.0, 'units_completed' => 980],
        ], [
            ['item_id' => $itemsCatalog['preform_32g']->id, 'source_godown_id' => $godownPkg->id, 'batch_no' => 'BATCH-PRE-32-OPN', 'consumed_quantity' => 1000.0, 'unit_cost' => 2.00],
            ['item_id' => $itemsCatalog['cap_28mm']->id, 'source_godown_id' => $godownPkg->id, 'batch_no' => 'BATCH-CAP-28-OPN', 'consumed_quantity' => 1000.0, 'unit_cost' => 0.45],
            ['item_id' => $itemsCatalog['label_1l']->id, 'source_godown_id' => $godownPkg->id, 'batch_no' => 'BATCH-LBL-1L-OPN', 'consumed_quantity' => 1000.0, 'unit_cost' => 0.30],
            ['item_id' => $itemsCatalog['minerals_salt']->id, 'source_godown_id' => $godownRaw->id, 'batch_no' => 'BATCH-MIN-OPN', 'consumed_quantity' => 5.0, 'unit_cost' => 180.00],
        ], [
            ['item_id' => $itemsCatalog['fnd_water_1l']->id, 'dest_godown_id' => $godownFinished->id, 'batch_number' => 'BAT-AQ-BTL-261012', 'produced_quantity' => 980.0, 'unit_cost' => 6.50, 'quality_status' => 'passed'],
        ], [
            ['wastage_name' => 'Blowing Oven Preform Thermal Distortion', 'wastage_type' => 'process_loss', 'quantity' => 15.0, 'unit' => 'PCS', 'estimated_cost' => 30.0, 'recoverable_quantity' => 15.0, 'reason' => 'Oven mandrel sensor trip during shift changeover'],
            ['wastage_name' => 'Capper Chuck Thread Jam Caps', 'wastage_type' => 'packaging_damage', 'quantity' => 5.0, 'unit' => 'PCS', 'estimated_cost' => 2.5, 'recoverable_quantity' => 0.0, 'reason' => 'Chuck alignment torque slip'],
        ], [
            ['item_id' => $itemsCatalog['fnd_water_1l']->id, 'destination_godown_id' => $godownFinished->id, 'destination_type' => 'head_office_warehouse', 'batch_no' => 'BAT-AQ-BTL-261012', 'quantity' => 600.0],
            ['item_id' => $itemsCatalog['fnd_water_1l']->id, 'destination_godown_id' => $godownTruck->id, 'destination_type' => 'delivery_van', 'batch_no' => 'BAT-AQ-BTL-261012', 'quantity' => 380.0],
        ]);

        // Daily Operation 2: 20L Commercial Can Sanitization & Refill
        $dailyOpService->createOperation([
            'company_id' => $company->id,
            'branch_id' => $branchDelhi->id,
            'department_id' => $deptLog->id,
            'operation_number' => 'DOP-AQ-20261014-002',
            'operation_date' => '2026-10-14',
            'department_head_id' => $sanjayStaff?->id,
            'supervisor_id' => $harishStaff?->id,
            'work_order_ref' => 'WO-AQ-CAN-02',
            'process_name' => 'Ultrasonic Decontamination, Refill & Tamper Evident Sealing',
            'work_description' => 'Multi-stage caustic soda wash, warm water rinse, ozone sanitize, filling 20L jars and heat shrink neck band seal.',
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'work_status' => 'completed',
            'approval_status' => 'department_head_approved',
            'remarks' => '197 clean cans filled and ready for corporate delivery fleet dispatch.',
        ], [
            ['staff_id' => $harishStaff->id, 'employment_type' => 'salaried', 'hours_worked' => 8.0, 'units_completed' => 197],
            ['staff_id' => $rakeshStaff->id, 'employment_type' => 'daily_wage', 'hours_worked' => 8.0, 'units_completed' => 197, 'hourly_rate' => 85.00],
        ], [
            ['item_id' => $itemsCatalog['jar_20l_empty']->id, 'source_godown_id' => $godownCans->id, 'batch_no' => 'BATCH-JAR-20L-OPN', 'consumed_quantity' => 200.0, 'unit_cost' => 150.00],
            ['item_id' => $itemsCatalog['cap_55mm']->id, 'source_godown_id' => $godownPkg->id, 'batch_no' => 'BATCH-CAP-55-OPN', 'consumed_quantity' => 200.0, 'unit_cost' => 2.20],
        ], [
            ['item_id' => $itemsCatalog['fnd_can_20l']->id, 'dest_godown_id' => $godownCans->id, 'batch_number' => 'BAT-AQ-CAN-261014', 'produced_quantity' => 197.0, 'unit_cost' => 28.00, 'quality_status' => 'passed'],
        ], [
            ['wastage_name' => 'Hairline Neck Fracture Damaged Jars', 'wastage_type' => 'scrap_trimming', 'quantity' => 3.0, 'unit' => 'JAR', 'estimated_cost' => 450.0, 'recoverable_quantity' => 0.0, 'reason' => 'Structural polycarbonate fatigue identified under pressure leak test'],
        ], [
            ['item_id' => $itemsCatalog['fnd_can_20l']->id, 'destination_godown_id' => $godownCans->id, 'destination_type' => 'branch_warehouse', 'batch_no' => 'BAT-AQ-CAN-261014', 'quantity' => 117.0],
            ['item_id' => $itemsCatalog['fnd_can_20l']->id, 'destination_godown_id' => $godownVanCan->id, 'destination_type' => 'delivery_van', 'batch_no' => 'BAT-AQ-CAN-261014', 'quantity' => 80.0],
        ]);

        // 21. User Accounts
        $compAdminRole = Role::where('slug', 'company-admin')->first();
        $accountantRole = Role::where('slug', 'accounts-manager')->first();

        $compAdminUser = User::firstOrCreate(['email' => 'admin@aquafreshbev.com'], [
            'name' => 'Sanjay Mathur (AquaFresh Admin)',
            'password' => Hash::make('password123'),
            'company_id' => $company->id,
            'branch_id' => $branchPlant->id,
            'role_id' => $compAdminRole?->id,
            'phone' => '+91 9811122334',
            'is_active' => true,
        ]);
        $compAdminUser->companies()->syncWithoutDetaching([$company->id => ['branch_id' => $branchPlant->id]]);

        $accountsUser = User::firstOrCreate(['email' => 'accounts@aquafreshbev.com'], [
            'name' => 'Amitabh Saxena (AquaFresh Accountant)',
            'password' => Hash::make('password123'),
            'company_id' => $company->id,
            'branch_id' => $branchPlant->id,
            'role_id' => $accountantRole?->id,
            'phone' => '+91 9811223344',
            'is_active' => true,
        ]);
        $accountsUser->companies()->syncWithoutDetaching([$company->id => ['branch_id' => $branchPlant->id]]);

        // Attach Super Admin
        $superAdmin = User::where('email', 'admin@erp.com')->first();
        if ($superAdmin) {
            $superAdmin->companies()->syncWithoutDetaching([$company->id => ['branch_id' => $branchPlant->id]]);
        }
    }
}

