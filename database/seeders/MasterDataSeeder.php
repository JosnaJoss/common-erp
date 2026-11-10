<?php

namespace Database\Seeders;

use App\Models\AccountGroup;
use App\Models\Category;
use App\Models\City;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\Item;
use App\Models\Ledger;
use App\Models\Location;
use App\Models\Product;
use App\Models\Route;
use App\Models\Shift;
use App\Models\Staff;
use App\Models\Subcategory;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Database\Seeder;

class MasterDataSeeder extends Seeder
{
    public function run(): void
    {
        $company = Company::where('code', 'APEX-IND')->first();
        if (!$company) return;

        // 1. Units of Measure
        $unitPcs = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'PCS'], [
            'name' => 'Pieces', 'unit_type' => 'quantity', 'conversion_factor' => 1.0000, 'status' => 'active',
        ]);
        $unitBox = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'BOX'], [
            'name' => 'Box (10 Pcs)', 'unit_type' => 'quantity', 'conversion_factor' => 10.0000, 'status' => 'active',
        ]);
        $unitKg = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'KG'], [
            'name' => 'Kilogram', 'unit_type' => 'weight', 'conversion_factor' => 1.0000, 'status' => 'active',
        ]);
        $unitLtr = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'LTR'], [
            'name' => 'Litre', 'unit_type' => 'volume', 'conversion_factor' => 1.0000, 'status' => 'active',
        ]);
        $unitMtr = Unit::firstOrCreate(['company_id' => $company->id, 'symbol' => 'MTR'], [
            'name' => 'Meter', 'unit_type' => 'length', 'conversion_factor' => 1.0000, 'status' => 'active',
        ]);

        // 2. Categories & Subcategories
        $catRaw = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Industrial Raw Materials'], [
            'description' => 'Raw steel, copper, sheets and casting blocks', 'status' => 'active',
        ]);
        $subSteel = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catRaw->id, 'name' => 'Alloy & Mild Steel'], [
            'description' => 'Structural steel bars, plates and billets', 'status' => 'active',
        ]);
        $subCopper = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catRaw->id, 'name' => 'Copper & Wiring'], [
            'description' => 'Winding wire, cables and copper rods', 'status' => 'active',
        ]);

        $catMach = Category::firstOrCreate(['company_id' => $company->id, 'name' => 'Industrial Machinery & Motors'], [
            'description' => 'Electrical motors, gearboxes and actuators', 'status' => 'active',
        ]);
        $subMotors = Subcategory::firstOrCreate(['company_id' => $company->id, 'category_id' => $catMach->id, 'name' => 'Electric Motors (AC/DC)'], [
            'description' => 'Three phase induction motors and DC servos', 'status' => 'active',
        ]);

        // 3. Products
        $prodMotor5HP = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-MTR-5HP'], [
            'category_id' => $catMach->id,
            'subcategory_id' => $subMotors->id,
            'name' => 'Apex 5HP Heavy Duty 3-Phase Induction Motor',
            'description' => 'High torque 1440 RPM industrial electric motor',
            'status' => 'active',
        ]);

        $prodSteelRod = Product::firstOrCreate(['company_id' => $company->id, 'code' => 'PRD-STL-ROD'], [
            'category_id' => $catRaw->id,
            'subcategory_id' => $subSteel->id,
            'name' => 'SS 304 High Grade Stainless Steel Rods',
            'description' => 'Corrosion resistant industrial grade rods',
            'status' => 'active',
        ]);

        // 4. Items (With 3-tier pricing: min_price, normal_price, max_price)
        $itemMotor5HP = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-MTR-001'], [
            'category_id' => $catMach->id,
            'subcategory_id' => $subMotors->id,
            'product_id' => $prodMotor5HP->id,
            'unit_id' => $unitPcs->id,
            'name' => 'Apex 5HP Induction Motor 415V',
            'item_type' => 'sales_item',
            'weight' => 28.500,
            'min_price' => 14500.00,
            'normal_price' => 16500.00,
            'max_price' => 18000.00,
            'gst_percentage' => 18.00,
            'hsn_code' => '8501',
            'reorder_quantity' => 5.00,
            'status' => 'active',
        ]);

        $itemMotor3HP = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-MTR-002'], [
            'category_id' => $catMach->id,
            'subcategory_id' => $subMotors->id,
            'product_id' => $prodMotor5HP->id,
            'unit_id' => $unitPcs->id,
            'name' => 'Apex 3HP Compact Induction Motor',
            'item_type' => 'sales_item',
            'weight' => 18.200,
            'min_price' => 9500.00,
            'normal_price' => 11000.00,
            'max_price' => 12500.00,
            'gst_percentage' => 18.00,
            'hsn_code' => '8501',
            'reorder_quantity' => 10.00,
            'status' => 'active',
        ]);

        $itemSteelRod25 = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-STL-025'], [
            'category_id' => $catRaw->id,
            'subcategory_id' => $subSteel->id,
            'product_id' => $prodSteelRod->id,
            'unit_id' => $unitKg->id,
            'name' => 'SS 304 Solid Steel Rod 25mm dia',
            'item_type' => 'both',
            'weight' => 1.000,
            'min_price' => 280.00,
            'normal_price' => 320.00,
            'max_price' => 350.00,
            'gst_percentage' => 18.00,
            'hsn_code' => '7222',
            'reorder_quantity' => 100.00,
            'status' => 'active',
        ]);

        $itemCopperWire = Item::firstOrCreate(['company_id' => $company->id, 'code' => 'ITM-COP-001'], [
            'category_id' => $catRaw->id,
            'subcategory_id' => $subCopper->id,
            'unit_id' => $unitKg->id,
            'name' => 'Insulated Copper Winding Wire 1.2mm',
            'item_type' => 'raw_material',
            'weight' => 1.000,
            'min_price' => 750.00,
            'normal_price' => 820.00,
            'max_price' => 900.00,
            'gst_percentage' => 18.00,
            'hsn_code' => '8544',
            'reorder_quantity' => 50.00,
            'status' => 'active',
        ]);

        // 5. Suppliers with Ledger connection
        $creditorGroup = AccountGroup::where('company_id', $company->id)->where('name', 'Sundry Creditors (Suppliers)')->first();

        $supp1Ledger = Ledger::firstOrCreate(['company_id' => $company->id, 'name' => 'Tata Steel Tubes & Alloys Ltd'], [
            'account_group_id' => $creditorGroup->id,
            'opening_balance' => 45000.00,
            'balance_type' => 'credit',
            'current_balance' => 45000.00,
            'is_bank_or_cash' => false,
            'status' => 'active',
        ]);

        $supp1 = Supplier::firstOrCreate(['company_id' => $company->id, 'code' => 'SUPP-TATA-01'], [
            'name' => 'Tata Steel Tubes & Alloys Ltd',
            'contact_person' => 'Arun Mehra',
            'phone' => '+91 22 6665 8282',
            'email' => 'sales@tatasteel.example.com',
            'address' => 'Bombay House, 24 Homi Mody Street, Fort, Mumbai 400001',
            'pincode' => '400001',
            'city' => 'Mumbai',
            'gst_number' => '27AAACT2727Q1ZW',
            'pan_number' => 'AAACT2727Q',
            'account_group_id' => $creditorGroup->id,
            'ledger_id' => $supp1Ledger->id,
            'opening_balance' => 45000.00,
            'opening_balance_date' => '2026-04-01',
            'account_type' => 'credit',
            'current_balance' => 45000.00,
            'status' => 'active',
        ]);

        $supp2Ledger = Ledger::firstOrCreate(['company_id' => $company->id, 'name' => 'Bharat Electrical Conductors Ltd'], [
            'account_group_id' => $creditorGroup->id,
            'opening_balance' => 28000.00,
            'balance_type' => 'credit',
            'current_balance' => 28000.00,
            'is_bank_or_cash' => false,
            'status' => 'active',
        ]);

        $supp2 = Supplier::firstOrCreate(['company_id' => $company->id, 'code' => 'SUPP-BHEL-02'], [
            'name' => 'Bharat Electrical Conductors Ltd',
            'contact_person' => 'Deepak Chauhan',
            'phone' => '+91 22 2577 9100',
            'email' => 'contact@bharatelectricals.example.com',
            'address' => 'LBS Marg, Bhandup West, Mumbai 400078',
            'pincode' => '400078',
            'city' => 'Mumbai',
            'gst_number' => '27BBBCB4545P1ZX',
            'pan_number' => 'BBBCB4545P',
            'account_group_id' => $creditorGroup->id,
            'ledger_id' => $supp2Ledger->id,
            'opening_balance' => 28000.00,
            'opening_balance_date' => '2026-04-01',
            'account_type' => 'credit',
            'current_balance' => 28000.00,
            'status' => 'active',
        ]);

        // 6. Customers with Route, Price Tier, and Ledger connection
        $debtorGroup = AccountGroup::where('company_id', $company->id)->where('name', 'Sundry Debtors (Customers)')->first();
        $route = Route::where('company_id', $company->id)->first();
        $location = Location::where('company_id', $company->id)->first();

        $cust1Ledger = Ledger::firstOrCreate(['company_id' => $company->id, 'name' => 'Mahavir Engineering Works'], [
            'account_group_id' => $debtorGroup->id,
            'opening_balance' => 85000.00,
            'balance_type' => 'debit',
            'current_balance' => 85000.00,
            'is_bank_or_cash' => false,
            'status' => 'active',
        ]);

        Customer::firstOrCreate(['company_id' => $company->id, 'code' => 'CUST-MAH-001'], [
            'name' => 'Mahavir Engineering Works',
            'shop_name' => 'Mahavir Machine Tools',
            'phone' => '+91 9820123456',
            'email' => 'orders@mahavireng.example.com',
            'address' => 'Unit 5, Royal Industrial Estate, Andheri West, Mumbai',
            'pincode' => '400058',
            'city' => 'Mumbai',
            'territory' => 'Western Suburbs',
            'route_id' => $route?->id,
            'location_id' => $location?->id,
            'gst_number' => '27AABCM8899M1Z1',
            'pan_number' => 'AABCM8899M',
            'account_group_id' => $debtorGroup->id,
            'ledger_id' => $cust1Ledger->id,
            'price_level' => 'normal',
            'opening_balance' => 85000.00,
            'opening_balance_date' => '2026-04-01',
            'account_type' => 'debit',
            'current_balance' => 85000.00,
            'status' => 'active',
        ]);

        $cust2Ledger = Ledger::firstOrCreate(['company_id' => $company->id, 'name' => 'Larsen Infrastructure Contractors'], [
            'account_group_id' => $debtorGroup->id,
            'opening_balance' => 140000.00,
            'balance_type' => 'debit',
            'current_balance' => 140000.00,
            'is_bank_or_cash' => false,
            'status' => 'active',
        ]);

        Customer::firstOrCreate(['company_id' => $company->id, 'code' => 'CUST-LAR-002'], [
            'name' => 'Larsen Infrastructure Contractors',
            'shop_name' => 'Larsen Project Yard',
            'phone' => '+91 9820987654',
            'email' => 'procurement@larseninfra.example.com',
            'address' => 'G-Block, Bandra Kurla Complex, Mumbai',
            'pincode' => '400051',
            'city' => 'Mumbai',
            'territory' => 'BKC Commercial Hub',
            'route_id' => $route?->id,
            'location_id' => $location?->id,
            'gst_number' => '27AALCL3344L1Z9',
            'pan_number' => 'AALCL3344L',
            'account_group_id' => $debtorGroup->id,
            'ledger_id' => $cust2Ledger->id,
            'price_level' => 'minimum', // Wholesale discount price level
            'opening_balance' => 140000.00,
            'opening_balance_date' => '2026-04-01',
            'account_type' => 'debit',
            'current_balance' => 140000.00,
            'status' => 'active',
        ]);

        // 7. Staff Master Data
        $branch = $company->branches()->first();
        $deptAccounts = Department::where('company_id', $company->id)->where('code', 'DEPT-ACC')->first();
        $deptProd = Department::where('company_id', $company->id)->where('code', 'DEPT-PROD')->first();

        Staff::firstOrCreate(['company_id' => $company->id, 'employee_id' => 'EMP-IND-001'], [
            'branch_id' => $branch?->id,
            'department_id' => $deptAccounts?->id,
            'first_name' => 'Sunil',
            'last_name' => 'Gupta',
            'email' => 'sunil.gupta@apexindustrial.com',
            'phone' => '+91 9820055667',
            'aadhaar_number' => '7845 1290 3456',
            'pan_number' => 'ABCPG1234D',
            'date_of_birth' => '1985-06-15',
            'gender' => 'Male',
            'blood_group' => 'B+',
            'highest_education' => 'M.Com, Chartered Accountant (Inter)',
            'marital_status' => 'Married',
            'permanent_address' => 'Flat 402, Shanti Heights, Thane West 400602',
            'temporary_address' => 'Flat 402, Shanti Heights, Thane West 400602',
            'emergency_contact_person' => 'Meena Gupta',
            'emergency_contact_relation' => 'Spouse',
            'emergency_contact_number' => '+91 9820055668',
            'designation' => 'Senior Accounts Manager',
            'employee_type' => 'salaried',
            'salary_amount' => 65000.00,
            'date_of_joining' => '2021-08-01',
            'status' => 'active',
            'basic_salary' => 35000.00,
            'hra' => 17500.00,
            'other_allowances' => 12500.00,
            'standard_deductions' => 0.00,
            'pf_applicable' => true,
            'pf_number' => 'MH/BAN/0012345/000/000101',
            'pf_uan' => '100987654321',
            'pf_rate_percent' => 12.00,
            'esi_applicable' => false,
            'bank_name' => 'HDFC Bank Ltd',
            'bank_ifsc' => 'HDFC0000123',
            'bank_branch' => 'Thane Station Branch',
            'bank_account_type' => 'Salary',
            'bank_holder_name' => 'Sunil R Gupta',
            'bank_account_number' => '50100234567890',
        ]);

        Staff::firstOrCreate(['company_id' => $company->id, 'employee_id' => 'EMP-IND-002'], [
            'branch_id' => $branch?->id,
            'department_id' => $deptProd?->id,
            'first_name' => 'Vikram',
            'last_name' => 'Deshmukh',
            'email' => 'vikram.d@apexindustrial.com',
            'phone' => '+91 9820088990',
            'aadhaar_number' => '8912 3456 7890',
            'pan_number' => 'ABCPD5678K',
            'date_of_birth' => '1988-11-20',
            'gender' => 'Male',
            'blood_group' => 'O+',
            'highest_education' => 'B.E. Mechanical Engineering',
            'marital_status' => 'Married',
            'permanent_address' => 'Plot 18, Sahakar Nagar, Pune 411009',
            'temporary_address' => 'Bldg 3, MHADA Colony, Andheri East, Mumbai 400093',
            'emergency_contact_person' => 'Sneha Deshmukh',
            'emergency_contact_relation' => 'Spouse',
            'emergency_contact_number' => '+91 9820088991',
            'designation' => 'Production Supervisor',
            'employee_type' => 'salaried',
            'salary_amount' => 55000.00,
            'date_of_joining' => '2022-03-15',
            'status' => 'active',
            'basic_salary' => 30000.00,
            'hra' => 15000.00,
            'other_allowances' => 10000.00,
            'pf_applicable' => true,
            'pf_number' => 'MH/BAN/0012345/000/000102',
            'pf_uan' => '100987654322',
            'pf_rate_percent' => 12.00,
            'esi_applicable' => false,
            'license_number' => 'MH-03-2015-0012345',
            'license_class' => 'LMV / Transport',
            'license_expiry_date' => '2035-11-19',
            'bank_name' => 'State Bank of India',
            'bank_ifsc' => 'SBIN0000456',
            'bank_branch' => 'Andheri East Branch',
            'bank_account_type' => 'Salary',
            'bank_holder_name' => 'Vikram S Deshmukh',
            'bank_account_number' => '30456789012',
        ]);

        // 8. Shifts & Holidays
        Shift::firstOrCreate(['company_id' => $company->id, 'shift_name' => 'General Day Shift'], [
            'branch_id' => $branch?->id,
            'department_id' => null,
            'start_time' => '09:00:00',
            'end_time' => '18:00:00',
            'break_duration_minutes' => 60,
            'status' => 'active',
        ]);

        Shift::firstOrCreate(['company_id' => $company->id, 'shift_name' => 'Plant Morning Shift'], [
            'branch_id' => $branch?->id,
            'department_id' => $deptProd?->id,
            'start_time' => '07:00:00',
            'end_time' => '15:30:00',
            'break_duration_minutes' => 45,
            'status' => 'active',
        ]);

        Holiday::firstOrCreate(['company_id' => $company->id, 'holiday_name' => 'Republic Day'], [
            'branch_id' => $branch?->id,
            'holiday_date' => '2026-01-26',
            'holiday_type' => 'National',
            'description' => 'Republic Day of India',
        ]);
        Holiday::firstOrCreate(['company_id' => $company->id, 'holiday_name' => 'Independence Day'], [
            'branch_id' => $branch?->id,
            'holiday_date' => '2026-08-15',
            'holiday_type' => 'National',
            'description' => 'Independence Day celebration',
        ]);
        Holiday::firstOrCreate(['company_id' => $company->id, 'holiday_name' => 'Diwali Festive Holiday'], [
            'branch_id' => $branch?->id,
            'holiday_date' => '2026-11-08',
            'holiday_type' => 'Festival',
            'description' => 'Deepavali celebration',
        ]);
    }
}

