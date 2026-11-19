<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Company;
use App\Models\Branch;
use App\Models\Department;
use App\Models\Godown;
use App\Models\Unit;
use App\Models\Category;
use App\Models\Subcategory;
use App\Models\Item;
use App\Models\Product;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\Staff;
use App\Models\Shift;
use App\Models\Holiday;
use App\Models\User;
use App\Models\AccountGroup;
use App\Models\Ledger;
use App\Models\FinancialYear;
use App\Models\CompanyModule;
use App\Models\City;
use App\Models\Vehicle;
use App\Models\Route;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "=== TESTING SINGLE INDUSTRY SEED (MALABAR FOODS) ===\n";

DB::beginTransaction();

try {
    // 1. Company
    $company = Company::create([
        'name' => 'Malabar Taste Foods Pvt. Ltd.',
        'code' => 'MALABAR-FOODS',
        'registration_no' => 'U15400KL2021PTC068912',
        'gst_no' => '32AABCM1234F1Z8',
        'pan_no' => 'AABCM1234F',
        'address' => 'Malabar Food Park, Kakkanad, Kochi, Kerala 682030',
        'phone' => '+91 484 298 4400',
        'email' => 'info@malabarfoods.com',
        'website' => 'https://malabarfoods.example.com',
        'currency' => 'INR',
        'currency_symbol' => '₹',
        'financial_year_start' => '2026-04-01',
        'financial_year_end' => '2027-03-31',
        'business_type' => 'manufacturing',
        'status' => 'active',
    ]);
    echo "Created Company: {$company->name} (ID: {$company->id})\n";

    // 2. Financial Year
    FinancialYear::create([
        'company_id' => $company->id,
        'title' => 'FY 2026-2027',
        'start_date' => '2026-04-01',
        'end_date' => '2027-03-31',
        'is_closed' => false,
    ]);

    // 3. Modules
    $modules = ['staff_hr', 'inventory', 'purchase', 'sales', 'accounts', 'production', 'vehicle_route', 'quality', 'crm'];
    foreach ($modules as $mod) {
        CompanyModule::create([
            'company_id' => $company->id,
            'module_key' => $mod,
            'is_enabled' => true,
        ]);
    }

    // 4. Chart of Accounts
    $ca = AccountGroup::create(['company_id' => $company->id, 'code' => 'CA-01', 'name' => 'Current Assets', 'nature' => 'Assets']);
    $fa = AccountGroup::create(['company_id' => $company->id, 'code' => 'FA-01', 'name' => 'Fixed Assets', 'nature' => 'Assets']);
    $bk = AccountGroup::create(['company_id' => $company->id, 'code' => 'BK-01', 'name' => 'Bank & Cash Accounts', 'nature' => 'Assets', 'parent_id' => $ca->id]);
    $sd = AccountGroup::create(['company_id' => $company->id, 'code' => 'SD-01', 'name' => 'Sundry Debtors (Customers)', 'nature' => 'Assets', 'parent_id' => $ca->id]);
    $cl = AccountGroup::create(['company_id' => $company->id, 'code' => 'CL-01', 'name' => 'Current Liabilities', 'nature' => 'Liabilities']);
    $sc = AccountGroup::create(['company_id' => $company->id, 'code' => 'SC-01', 'name' => 'Sundry Creditors (Suppliers)', 'nature' => 'Liabilities', 'parent_id' => $cl->id]);
    $dt = AccountGroup::create(['company_id' => $company->id, 'code' => 'DT-01', 'name' => 'Duties & Taxes', 'nature' => 'Liabilities', 'parent_id' => $cl->id]);
    $eq = AccountGroup::create(['company_id' => $company->id, 'code' => 'EQ-01', 'name' => 'Capital & Reserves', 'nature' => 'Equity']);
    $rev = AccountGroup::create(['company_id' => $company->id, 'code' => 'REV-01', 'name' => 'Sales & Operating Revenue', 'nature' => 'Revenue']);
    $exp1 = AccountGroup::create(['company_id' => $company->id, 'code' => 'EXP-01', 'name' => 'Direct Expenses & Purchases', 'nature' => 'Expenses']);
    $exp2 = AccountGroup::create(['company_id' => $company->id, 'code' => 'EXP-02', 'name' => 'Administrative & Indirect Expenses', 'nature' => 'Expenses']);

    $ledgers = [
        ['name' => 'Cash in Hand', 'group' => $bk->id, 'type' => 'debit', 'is_bank_or_cash' => true],
        ['name' => 'Federal Bank Current A/C', 'group' => $bk->id, 'type' => 'debit', 'is_bank_or_cash' => true, 'bank_name' => 'Federal Bank', 'acc_no' => '1029384756', 'ifsc' => 'FDRL0001029'],
        ['name' => 'Sales Account', 'group' => $rev->id, 'type' => 'credit', 'is_bank_or_cash' => false],
        ['name' => 'Sales Return Account', 'group' => $rev->id, 'type' => 'debit', 'is_bank_or_cash' => false],
        ['name' => 'Purchase Account', 'group' => $exp1->id, 'type' => 'debit', 'is_bank_or_cash' => false],
        ['name' => 'Purchase Return Account', 'group' => $exp1->id, 'type' => 'credit', 'is_bank_or_cash' => false],
        ['name' => 'Salaries & Staff Wages', 'group' => $exp2->id, 'type' => 'debit', 'is_bank_or_cash' => false],
        ['name' => 'Facility Rent & Lease', 'group' => $exp2->id, 'type' => 'debit', 'is_bank_or_cash' => false],
        ['name' => 'Electricity & Utilities', 'group' => $exp2->id, 'type' => 'debit', 'is_bank_or_cash' => false],
        ['name' => 'Freight & Logistics Outward', 'group' => $exp2->id, 'type' => 'debit', 'is_bank_or_cash' => false],
        ['name' => 'GST Input Tax Credit', 'group' => $ca->id, 'type' => 'debit', 'is_bank_or_cash' => false],
        ['name' => 'GST Output Tax Liability', 'group' => $dt->id, 'type' => 'credit', 'is_bank_or_cash' => false],
        ['name' => 'PF & ESI Payable', 'group' => $dt->id, 'type' => 'credit', 'is_bank_or_cash' => false],
        ['name' => 'Promoters Share Capital', 'group' => $eq->id, 'type' => 'credit', 'is_bank_or_cash' => false],
    ];
    foreach ($ledgers as $l) {
        Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $l['group'],
            'name' => $l['name'],
            'opening_balance' => 0,
            'balance_type' => $l['type'],
            'current_balance' => 0,
            'is_bank_or_cash' => $l['is_bank_or_cash'],
            'bank_name' => $l['bank_name'] ?? null,
            'bank_account_no' => $l['acc_no'] ?? null,
            'bank_ifsc' => $l['ifsc'] ?? null,
            'status' => 'active',
        ]);
    }
    echo "Created Chart of Accounts (11 groups, " . count($ledgers) . " ledgers)\n";

    // 5. Branches
    $b1 = Branch::create([
        'company_id' => $company->id,
        'name' => 'Kochi Central Processing & HQ',
        'code' => 'BR-KOC-01',
        'address' => 'Kakkanad Industrial Zone, Kochi',
        'city' => 'Kochi',
        'state' => 'Kerala',
        'postal_code' => '682030',
        'contact_number' => '+91 484 298 4401',
        'email' => 'kochi@malabarfoods.com',
        'is_main_branch' => true,
        'status' => 'active',
    ]);
    $b2 = Branch::create([
        'company_id' => $company->id,
        'name' => 'Kozhikode Spices & Milling Plant',
        'code' => 'BR-CCJ-02',
        'address' => 'Mavoor Road Industrial Area, Kozhikode',
        'city' => 'Kozhikode',
        'state' => 'Kerala',
        'postal_code' => '673004',
        'contact_number' => '+91 495 272 8800',
        'email' => 'calicut@malabarfoods.com',
        'is_main_branch' => false,
        'status' => 'active',
    ]);
    $b3 = Branch::create([
        'company_id' => $company->id,
        'name' => 'Thrissur Distribution Centre',
        'code' => 'BR-TCR-03',
        'address' => 'Olarikkara Commercial Hub, Thrissur',
        'city' => 'Thrissur',
        'state' => 'Kerala',
        'postal_code' => '680012',
        'contact_number' => '+91 487 236 1120',
        'email' => 'thrissur@malabarfoods.com',
        'is_main_branch' => false,
        'status' => 'active',
    ]);
    echo "Created 3 Branches\n";

    // 6. Departments
    $depts = [
        ['name' => 'Production & Processing', 'code' => 'DEP-PROD', 'type' => 'production'],
        ['name' => 'Quality Assurance & Lab', 'code' => 'DEP-QA', 'type' => 'quality'],
        ['name' => 'Warehouse & Inventory', 'code' => 'DEP-WH', 'type' => 'inventory'],
        ['name' => 'Sales & Channel Distribution', 'code' => 'DEP-SALES', 'type' => 'sales'],
        ['name' => 'Finance & Accounts', 'code' => 'DEP-FIN', 'type' => 'accounts'],
    ];
    $deptModels = [];
    foreach ($depts as $d) {
        $deptModels[$d['code']] = Department::create([
            'company_id' => $company->id,
            'branch_id' => $b1->id,
            'name' => $d['name'],
            'code' => $d['code'],
            'department_type' => $d['type'],
            'status' => 'active',
        ]);
    }
    echo "Created " . count($deptModels) . " Departments\n";

    // 7. Godowns
    $g1 = Godown::create([
        'company_id' => $company->id,
        'branch_id' => $b1->id,
        'name' => 'Raw Spices & Grain Godown',
        'code' => 'GDW-KOC-RAW',
        'location_type' => 'warehouse',
        'status' => 'active',
    ]);
    $g2 = Godown::create([
        'company_id' => $company->id,
        'branch_id' => $b1->id,
        'name' => 'Finished Goods Packhouse',
        'code' => 'GDW-KOC-FG',
        'location_type' => 'store',
        'status' => 'active',
    ]);
    $g3 = Godown::create([
        'company_id' => $company->id,
        'branch_id' => $b2->id,
        'name' => 'Calicut Regional Hub Godown',
        'code' => 'GDW-CCJ-HUB',
        'location_type' => 'warehouse',
        'status' => 'active',
    ]);
    echo "Created 3 Godowns\n";

    // 8. Units
    $units = [
        ['name' => 'Kilogram', 'symbol' => 'KG', 'type' => 'weight'],
        ['name' => 'Gram', 'symbol' => 'GM', 'type' => 'weight'],
        ['name' => 'Metric Ton', 'symbol' => 'TON', 'type' => 'weight'],
        ['name' => 'Corrugated Box', 'symbol' => 'BOX', 'type' => 'quantity'],
        ['name' => 'Packet', 'symbol' => 'PKT', 'type' => 'quantity'],
    ];
    $unitModels = [];
    foreach ($units as $u) {
        $unitModels[$u['symbol']] = Unit::create([
            'company_id' => $company->id,
            'name' => $u['name'],
            'symbol' => $u['symbol'],
            'unit_type' => $u['type'],
            'conversion_factor' => 1.0,
            'status' => 'active',
        ]);
    }
    echo "Created " . count($unitModels) . " Units\n";

    // 9. Categories & Subcategories
    $cat1 = Category::create(['company_id' => $company->id, 'name' => 'Spices & Seasonings', 'description' => 'Whole spices and blended seasonings']);
    $sub1 = Subcategory::create(['company_id' => $company->id, 'category_id' => $cat1->id, 'name' => 'Whole Spices']);
    $sub2 = Subcategory::create(['company_id' => $company->id, 'category_id' => $cat1->id, 'name' => 'Ground Powders']);

    $cat2 = Category::create(['company_id' => $company->id, 'name' => 'Pickles & Condiments', 'description' => 'Traditional ethnic condiments']);
    $sub3 = Subcategory::create(['company_id' => $company->id, 'category_id' => $cat2->id, 'name' => 'Traditional Pickles']);

    $cat3 = Category::create(['company_id' => $company->id, 'name' => 'Packaging Materials', 'description' => 'Food grade pouches and shippers']);
    $sub4 = Subcategory::create(['company_id' => $company->id, 'category_id' => $cat3->id, 'name' => 'Printed Pouches']);

    // 10. Items (Raw materials)
    $items = [
        ['name' => 'Whole Black Pepper Grade 1', 'code' => 'RM-PEP-01', 'type' => 'raw_material', 'cat' => $cat1->id, 'sub' => $sub1->id, 'unit' => $unitModels['KG']->id, 'min' => 480, 'normal' => 520, 'max' => 560, 'gst' => 5, 'hsn' => '0904'],
        ['name' => 'Dry Red Chilli Whole (Guntur)', 'code' => 'RM-CHI-01', 'type' => 'raw_material', 'cat' => $cat1->id, 'sub' => $sub1->id, 'unit' => $unitModels['KG']->id, 'min' => 210, 'normal' => 230, 'max' => 250, 'gst' => 5, 'hsn' => '0904'],
        ['name' => 'Turmeric Whole Fingers (Salem)', 'code' => 'RM-TUR-01', 'type' => 'raw_material', 'cat' => $cat1->id, 'sub' => $sub1->id, 'unit' => $unitModels['KG']->id, 'min' => 140, 'normal' => 155, 'max' => 170, 'gst' => 5, 'hsn' => '0910'],
        ['name' => 'Printed Multi-Layer Spices Pouch 250g', 'code' => 'PM-PCH-250', 'type' => 'raw_material', 'cat' => $cat3->id, 'sub' => $sub4->id, 'unit' => $unitModels['PKT']->id, 'min' => 2.2, 'normal' => 2.5, 'max' => 3.0, 'gst' => 18, 'hsn' => '3923'],
    ];
    foreach ($items as $it) {
        Item::create([
            'company_id' => $company->id,
            'category_id' => $it['cat'],
            'subcategory_id' => $it['sub'],
            'unit_id' => $it['unit'],
            'name' => $it['name'],
            'code' => $it['code'],
            'item_type' => $it['type'],
            'min_price' => $it['min'],
            'normal_price' => $it['normal'],
            'max_price' => $it['max'],
            'gst_percentage' => $it['gst'],
            'hsn_code' => $it['hsn'],
            'reorder_quantity' => 100,
            'status' => 'active',
        ]);
    }

    // Finished Products (in products table + in items table for sales)
    $products = [
        ['name' => 'Malabar Special Garam Masala 250g', 'code' => 'FG-GM-250', 'cat' => $cat1->id, 'sub' => $sub2->id, 'price' => 110, 'gst' => 5, 'hsn' => '0910'],
        ['name' => 'Pure Turmeric Powder 500g', 'code' => 'FG-TUR-500', 'cat' => $cat1->id, 'sub' => $sub2->id, 'price' => 135, 'gst' => 5, 'hsn' => '0910'],
        ['name' => 'Tender Mango Pickle Jar 400g', 'code' => 'FG-PCK-400', 'cat' => $cat2->id, 'sub' => $sub3->id, 'price' => 95, 'gst' => 12, 'hsn' => '2001'],
    ];
    foreach ($products as $p) {
        $prod = Product::create([
            'company_id' => $company->id,
            'category_id' => $p['cat'],
            'subcategory_id' => $p['sub'],
            'name' => $p['name'],
            'code' => $p['code'],
            'description' => "High grade {$p['name']}",
            'status' => 'active',
        ]);

        Item::create([
            'company_id' => $company->id,
            'category_id' => $p['cat'],
            'subcategory_id' => $p['sub'],
            'product_id' => $prod->id,
            'unit_id' => $unitModels['PKT']->id,
            'name' => $p['name'],
            'code' => $p['code'],
            'item_type' => 'sales_item',
            'min_price' => $p['price'] * 0.9,
            'normal_price' => $p['price'],
            'max_price' => $p['price'] * 1.15,
            'gst_percentage' => $p['gst'],
            'hsn_code' => $p['hsn'],
            'reorder_quantity' => 50,
            'status' => 'active',
        ]);
    }
    echo "Created Items & Products\n";

    // 11. Suppliers
    $supps = [
        ['name' => 'Wayanad Pepper Planters Co-op', 'code' => 'SUP-WYD-01', 'contact' => 'Varghese Mathew', 'phone' => '+91 94471 23456', 'email' => 'sales@wayanadpepper.org', 'city' => 'Kalpetta', 'gst' => '32AAAFW1122D1Z4'],
        ['name' => 'Guntur Spices Trading Company', 'code' => 'SUP-GNT-02', 'contact' => 'K. R. Rao', 'phone' => '+91 86322 34567', 'email' => 'orders@gunturspices.in', 'city' => 'Guntur', 'gst' => '37AAACG5566G1Z2'],
        ['name' => 'Apex Flexible Packaging Systems', 'code' => 'SUP-APX-03', 'contact' => 'Dinesh Sharma', 'phone' => '+91 98201 98765', 'email' => 'dinesh@apexpackaging.in', 'city' => 'Mumbai', 'gst' => '27AAACA9988H1Z1'],
    ];
    foreach ($supps as $s) {
        $sLedger = Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $sc->id,
            'name' => $s['name'],
            'opening_balance' => 0,
            'balance_type' => 'credit',
            'current_balance' => 0,
            'is_bank_or_cash' => false,
            'status' => 'active',
        ]);

        Supplier::create([
            'company_id' => $company->id,
            'code' => $s['code'],
            'name' => $s['name'],
            'contact_person' => $s['contact'],
            'phone' => $s['phone'],
            'email' => $s['email'],
            'city' => $s['city'],
            'gst_number' => $s['gst'],
            'account_group_id' => $sc->id,
            'ledger_id' => $sLedger->id,
            'opening_balance' => 0,
            'account_type' => 'credit',
            'current_balance' => 0,
            'status' => 'active',
        ]);
    }
    echo "Created " . count($supps) . " Suppliers\n";

    // 12. Customers
    $custs = [
        ['name' => 'Nilgiri Supermarkets Chain', 'code' => 'CUS-NIL-01', 'shop' => 'Nilgiri Superstores Kochi', 'phone' => '+91 98460 11223', 'email' => 'purchase@nilgiris.com', 'city' => 'Kochi', 'gst' => '32AAACN4455E1Z9'],
        ['name' => 'Cochin Hypermarkets Ltd.', 'code' => 'CUS-CHM-02', 'shop' => 'Cochin Hypermarket MG Road', 'phone' => '+91 98470 22334', 'email' => 'cochin@hypermarkets.in', 'city' => 'Kochi', 'gst' => '32AAACC7788P1Z6'],
        ['name' => 'Malabar Family Restaurant Chain', 'code' => 'CUS-MFR-03', 'shop' => 'Malabar Restaurant Kozhikode', 'phone' => '+91 94470 33445', 'email' => 'chef@malabarrest.com', 'city' => 'Kozhikode', 'gst' => '32AAACM3344M1Z5'],
        ['name' => 'Calicut Daily Fresh Mart', 'code' => 'CUS-CDF-04', 'shop' => 'Daily Fresh Palayam', 'phone' => '+91 98465 44556', 'email' => 'dailyfresh@calicut.in', 'city' => 'Kozhikode', 'gst' => '32AAACD6677K1Z3'],
    ];
    foreach ($custs as $cu) {
        $cLedger = Ledger::create([
            'company_id' => $company->id,
            'account_group_id' => $sd->id,
            'name' => $cu['name'],
            'opening_balance' => 0,
            'balance_type' => 'debit',
            'current_balance' => 0,
            'is_bank_or_cash' => false,
            'status' => 'active',
        ]);

        Customer::create([
            'company_id' => $company->id,
            'code' => $cu['code'],
            'name' => $cu['name'],
            'shop_name' => $cu['shop'],
            'phone' => $cu['phone'],
            'email' => $cu['email'],
            'city' => $cu['city'],
            'gst_number' => $cu['gst'],
            'account_group_id' => $sd->id,
            'ledger_id' => $cLedger->id,
            'price_level' => 'normal',
            'opening_balance' => 0,
            'account_type' => 'debit',
            'current_balance' => 0,
            'status' => 'active',
        ]);
    }
    echo "Created " . count($custs) . " Customers\n";

    // 13. Staff (5 diverse employment types)
    $staffList = [
        ['first' => 'Ramesh', 'last' => 'Pillai', 'code' => 'STF-MAL-01', 'desig' => 'Plant Operations Head', 'type' => 'salaried', 'sal' => 55000, 'basic' => 45000, 'hourly' => 0, 'piece' => 0, 'dept' => 'DEP-PROD', 'pf' => true, 'esi' => false],
        ['first' => 'Anil', 'last' => 'Kumar', 'code' => 'STF-MAL-02', 'desig' => 'Milling Machine Operator', 'type' => 'hourly', 'sal' => 0, 'basic' => 0, 'hourly' => 175, 'piece' => 0, 'dept' => 'DEP-PROD', 'pf' => false, 'esi' => true],
        ['first' => 'Sujatha', 'last' => 'Menon', 'code' => 'STF-MAL-03', 'desig' => 'Quality Lab Chemist', 'type' => 'contract', 'sal' => 32000, 'basic' => 32000, 'hourly' => 0, 'piece' => 0, 'dept' => 'DEP-QA', 'pf' => false, 'esi' => false],
        ['first' => 'Babu', 'last' => 'Raj', 'code' => 'STF-MAL-04', 'desig' => 'Spice Packing Worker', 'type' => 'piece_rate', 'sal' => 0, 'basic' => 0, 'hourly' => 0, 'piece' => 12.5, 'dept' => 'DEP-PROD', 'pf' => false, 'esi' => false],
        ['first' => 'Mani', 'last' => 'Kandan', 'code' => 'STF-MAL-05', 'desig' => 'Loading & Warehouse Helper', 'type' => 'daily_wage', 'sal' => 850, 'basic' => 0, 'hourly' => 0, 'piece' => 0, 'dept' => 'DEP-WH', 'pf' => false, 'esi' => true],
    ];
    foreach ($staffList as $st) {
        Staff::create([
            'company_id' => $company->id,
            'branch_id' => $b1->id,
            'department_id' => $deptModels[$st['dept']]->id,
            'employee_id' => $st['code'],
            'first_name' => $st['first'],
            'last_name' => $st['last'],
            'email' => strtolower($st['first'] . '.' . $st['last'] . '@malabarfoods.com'),
            'phone' => '+91 9447' . rand(100000, 999999),
            'designation' => $st['desig'],
            'employee_type' => $st['type'],
            'salary_amount' => $st['sal'],
            'basic_salary' => $st['basic'],
            'hourly_rate' => $st['hourly'],
            'piece_rate' => $st['piece'],
            'date_of_joining' => '2026-04-01',
            'pf_applicable' => $st['pf'],
            'esi_applicable' => $st['esi'],
            'status' => 'active',
        ]);
    }
    echo "Created 5 Staff (Salaried, Hourly, Contract, Piece-rate, Daily wage)\n";

    // 14. Shifts & Holidays
    Shift::create([
        'company_id' => $company->id,
        'branch_id' => $b1->id,
        'shift_name' => 'General Processing Shift',
        'start_time' => '08:30:00',
        'end_time' => '17:30:00',
        'break_duration_minutes' => 60,
        'status' => 'active',
    ]);
    Holiday::create([
        'company_id' => $company->id,
        'branch_id' => $b1->id,
        'holiday_name' => 'Onam Festival',
        'holiday_date' => '2026-08-28',
        'holiday_type' => 'company',
        'description' => 'Regional Public Holiday for Onam',
    ]);
    echo "Created Shift & Holiday\n";

    // 15. Demo Users
    $users = [
        ['name' => 'Malabar Admin', 'email' => 'admin@malabarfoods.com', 'role_id' => 2],
        ['name' => 'Malabar Accounts', 'email' => 'accounts@malabarfoods.com', 'role_id' => 3],
        ['name' => 'Malabar Warehouse', 'email' => 'warehouse@malabarfoods.com', 'role_id' => 4],
        ['name' => 'Malabar Sales', 'email' => 'sales@malabarfoods.com', 'role_id' => 5],
    ];
    foreach ($users as $u) {
        $uModel = User::create([
            'company_id' => $company->id,
            'branch_id' => $b1->id,
            'role_id' => $u['role_id'],
            'name' => $u['name'],
            'email' => $u['email'],
            'password' => Hash::make('password'),
            'is_active' => true,
        ]);
        DB::table('user_company_branches')->insert([
            'user_id' => $uModel->id,
            'company_id' => $company->id,
            'branch_id' => $b1->id,
        ]);
    }
    echo "Created 4 Demo Users with 'password'\n";

    // Always rollback during test
    DB::rollBack();
    echo "\n>>> SINGLE INDUSTRY TEST COMPLETED PERFECTLY! (Rolled back) <<<\n";

} catch (\Exception $e) {
    DB::rollBack();
    echo "ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
}
