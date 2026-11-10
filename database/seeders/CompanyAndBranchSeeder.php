<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\Department;
use App\Models\Godown;
use App\Models\Location;
use App\Models\Role;
use App\Models\Route;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CompanyAndBranchSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Company 1 (Manufacturing / Distribution / Trading)
        $company1 = Company::firstOrCreate(['code' => 'APEX-IND'], [
            'name' => 'Apex Industrial Solutions Pvt Ltd',
            'registration_no' => 'U29100MH2020PTC123456',
            'gst_no' => '27AAACA1234A1Z5',
            'pan_no' => 'AAACA1234A',
            'address' => 'Plot 42, Industrial Area, Phase II, Andheri East, Mumbai, Maharashtra 400093',
            'phone' => '+91 22 2847 9000',
            'email' => 'contact@apexindustrial.com',
            'website' => 'https://apexindustrial.example.com',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'financial_year_start' => '2026-04-01',
            'financial_year_end' => '2027-03-31',
            'business_type' => 'manufacturing',
            'status' => 'active',
        ]);

        // Enable all modules for Company 1
        $modules = ['staff_hr', 'inventory', 'purchase', 'sales', 'accounts', 'production', 'vehicle_route', 'quality', 'crm'];
        foreach ($modules as $mod) {
            CompanyModule::firstOrCreate(['company_id' => $company1->id, 'module_key' => $mod], ['is_enabled' => true]);
        }

        // 2. Create Company 2 (Wholesale & Retail)
        $company2 = Company::firstOrCreate(['code' => 'APEX-MART'], [
            'name' => 'Apex Wholesale & Retail Mart',
            'registration_no' => 'U52100DL2021PTC654321',
            'gst_no' => '07BBBCB5678B1Z2',
            'pan_no' => 'BBBCB5678B',
            'address' => 'Shop 12-16, Commercial Center, Connaught Place, New Delhi 110001',
            'phone' => '+91 11 4150 8800',
            'email' => 'info@apexmart.com',
            'website' => 'https://apexmart.example.com',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'financial_year_start' => '2026-04-01',
            'financial_year_end' => '2027-03-31',
            'business_type' => 'wholesale',
            'status' => 'active',
        ]);

        foreach ($modules as $mod) {
            $enabled = !in_array($mod, ['production', 'quality']); // Retail doesn't need factory production
            CompanyModule::firstOrCreate(['company_id' => $company2->id, 'module_key' => $mod], ['is_enabled' => $enabled]);
        }

        // 3. Create Company 3 (Service Company)
        $company3 = Company::firstOrCreate(['code' => 'APEX-SERV'], [
            'name' => 'Apex Engineering & Technical Services',
            'registration_no' => 'U74900KA2022PTC987654',
            'gst_no' => '29CCCC9876C1Z8',
            'pan_no' => 'CCCCC9876C',
            'address' => 'Tech Park, 4th Floor, Whitefield, Bangalore, Karnataka 560066',
            'phone' => '+91 80 4960 7000',
            'email' => 'services@apexengineering.com',
            'website' => 'https://apexservices.example.com',
            'currency' => 'INR',
            'currency_symbol' => '₹',
            'financial_year_start' => '2026-04-01',
            'financial_year_end' => '2027-03-31',
            'business_type' => 'service',
            'status' => 'active',
        ]);

        foreach ($modules as $mod) {
            $enabled = in_array($mod, ['staff_hr', 'accounts', 'crm', 'sales']); // Service disables inventory, production, etc.
            CompanyModule::firstOrCreate(['company_id' => $company3->id, 'module_key' => $mod], ['is_enabled' => $enabled]);
        }

        // 4. Create Branches for Company 1
        $branchMumbai = Branch::firstOrCreate(['company_id' => $company1->id, 'code' => 'BUM-HO'], [
            'name' => 'Mumbai Head Office & Plant',
            'address' => 'Plot 42, Andheri East',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'postal_code' => '400093',
            'contact_number' => '+91 22 2847 9001',
            'email' => 'mumbai@apexindustrial.com',
            'branch_manager' => 'Rajesh Sharma',
            'is_main_branch' => true,
            'status' => 'active',
        ]);

        $branchPune = Branch::firstOrCreate(['company_id' => $company1->id, 'code' => 'PUN-WH'], [
            'name' => 'Pune Regional Depot',
            'address' => 'MIDC Bhosari, Pune',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'postal_code' => '411026',
            'contact_number' => '+91 20 2712 3456',
            'email' => 'pune@apexindustrial.com',
            'branch_manager' => 'Vikas Patil',
            'is_main_branch' => false,
            'status' => 'active',
        ]);

        // 5. Create Departments
        $depts = [
            ['name' => 'Production & Assembly', 'code' => 'DEPT-PROD', 'head' => 'Vikram Deshmukh'],
            ['name' => 'Inventory & Logistics', 'code' => 'DEPT-INV', 'head' => 'Suresh Nair'],
            ['name' => 'Sales & Marketing', 'code' => 'DEPT-SAL', 'head' => 'Anita Kapoor'],
            ['name' => 'Purchase & Procurement', 'code' => 'DEPT-PUR', 'head' => 'Ramesh Iyer'],
            ['name' => 'Accounts & Finance', 'code' => 'DEPT-ACC', 'head' => 'Sunil Gupta'],
            ['name' => 'Human Resources', 'code' => 'DEPT-HR', 'head' => 'Pooja Verma'],
            ['name' => 'Quality Assurance', 'code' => 'DEPT-QA', 'head' => 'Dr. Milind Joshi'],
        ];

        foreach ($depts as $d) {
            Department::firstOrCreate(['company_id' => $company1->id, 'code' => $d['code']], [
                'branch_id' => $branchMumbai->id,
                'name' => $d['name'],
                'address' => 'Mumbai Facility',
                'postal_code' => '400093',
                'department_head' => $d['head'],
                'responsible_staff' => $d['head'],
                'status' => 'active',
            ]);
        }

        // 6. Create Godowns / Warehouses
        $godownCentral = Godown::firstOrCreate(['company_id' => $company1->id, 'code' => 'WH-MUM-01'], [
            'branch_id' => $branchMumbai->id,
            'name' => 'Central Factory Warehouse',
            'address' => 'Bldg A, Andheri Plant',
            'postal_code' => '400093',
            'responsible_staff' => 'Suresh Nair',
            'status' => 'active',
        ]);

        $godownRaw = Godown::firstOrCreate(['company_id' => $company1->id, 'code' => 'WH-MUM-RAW'], [
            'branch_id' => $branchMumbai->id,
            'name' => 'Raw Materials Godown',
            'address' => 'Bldg B, Andheri Plant',
            'postal_code' => '400093',
            'responsible_staff' => 'Mahesh Gaikwad',
            'status' => 'active',
        ]);

        $godownPune = Godown::firstOrCreate(['company_id' => $company1->id, 'code' => 'WH-PUN-01'], [
            'branch_id' => $branchPune->id,
            'name' => 'Pune Distribution Godown',
            'address' => 'Plot 88, MIDC Bhosari',
            'postal_code' => '411026',
            'responsible_staff' => 'Vikas Patil',
            'status' => 'active',
        ]);

        // 7. Territory, Route & Vehicles
        $cityMumbai = City::firstOrCreate(['company_id' => $company1->id, 'name' => 'Mumbai'], [
            'branch_id' => $branchMumbai->id,
            'status' => 'active',
        ]);
        $cityThane = City::firstOrCreate(['company_id' => $company1->id, 'name' => 'Thane'], [
            'branch_id' => $branchMumbai->id,
            'status' => 'active',
        ]);
        $cityPune = City::firstOrCreate(['company_id' => $company1->id, 'name' => 'Pune'], [
            'branch_id' => $branchPune->id,
            'status' => 'active',
        ]);

        $locAndheri = Location::firstOrCreate(['company_id' => $company1->id, 'city_id' => $cityMumbai->id, 'name' => 'Andheri West'], [
            'pincode' => '400058',
            'status' => 'active',
        ]);
        $locBandra = Location::firstOrCreate(['company_id' => $company1->id, 'city_id' => $cityMumbai->id, 'name' => 'Bandra Kurla Complex'], [
            'pincode' => '400051',
            'status' => 'active',
        ]);
        $locWagle = Location::firstOrCreate(['company_id' => $company1->id, 'city_id' => $cityThane->id, 'name' => 'Wagle Estate'], [
            'pincode' => '400604',
            'status' => 'active',
        ]);

        $vehicle1 = Vehicle::firstOrCreate(['company_id' => $company1->id, 'registration_number' => 'MH-02-CB-4040'], [
            'branch_id' => $branchMumbai->id,
            'vehicle_name' => 'Tata 407 Heavy Truck',
            'vehicle_type' => 'Truck',
            'capacity' => 4500.00,
            'assigned_driver' => 'Ramesh Shinde',
            'insurance_expiry_date' => '2027-05-15',
            'fitness_certificate_expiry_date' => '2027-04-10',
            'status' => 'active',
        ]);

        $vehicle2 = Vehicle::firstOrCreate(['company_id' => $company1->id, 'registration_number' => 'MH-02-EE-8822'], [
            'branch_id' => $branchMumbai->id,
            'vehicle_name' => 'Mahindra Bolero Maxi Truck',
            'vehicle_type' => 'Tempo',
            'capacity' => 1500.00,
            'assigned_driver' => 'Dilip Yadav',
            'insurance_expiry_date' => '2027-08-20',
            'fitness_certificate_expiry_date' => '2027-07-30',
            'status' => 'active',
        ]);

        $route1 = Route::firstOrCreate(['company_id' => $company1->id, 'route_name' => 'Mumbai Western Express Route'], [
            'branch_id' => $branchMumbai->id,
            'vehicle_id' => $vehicle1->id,
            'assigned_driver' => 'Ramesh Shinde',
            'status' => 'active',
        ]);
        $route1->cities()->syncWithoutDetaching([$cityMumbai->id, $cityThane->id]);
        $route1->locations()->syncWithoutDetaching([$locAndheri->id, $locBandra->id, $locWagle->id]);

        // 8. Users
        $superAdminRole = Role::where('slug', 'super-admin')->first();
        $companyAdminRole = Role::where('slug', 'company-admin')->first();
        $accountantRole = Role::where('slug', 'accounts-manager')->first();
        $inventoryRole = Role::where('slug', 'inventory-manager')->first();

        // Super Admin (can access everything across companies)
        $admin = User::firstOrCreate(['email' => 'admin@erp.com'], [
            'name' => 'Super Administrator',
            'password' => Hash::make('password123'),
            'company_id' => $company1->id,
            'branch_id' => $branchMumbai->id,
            'role_id' => $superAdminRole->id,
            'phone' => '+91 9820011223',
            'is_active' => true,
        ]);
        $admin->companies()->syncWithoutDetaching([
            $company1->id => ['branch_id' => $branchMumbai->id],
            $company2->id => ['branch_id' => null],
            $company3->id => ['branch_id' => null],
        ]);

        // Company Admin for Apex Industrial
        $compAdmin = User::firstOrCreate(['email' => 'admin@apexindustrial.com'], [
            'name' => 'Rajesh Sharma',
            'password' => Hash::make('password123'),
            'company_id' => $company1->id,
            'branch_id' => $branchMumbai->id,
            'role_id' => $companyAdminRole->id,
            'phone' => '+91 9820033445',
            'is_active' => true,
        ]);

        // Accountant
        User::firstOrCreate(['email' => 'accounts@apexindustrial.com'], [
            'name' => 'Sunil Gupta',
            'password' => Hash::make('password123'),
            'company_id' => $company1->id,
            'branch_id' => $branchMumbai->id,
            'role_id' => $accountantRole->id,
            'phone' => '+91 9820055667',
            'is_active' => true,
        ]);

        // Inventory Manager
        User::firstOrCreate(['email' => 'inventory@apexindustrial.com'], [
            'name' => 'Suresh Nair',
            'password' => Hash::make('password123'),
            'company_id' => $company1->id,
            'branch_id' => $branchMumbai->id,
            'role_id' => $inventoryRole->id,
            'phone' => '+91 9820077889',
            'is_active' => true,
        ]);
    }
}

