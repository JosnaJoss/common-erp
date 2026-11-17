<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Category;
use App\Models\City;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Godown;
use App\Models\Item;
use App\Models\Location;
use App\Models\Product;
use App\Models\Role;
use App\Models\Route;
use App\Models\Staff;
use App\Models\Subcategory;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Vehicle;
use App\Services\DailyOperationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;

class CraftFurnitureSeeder extends BaseIndustrySeeder
{
    public function run(): void
    {
        $this->command->info('Seeding Company D: Craftline Furniture Works (Furniture Manufacturing)...');

        // 1. Company Profile
        $company = $this->initCompany('CRAFT-FURNITURE', [
            'name' => 'Craftline Furniture Works',
            'business_type' => 'manufacturing',
            'registration_no' => 'U36100KA2021PTC148820',
            'gst_no' => '29AAACC1234F1Z8',
            'pan_no' => 'AAACC1234F',
            'email' => 'operations@craftlinefurniture.com',
            'phone' => '+91 80 2839 5500',
            'address' => 'Plot 44-B, 3rd Phase, Peenya Industrial Area, Bangalore, Karnataka - 560058',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'financial_year_start' => '2026-04-01',
            'financial_year_end' => '2027-03-31',
            'status' => 'active',
        ], ['staff_hr', 'inventory', 'purchase', 'sales', 'accounts', 'production', 'vehicle_route', 'quality', 'crm']);

        // 2. Chart of Accounts
        $ledgers = $this->initChartOfAccounts(
            $company,
            'HDFC Bank Ltd (Current A/c 502000334455)',
            '502000334455',
            'HDFC0000044'
        );

        // 3. Branches
        $branchFactory = Branch::firstOrCreate(['company_id' => $company->id, 'code' => 'CRAFT-BR-FAC'], [
            'name' => 'Peenya Sawmill & Manufacturing Plant',
            'address' => 'Plot 44-B, Peenya Industrial Area',
            'city' => 'Bangalore',
            'state' => 'Karnataka',
            'postal_code' => '560058',
            'contact_number' => '+91 80 2839 5501',
            'email' => 'factory@craftlinefurniture.com',
            'branch_manager' => 'Devraj Gowda',
            'is_main_branch' => true,
            'status' => 'active',
        ]);

        $branchShowroom = Branch::firstOrCreate(['company_id' => $company->id, 'code' => 'CRAFT-BR-SHO'], [
            'name' => 'Indiranagar Flagship Design Showroom',
            'address' => '777, 100 Feet Road, HAL 2nd Stage, Indiranagar',
            'city' => 'Bangalore',
            'state' => 'Karnataka',
            'postal_code' => '560038',
            'contact_number' => '+91 80 4125 8899',
            'email' => 'showroom@craftlinefurniture.com',
            'branch_manager' => 'Meera Nambiar',
            'is_main_branch' => false,
            'status' => 'active',
        ]);

        $branchMumbai = Branch::firstOrCreate(['company_id' => $company->id, 'code' => 'CRAFT-BR-MUM'], [
            'name' => 'Bhiwandi West Logistics Depot',
            'address' => 'Warehouse No. 12, Indian Corporation Logistics Park',
            'city' => 'Bhiwandi',
            'state' => 'Maharashtra',
            'postal_code' => '421302',
            'contact_number' => '+91 2522 660 110',
            'email' => 'mumbai.depot@craftlinefurniture.com',
            'branch_manager' => 'Sanjay Patil',
            'is_main_branch' => false,
            'status' => 'active',
        ]);

        // 4. Territory, Vehicle & Mobile Stock Godowns
        $cityBlr = City::firstOrCreate(['company_id' => $company->id, 'name' => 'Bangalore Metro'], [
            'branch_id' => $branchFactory->id, 'status' => 'active',
        ]);
        $locPeenya = Location::firstOrCreate(['company_id' => $company->id, 'city_id' => $cityBlr->id, 'name' => 'Peenya Industrial Area'], ['pincode' => '560058', 'status' => 'active']);
        $locIndira = Location::firstOrCreate(['company_id' => $company->id, 'city_id' => $cityBlr->id, 'name' => 'Indiranagar 100ft Rd'], ['pincode' => '560038', 'status' => 'active']);
        $locWhite = Location::firstOrCreate(['company_id' => $company->id, 'city_id' => $cityBlr->id, 'name' => 'Whitefield Tech Corridor'], ['pincode' => '560066', 'status' => 'active']);

        $vehicleTruck = Vehicle::firstOrCreate(['company_id' => $company->id, 'registration_number' => 'KA-01-CR-9922'], [
            'branch_id' => $branchFactory->id,
            'vehicle_name' => 'Eicher Pro 2049 Furniture Carrier',
            'vehicle_type' => 'Truck',
            'capacity' => 3500.00,
            'assigned_driver' => 'Muthuswamy R',
            'status' => 'active',
        ]);

        // Ensure Mobile Van Stock Location
        $godownVan = $this->inventoryService->ensureMobileVanGodown($vehicleTruck);

        // Fixed Godowns
        $godownTimber = Godown::firstOrCreate(['company_id' => $company->id, 'code' => 'WH-CRF-TYD'], [
            'branch_id' => $branchFactory->id,
            'name' => 'Seasoned Timber Yard & Lumber Shed',
            'address' => 'Yard A, Peenya Works',
            'postal_code' => '560058',
            'responsible_staff' => 'Basavaraj M',
            'location_type' => 'warehouse',
            'status' => 'active',
        ]);

        $godownCarpentry = Godown::firstOrCreate(['company_id' => $company->id, 'code' => 'WH-CRF-FLR'], [
            'branch_id' => $branchFactory->id,
            'name' => 'Carpentry & Assembly Shop Floor',
            'address' => 'Floor 1, Main Hangar',
            'postal_code' => '560058',
            'responsible_staff' => 'Master Ramesh Acharya',
            'location_type' => 'production_unit',
            'status' => 'active',
        ]);

        $godownShowroom = Godown::firstOrCreate(['company_id' => $company->id, 'code' => 'WH-CRF-SHO'], [
            'branch_id' => $branchShowroom->id,
            'name' => 'Indiranagar Flagship Showroom Display',
            'address' => '100 Feet Road Gallery',
            'postal_code' => '560038',
            'responsible_staff' => 'Meera Nambiar',
            'location_type' => 'sales_outlet',
            'status' => 'active',
        ]);

        $godownFinished = Godown::firstOrCreate(['company_id' => $company->id, 'code' => 'WH-CRF-FND'], [
            'branch_id' => $branchFactory->id,
            'name' => 'Finished Goods Warehouse & Packing Bay',
            'address' => 'Bay 4, Peenya Works',
            'postal_code' => '560058',
            'responsible_staff' => 'Kiran Hegde',
            'location_type' => 'packing_unit',
            'status' => 'active',
        ]);

        // 5. Departments
        $deptSaw = Department::firstOrCreate(['company_id' => $company->id, 'code' => 'DEPT-CRF-SAW'], [
            'branch_id' => $branchFactory->id,
            'name' => 'Sawmill & Timber Sizing',
            'department_type' => 'cutting',
            'department_head' => 'Gururaj Kulkarni',
            'status' => 'active',
        ]);

        $deptCarpentry = Department::firstOrCreate(['company_id' => $company->id, 'code' => 'DEPT-CRF-CRP'], [
            'branch_id' => $branchFactory->id,
            'name' => 'Carpentry & Joinery Workshop',
            'department_type' => 'production',
            'department_head' => 'Ramesh Acharya',
            'status' => 'active',
        ]);

        $deptPolish = Department::firstOrCreate(['company_id' => $company->id, 'code' => 'DEPT-CRF-POL'], [
            'branch_id' => $branchFactory->id,
            'name' => 'Sanding & PU Polishing Studio',
            'department_type' => 'production',
            'department_head' => 'Anthony Joseph',
            'status' => 'active',
        ]);

        $deptQC = Department::firstOrCreate(['company_id' => $company->id, 'code' => 'DEPT-CRF-QC'], [
            'branch_id' => $branchFactory->id,
            'name' => 'Quality Assurance & Structural Testing',
            'department_type' => 'quality_control',
            'department_head' => 'Vinayak Bhat',
            'status' => 'active',
        ]);

        $deptSales = Department::firstOrCreate(['company_id' => $company->id, 'code' => 'DEPT-CRF-SAL'], [
            'branch_id' => $branchShowroom->id,
            'name' => 'Bespoke Sales & Interior Projects',
            'department_type' => 'sales',
            'department_head' => 'Meera Nambiar',
            'status' => 'active',
        ]);

        // 6. Units of Measure
        $uCft = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'CFT'], [
            'name' => 'Cubic Feet', 'unit_type' => 'volume', 'conversion_factor' => 1, 'status' => 'active',
        ]);
        $uPcs = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'PCS'], [
            'name' => 'Pieces', 'unit_type' => 'quantity', 'conversion_factor' => 1, 'status' => 'active',
        ]);
        $uSht = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'SHT'], [
            'name' => 'Sheets', 'unit_type' => 'quantity', 'conversion_factor' => 1, 'status' => 'active',
        ]);
        $uLtr = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'LTR'], [
            'name' => 'Litres', 'unit_type' => 'volume', 'conversion_factor' => 1, 'status' => 'active',
        ]);
        $uBox = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'BOX'], [
            'name' => 'Box of 100 Hardware', 'unit_type' => 'quantity', 'conversion_factor' => 100, 'status' => 'active',
        ]);
        $uMtr = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'MTR'], [
            'name' => 'Meters (Fabric)', 'unit_type' => 'length', 'conversion_factor' => 1, 'status' => 'active',
        ]);

        // 7. Categories & Subcategories
        $catTimber = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Hardwood & Timber'], ['status' => 'active']);
        $subTeak = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catTimber->id, 'name' => 'Burma & Nilambur Teak'], ['status' => 'active']);
        $subOak = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catTimber->id, 'name' => 'White Oak & Walnut'], ['status' => 'active']);

        $catHardware = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Hardware & Finishes'], ['status' => 'active']);
        $subFinishes = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catHardware->id, 'name' => 'Stains, Varnishes & PU Polish'], ['status' => 'active']);
        $subFasteners = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catHardware->id, 'name' => 'Screws, Hinges & Channels'], ['status' => 'active']);

        $catDining = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Dining & Living Furniture'], ['status' => 'active']);
        $subTables = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catDining->id, 'name' => 'Solid Wood Dining Tables'], ['status' => 'active']);
        $subChairs = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catDining->id, 'name' => 'Chairs & Benches'], ['status' => 'active']);

        $catOffice = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Executive Office & Study'], ['status' => 'active']);
        $subDesks = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catOffice->id, 'name' => 'Executive Desks & Credenzas'], ['status' => 'active']);

        // 8. Products & Items (Raw Materials + Finished Goods)
        $items = [];

        // Raw Materials
        $items['teak_planks'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-RM-TEAK'], [
            'category_id' => $catTimber->id, 'subcategory_id' => $subTeak->id, 'unit_id' => $uCft->id,
            'name' => 'Grade-A Seasoned Burma Teak Wood Planks', 'item_type' => 'raw_material', 'weight' => 22.0,
            'min_price' => 3800.00, 'normal_price' => 4500.00, 'max_price' => 5200.00, 'gst_percentage' => 18.00, 'hsn_code' => '4407', 'status' => 'active',
        ]);

        $items['oak_logs'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-RM-OAK'], [
            'category_id' => $catTimber->id, 'subcategory_id' => $subOak->id, 'unit_id' => $uCft->id,
            'name' => 'Kiln-Dried European White Oak Timber', 'item_type' => 'raw_material', 'weight' => 24.0,
            'min_price' => 3200.00, 'normal_price' => 3800.00, 'max_price' => 4400.00, 'gst_percentage' => 18.00, 'hsn_code' => '4407', 'status' => 'active',
        ]);

        $items['ply_18mm'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-RM-PLY18'], [
            'category_id' => $catTimber->id, 'subcategory_id' => $subTeak->id, 'unit_id' => $uSht->id,
            'name' => 'Marine Grade BWP Plywood 18mm (8x4 ft)', 'item_type' => 'raw_material', 'weight' => 32.0,
            'min_price' => 2400.00, 'normal_price' => 2850.00, 'max_price' => 3200.00, 'gst_percentage' => 18.00, 'hsn_code' => '4412', 'status' => 'active',
        ]);

        $items['ss_screws'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-RM-SCRW'], [
            'category_id' => $catHardware->id, 'subcategory_id' => $subFasteners->id, 'unit_id' => $uBox->id,
            'name' => 'Stainless Steel 304 Wood Screws 2-inch (Box of 100)', 'item_type' => 'raw_material', 'weight' => 0.8,
            'min_price' => 220.00, 'normal_price' => 280.00, 'max_price' => 340.00, 'gst_percentage' => 18.00, 'hsn_code' => '7318', 'status' => 'active',
        ]);

        $items['pu_polish'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-RM-PUPOL'], [
            'category_id' => $catHardware->id, 'subcategory_id' => $subFinishes->id, 'unit_id' => $uLtr->id,
            'name' => 'Italian High-Gloss Polyurethane (PU) Clear Wood Polish', 'item_type' => 'raw_material', 'weight' => 1.0,
            'min_price' => 450.00, 'normal_price' => 580.00, 'max_price' => 680.00, 'gst_percentage' => 18.00, 'hsn_code' => '3208', 'status' => 'active',
        ]);

        $items['boucle_fabric'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-RM-FABRIC'], [
            'category_id' => $catHardware->id, 'subcategory_id' => $subFinishes->id, 'unit_id' => $uMtr->id,
            'name' => 'Heavy Textured Off-White Boucle Upholstery Fabric', 'item_type' => 'raw_material', 'weight' => 0.6,
            'min_price' => 850.00, 'normal_price' => 1100.00, 'max_price' => 1350.00, 'gst_percentage' => 12.00, 'hsn_code' => '5407', 'status' => 'active',
        ]);

        // Finished Goods
        $pDiningTable = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-CRF-DT6'], [
            'category_id' => $catDining->id, 'subcategory_id' => $subTables->id,
            'name' => 'Craftline Royal 6-Seater Solid Burma Teak Dining Table', 'status' => 'active',
        ]);
        $items['dining_table'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-DT6'], [
            'category_id' => $catDining->id, 'subcategory_id' => $subTables->id, 'product_id' => $pDiningTable->id, 'unit_id' => $uPcs->id,
            'name' => 'Royal 6-Seater Solid Burma Teak Dining Table', 'item_type' => 'sales_item', 'weight' => 65.0,
            'min_price' => 55000.00, 'normal_price' => 68000.00, 'max_price' => 78000.00, 'gst_percentage' => 18.00, 'hsn_code' => '9403', 'status' => 'active',
        ]);

        $pOfficeDesk = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-CRF-DSK'], [
            'category_id' => $catOffice->id, 'subcategory_id' => $subDesks->id,
            'name' => 'Craftline Executive Ergonomic Teak Office Workstation', 'status' => 'active',
        ]);
        $items['office_desk'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-DSK'], [
            'category_id' => $catOffice->id, 'subcategory_id' => $subDesks->id, 'product_id' => $pOfficeDesk->id, 'unit_id' => $uPcs->id,
            'name' => 'Executive Ergonomic Teak Office Workstation', 'item_type' => 'sales_item', 'weight' => 48.0,
            'min_price' => 38000.00, 'normal_price' => 46000.00, 'max_price' => 52000.00, 'gst_percentage' => 18.00, 'hsn_code' => '9403', 'status' => 'active',
        ]);

        $pLoungeChair = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-CRF-CHR'], [
            'category_id' => $catDining->id, 'subcategory_id' => $subChairs->id,
            'name' => 'Craftline Nordic Boucle Upholstered Lounge Armchair', 'status' => 'active',
        ]);
        $items['lounge_chair'] = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-FND-CHR'], [
            'category_id' => $catDining->id, 'subcategory_id' => $subChairs->id, 'product_id' => $pLoungeChair->id, 'unit_id' => $uPcs->id,
            'name' => 'Nordic Boucle Upholstered Lounge Armchair', 'item_type' => 'sales_item', 'weight' => 18.0,
            'min_price' => 16000.00, 'normal_price' => 21000.00, 'max_price' => 24500.00, 'gst_percentage' => 18.00, 'hsn_code' => '9401', 'status' => 'active',
        ]);

        // 9. Staff Members (Monthly Salaried, Daily Wage, and Piece-Rate Craftsmen)
        $staffMembers = [];

        // Department Head & Supervisor (Salaried)
        $staffMembers['head_saw'] = $this->createStaff($company, $branchFactory, $deptSaw, [
            'employee_id' => 'EMP-CRF-001', 'first_name' => 'Gururaj', 'last_name' => 'Kulkarni',
            'designation' => 'Sawmill Operations Manager', 'role_position' => 'department_head',
            'employee_type' => 'salaried', 'salary_amount' => 55000.00,
        ]);

        $staffMembers['head_carpentry'] = $this->createStaff($company, $branchFactory, $deptCarpentry, [
            'employee_id' => 'EMP-CRF-002', 'first_name' => 'Ramesh', 'last_name' => 'Acharya',
            'designation' => 'Master Joinery Craftsman', 'role_position' => 'department_head',
            'employee_type' => 'salaried', 'salary_amount' => 60000.00,
        ]);

        $staffMembers['sup_carpentry'] = $this->createStaff($company, $branchFactory, $deptCarpentry, [
            'employee_id' => 'EMP-CRF-003', 'first_name' => 'Anand', 'last_name' => 'Vishwakarma',
            'designation' => 'Joinery Floor Supervisor', 'role_position' => 'supervisor',
            'supervisor_id' => $staffMembers['head_carpentry']->id,
            'employee_type' => 'salaried', 'salary_amount' => 38000.00,
        ]);

        // Piece-rate craftsmen (Paid per completed furniture piece!)
        $staffMembers['carpenter_piece1'] = $this->createStaff($company, $branchFactory, $deptCarpentry, [
            'employee_id' => 'EMP-CRF-004', 'first_name' => 'Shivanna', 'last_name' => 'Badiger',
            'designation' => 'Senior Table Joiner', 'role_position' => 'worker',
            'supervisor_id' => $staffMembers['sup_carpentry']->id,
            'employee_type' => 'piece_rate', 'piece_rate' => 1200.00, 'salary_amount' => 0,
        ]);

        $staffMembers['carpenter_piece2'] = $this->createStaff($company, $branchFactory, $deptCarpentry, [
            'employee_id' => 'EMP-CRF-005', 'first_name' => 'Manjunath', 'last_name' => 'Acharya',
            'designation' => 'Chair Assembly Joiner', 'role_position' => 'worker',
            'supervisor_id' => $staffMembers['sup_carpentry']->id,
            'employee_type' => 'piece_rate', 'piece_rate' => 500.00, 'salary_amount' => 0,
        ]);

        // Daily wage lumber handlers
        $staffMembers['daily_helper1'] = $this->createStaff($company, $branchFactory, $deptSaw, [
            'employee_id' => 'EMP-CRF-006', 'first_name' => 'Basappa', 'last_name' => 'Yadwad',
            'designation' => 'Lumber Yard Helper', 'role_position' => 'worker',
            'employee_type' => 'daily_wage', 'salary_amount' => 750.00, // ₹750/day
        ]);

        $staffMembers['daily_helper2'] = $this->createStaff($company, $branchFactory, $deptSaw, [
            'employee_id' => 'EMP-CRF-007', 'first_name' => 'Chandru', 'last_name' => 'Hosmani',
            'designation' => 'Sawmill Helper', 'role_position' => 'worker',
            'employee_type' => 'daily_wage', 'salary_amount' => 750.00,
        ]);

        // Showroom Manager & Sales
        $staffMembers['showroom_head'] = $this->createStaff($company, $branchShowroom, $deptSales, [
            'employee_id' => 'EMP-CRF-008', 'first_name' => 'Meera', 'last_name' => 'Nambiar',
            'designation' => 'Showroom Director', 'role_position' => 'department_head',
            'employee_type' => 'salaried', 'salary_amount' => 65000.00,
        ]);

        // Link Department Heads & Supervisors
        $deptSaw->update(['head_staff_id' => $staffMembers['head_saw']->id]);
        $deptCarpentry->update([
            'head_staff_id' => $staffMembers['head_carpentry']->id,
            'supervisor_staff_id' => $staffMembers['sup_carpentry']->id,
        ]);
        $deptSales->update(['head_staff_id' => $staffMembers['showroom_head']->id]);

        // 10. Suppliers & Customers
        $supplierTimber = $this->createSupplierWithLedger($company, $ledgers['creditorGroup'], [
            'name' => 'Nilambur Forest Teak & Hardwoods Corp', 'code' => 'SUP-CRF-TIM',
            'state' => 'Kerala', 'gst_number' => '32AAAFN5555M1Z0', 'opening_balance' => 0,
        ]);

        $supplierPaints = $this->createSupplierWithLedger($company, $ledgers['creditorGroup'], [
            'name' => 'Berger & Renner Wood Finishes Ltd', 'code' => 'SUP-CRF-POL',
            'state' => 'Karnataka', 'gst_number' => '29AAABR8888D1Z2', 'opening_balance' => 0,
        ]);

        $customerArchitect = $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
            'name' => 'Studio Morph Architects & Interiors', 'code' => 'CUST-CRF-ARC',
            'state' => 'Karnataka', 'gst_number' => '29AABCS9999K1Z4', 'opening_balance' => 0,
        ]);

        $customerTechPark = $this->createCustomerWithLedger($company, $ledgers['debtorGroup'], [
            'name' => 'Embassy Tech Village Workspaces', 'code' => 'CUST-CRF-EMB',
            'state' => 'Karnataka', 'gst_number' => '29AAACE7777E1Z9', 'opening_balance' => 0,
        ]);

        // 11. Opening Stock
        $this->recordOpeningStock($company, $branchFactory, $godownTimber, $items['teak_planks'], 150.0, 4500.00, 'BAT-TK-OPEN');
        $this->recordOpeningStock($company, $branchFactory, $godownTimber, $items['oak_logs'], 80.0, 3800.00, 'BAT-OK-OPEN');
        $this->recordOpeningStock($company, $branchFactory, $godownTimber, $items['ply_18mm'], 100.0, 2850.00, 'BAT-PLY-OPEN');
        $this->recordOpeningStock($company, $branchFactory, $godownCarpentry, $items['ss_screws'], 50.0, 280.00, 'BAT-SCR-OPEN');
        $this->recordOpeningStock($company, $branchFactory, $godownCarpentry, $items['pu_polish'], 60.0, 580.00, 'BAT-POL-OPEN');
        $this->recordOpeningStock($company, $branchFactory, $godownCarpentry, $items['boucle_fabric'], 120.0, 1100.00, 'BAT-FAB-OPEN');
        $this->recordOpeningStock($company, $branchShowroom, $godownShowroom, $items['dining_table'], 2.0, 42000.00, 'BAT-DT-OPEN');

        // 12. Procurement Transactions
        $this->recordPurchaseWorkflow($company, $branchFactory, $godownTimber, $supplierTimber, [
            ['item' => $items['teak_planks'], 'quantity' => 50.0, 'rate' => 4400.00, 'tax_percent' => 18, 'batch_no' => 'BAT-TK-OCT01'],
        ], [
            'po_number' => 'PO-CRF-2026-001', 'grn_number' => 'GRN-CRF-2026-001',
            'purchase_number' => 'PUR-CRF-2026-001', 'invoice_number' => 'BILL-NIL-8840',
            'date' => '2026-10-05', 'paid_amount' => 150000.00,
        ], $ledgers);

        // 13. Daily Operations Management Records (The Key Highlight!)
        $dailyOpService = new DailyOperationService($this->inventoryService);

        // Activity 1: Timber Sizing & Sawing in Sawmill Department
        $op1 = $dailyOpService->createOperation([
            'company_id' => $company->id,
            'branch_id' => $branchFactory->id,
            'department_id' => $deptSaw->id,
            'operation_number' => 'DOP-20261010-001',
            'operation_date' => '2026-10-10',
            'department_head_id' => $staffMembers['head_saw']->id,
            'supervisor_id' => null,
            'work_order_ref' => 'WO-CRF-SAW-01',
            'process_name' => 'Timber Sizing, Planking & Kiln Seasoning',
            'work_description' => 'Breakdown of raw timber logs into 2-inch and 3-inch calibrated furniture planks.',
            'start_time' => '08:30:00',
            'end_time' => '17:00:00',
            'work_status' => 'completed',
            'approval_status' => 'department_head_approved',
            'remarks' => 'Clean cut with zero knot defects. Kiln moisture verified at 9.5%.',
        ], [
            ['staff_id' => $staffMembers['daily_helper1']->id, 'employment_type' => 'daily_wage', 'hours_worked' => 8.0, 'units_completed' => 0, 'rate_per_unit' => 750.0],
            ['staff_id' => $staffMembers['daily_helper2']->id, 'employment_type' => 'daily_wage', 'hours_worked' => 8.0, 'units_completed' => 0, 'rate_per_unit' => 750.0],
        ], [
            ['item_id' => $items['oak_logs']->id, 'source_godown_id' => $godownTimber->id, 'batch_no' => 'BAT-OK-OPEN', 'consumed_quantity' => 12.0, 'unit_cost' => 3800.00],
        ], [
            ['item_id' => $items['teak_planks']->id, 'dest_godown_id' => $godownCarpentry->id, 'batch_number' => 'BAT-SAW-261010', 'produced_quantity' => 10.5, 'unit_cost' => 4500.00, 'quality_status' => 'passed'],
        ], [
            ['wastage_name' => 'Fine Hardwood Sawdust', 'wastage_type' => 'process_loss', 'quantity' => 1.2, 'unit' => 'CFT', 'estimated_cost' => 600.0, 'recoverable_quantity' => 1.2, 'reason' => 'Bandsaw kerf loss (sold to particle board plant)'],
            ['wastage_name' => 'Irregular Bark Edge Trimmings', 'wastage_type' => 'scrap_trimming', 'quantity' => 0.3, 'unit' => 'CFT', 'estimated_cost' => 300.0, 'recoverable_quantity' => 0.0, 'reason' => 'Sapwood edge squaring'],
        ]);

        // Activity 2: Table Joinery Assembly (Piece-Rate Master Craftsmen!)
        $op2 = $dailyOpService->createOperation([
            'company_id' => $company->id,
            'branch_id' => $branchFactory->id,
            'department_id' => $deptCarpentry->id,
            'operation_number' => 'DOP-20261012-002',
            'operation_date' => '2026-10-12',
            'department_head_id' => $staffMembers['head_carpentry']->id,
            'supervisor_id' => $staffMembers['sup_carpentry']->id,
            'work_order_ref' => 'WO-CRF-DT6-01',
            'process_name' => 'Precision Tenon & Mortise Joinery Assembly',
            'work_description' => 'Handcrafted structural assembly of solid Burma teak 6-seater dining table frames.',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'work_status' => 'completed',
            'approval_status' => 'department_head_approved',
            'remarks' => 'Completed 5 dining table frames. Structural joints inspected and approved.',
        ], [
            ['staff_id' => $staffMembers['carpenter_piece1']->id, 'employment_type' => 'piece_rate', 'hours_worked' => 9.0, 'units_completed' => 5, 'rate_per_unit' => 1200.00, 'remarks' => '5 dining tables assembled'],
        ], [
            ['item_id' => $items['teak_planks']->id, 'source_godown_id' => $godownTimber->id, 'batch_no' => 'BAT-TK-OPEN', 'consumed_quantity' => 35.0, 'unit_cost' => 4500.00],
            ['item_id' => $items['ss_screws']->id, 'source_godown_id' => $godownCarpentry->id, 'batch_no' => 'BAT-SCR-OPEN', 'consumed_quantity' => 5.0, 'unit_cost' => 280.00],
        ], [
            ['item_id' => $items['dining_table']->id, 'dest_godown_id' => $godownCarpentry->id, 'batch_number' => 'BAT-DT-261012', 'produced_quantity' => 5.0, 'unit_cost' => 41000.00, 'quality_status' => 'passed'],
        ], [
            ['wastage_name' => 'Hand Planer Wood Shavings', 'wastage_type' => 'scrap_trimming', 'quantity' => 0.8, 'unit' => 'CFT', 'estimated_cost' => 500.0, 'recoverable_quantity' => 0.8, 'reason' => 'Hand smoothing of table top panels'],
        ]);

        // Activity 3: Sanding, Staining & Polishing + Output Distribution
        $op3 = $dailyOpService->createOperation([
            'company_id' => $company->id,
            'branch_id' => $branchFactory->id,
            'department_id' => $deptPolish->id,
            'operation_number' => 'DOP-20261014-003',
            'operation_date' => '2026-10-14',
            'department_head_id' => $staffMembers['head_carpentry']->id,
            'supervisor_id' => $staffMembers['sup_carpentry']->id,
            'work_order_ref' => 'WO-CRF-POL-01',
            'process_name' => 'Surface Sanding, Sealer Coat & PU Polish',
            'work_description' => 'Multi-stage 400-grit sanding, natural walnut stain, and high-gloss PU protective coat.',
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'work_status' => 'completed',
            'approval_status' => 'department_head_approved',
            'remarks' => 'Mirror finish achieved on all 5 dining tables. Distributed to showroom and logistics van.',
        ], [
            ['staff_id' => $staffMembers['carpenter_piece2']->id, 'employment_type' => 'piece_rate', 'hours_worked' => 8.5, 'units_completed' => 5, 'rate_per_unit' => 500.00, 'remarks' => 'Polished 5 tables'],
        ], [
            ['item_id' => $items['pu_polish']->id, 'source_godown_id' => $godownCarpentry->id, 'batch_no' => 'BAT-POL-OPEN', 'consumed_quantity' => 10.0, 'unit_cost' => 580.00],
        ], [
            ['item_id' => $items['dining_table']->id, 'dest_godown_id' => $godownFinished->id, 'batch_number' => 'BAT-DT-POLISHED', 'produced_quantity' => 5.0, 'unit_cost' => 45000.00, 'quality_status' => 'passed'],
        ], [
            ['wastage_name' => 'Abrasive Sanding Dust & Cotton Wipes', 'wastage_type' => 'process_loss', 'quantity' => 3.5, 'unit' => 'KG', 'estimated_cost' => 400.0, 'recoverable_quantity' => 0.0, 'reason' => 'Surface prep wipe clean'],
        ], [
            // Multi-Destination Distribution!
            ['item_id' => $items['dining_table']->id, 'destination_godown_id' => $godownShowroom->id, 'destination_type' => 'factory_sales_counter', 'batch_no' => 'BAT-DT-POLISHED', 'quantity' => 2.0],
            ['item_id' => $items['dining_table']->id, 'destination_godown_id' => $godownVan->id, 'destination_type' => 'delivery_van', 'batch_no' => 'BAT-DT-POLISHED', 'quantity' => 2.0],
        ]);

        // 14. Sales Transactions
        $this->recordSalesWorkflow($company, $branchShowroom, $godownShowroom, $customerArchitect, [
            ['item' => $items['dining_table'], 'quantity' => 1.0, 'rate' => 68000.00, 'tax_percent' => 18, 'batch_no' => 'BAT-DT-POLISHED'],
        ], [
            'order_number' => 'SO-CRF-2026-001', 'invoice_number' => 'INV-CRF-2026-001',
            'delivery_number' => 'DEL-CRF-2026-001', 'date' => '2026-10-18', 'paid_amount' => 80240.00,
        ], $ledgers, $vehicleTruck);

        // 15. Operational Expenses
        $this->recordExpenseVoucher($company, $branchFactory, $ledgers['rentLedger'], $ledgers['bankLedger'], 85000.00, '2026-10-01', 'Peenya Works Factory Lease Oct 2026', 'EXP-CRF-001');
        $this->recordExpenseVoucher($company, $branchFactory, $ledgers['utilityLedger'], $ledgers['bankLedger'], 24500.00, '2026-10-15', 'BESCOM HT Power Peenya Works', 'EXP-CRF-002');

        // 16. User Accounts
        $superAdmin = User::where('email', 'admin@erp.com')->first();
        if ($superAdmin) {
            $superAdmin->companies()->syncWithoutDetaching([$company->id]);
        }

        $compAdminRole = Role::where('slug', 'company-admin')->first();
        User::firstOrCreate(['email' => 'furniture.admin@craftline.com'], [
            'name' => 'Devraj Gowda',
            'password' => Hash::make('password123'),
            'company_id' => $company->id,
            'branch_id' => $branchFactory->id,
            'role_id' => $compAdminRole?->id,
            'is_active' => true,
        ]);

        $this->command->info('Company D: Craftline Furniture Works seeded successfully!');
    }
}
