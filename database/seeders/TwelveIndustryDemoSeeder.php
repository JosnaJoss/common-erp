<?php

namespace Database\Seeders;

use App\Models\AccountGroup;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Company;
use App\Models\CompanyModule;
use App\Models\Customer;
use App\Models\Department;
use App\Models\FinancialYear;
use App\Models\Godown;
use App\Models\Holiday;
use App\Models\Item;
use App\Models\Ledger;
use App\Models\Product;
use App\Models\Shift;
use App\Models\Staff;
use App\Models\Subcategory;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TwelveIndustryDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Seeds 12 distinct industry demo companies with complete master data ONLY.
     * Operational transactions remain completely empty to support manual testing.
     */
    public function run(): void
    {
        $this->command->info('================================================================');
        $this->command->info('Seeding 12 Fresh Industry Demo Companies (Master Data Only)...');
        $this->command->info('================================================================');

        $industries = $this->getIndustryDefinitions();

        foreach ($industries as $index => $industry) {
            $num = $index + 1;
            $this->command->info("[$num/12] Seeding: {$industry['company']['name']} ({$industry['company']['code']})...");
            $this->seedIndustry($industry);
        }

        $this->command->info('================================================================');
        $this->command->info('12 Industry Master Data Seeding completed successfully!');
        $this->command->info('Operational transactions are 100% empty, ready for manual testing.');
        $this->command->info('================================================================');
    }

    /**
     * Seed an individual industry company with complete master data
     */
    protected function seedIndustry(array $data): void
    {
        DB::transaction(function () use ($data) {
            // 1. Company
            $company = Company::firstOrCreate(
                ['code' => $data['company']['code']],
                [
                    'name' => $data['company']['name'],
                    'registration_no' => $data['company']['registration_no'] ?? null,
                    'gst_no' => $data['company']['gst_no'] ?? null,
                    'pan_no' => $data['company']['pan_no'] ?? null,
                    'address' => $data['company']['address'],
                    'phone' => $data['company']['phone'],
                    'email' => $data['company']['email'],
                    'website' => $data['company']['website'] ?? null,
                    'currency' => 'INR',
                    'currency_symbol' => '₹',
                    'financial_year_start' => '2026-04-01',
                    'financial_year_end' => '2027-03-31',
                    'business_type' => $data['company']['business_type'],
                    'status' => 'active',
                ]
            );

            // 2. Financial Year
            FinancialYear::firstOrCreate(
                ['company_id' => $company->id, 'title' => 'FY 2026-2027'],
                ['start_date' => '2026-04-01', 'end_date' => '2027-03-31', 'is_closed' => false]
            );

            // 3. Modules
            $allModules = ['staff_hr', 'inventory', 'purchase', 'sales', 'accounts', 'production', 'vehicle_route', 'quality', 'crm'];
            $enabledModules = $data['modules'] ?? $allModules;
            foreach ($allModules as $mod) {
                CompanyModule::firstOrCreate(
                    ['company_id' => $company->id, 'module_key' => $mod],
                    ['is_enabled' => in_array($mod, $enabledModules)]
                );
            }

            // 4. Chart of Accounts (Groups & Core Ledgers)
            $groups = $this->createAccountGroups($company);
            $this->createStandardLedgers($company, $groups, $data['bank']);

            // 5. Branches
            $branchMap = [];
            $mainBranch = null;
            foreach ($data['branches'] as $idx => $b) {
                $branch = Branch::firstOrCreate(
                    ['company_id' => $company->id, 'code' => $b['code']],
                    [
                        'name' => $b['name'],
                        'address' => $b['address'],
                        'city' => $b['city'],
                        'state' => $b['state'],
                        'postal_code' => $b['postal_code'],
                        'contact_number' => $b['contact_number'],
                        'email' => $b['email'],
                        'is_main_branch' => $b['is_main_branch'] ?? ($idx === 0),
                        'status' => 'active',
                    ]
                );
                $branchMap[$b['code']] = $branch;
                if ($branch->is_main_branch || $idx === 0) {
                    $mainBranch = $mainBranch ?? $branch;
                }
            }

            // 6. Departments
            $deptMap = [];
            foreach ($data['departments'] as $d) {
                $targetBranch = isset($d['branch_code']) && isset($branchMap[$d['branch_code']])
                    ? $branchMap[$d['branch_code']]
                    : $mainBranch;

                $dept = Department::firstOrCreate(
                    ['company_id' => $company->id, 'code' => $d['code']],
                    [
                        'branch_id' => $targetBranch->id,
                        'name' => $d['name'],
                        'department_type' => $d['type'] ?? 'operations',
                        'status' => 'active',
                    ]
                );
                $deptMap[$d['code']] = $dept;
            }

            // 7. Godowns
            foreach ($data['godowns'] as $g) {
                $targetBranch = isset($g['branch_code']) && isset($branchMap[$g['branch_code']])
                    ? $branchMap[$g['branch_code']]
                    : $mainBranch;

                Godown::firstOrCreate(
                    ['company_id' => $company->id, 'code' => $g['code']],
                    [
                        'branch_id' => $targetBranch->id,
                        'name' => $g['name'],
                        'location_type' => $g['location_type'] ?? 'warehouse',
                        'status' => 'active',
                    ]
                );
            }

            // 8. Units of Measurement
            $unitMap = [];
            foreach ($data['units'] as $u) {
                $unit = Unit::firstOrCreate(
                    ['company_id' => $company->id, 'symbol' => $u['symbol']],
                    [
                        'name' => $u['name'],
                        'unit_type' => $u['type'],
                        'conversion_factor' => 1.0,
                        'status' => 'active',
                    ]
                );
                $unitMap[$u['symbol']] = $unit;
            }

            // 9. Categories & Subcategories
            $catMap = [];
            $subMap = [];
            foreach ($data['categories'] as $c) {
                $cat = Category::firstOrCreate(
                    ['company_id' => $company->id, 'name' => $c['name']],
                    ['description' => $c['description'] ?? null, 'status' => 'active']
                );
                $catMap[$c['name']] = $cat;

                if (!empty($c['subcategories'])) {
                    foreach ($c['subcategories'] as $subName) {
                        $sub = Subcategory::firstOrCreate(
                            ['company_id' => $company->id, 'category_id' => $cat->id, 'name' => $subName],
                            ['description' => null, 'status' => 'active']
                        );
                        $subMap[$c['name'] . '::' . $subName] = $sub;
                    }
                }
            }

            // 10. Items (Raw materials / consumables)
            if (!empty($data['raw_materials'])) {
                foreach ($data['raw_materials'] as $rm) {
                    $cat = $catMap[$rm['category']] ?? null;
                    $sub = isset($rm['subcategory']) ? ($subMap[$rm['category'] . '::' . $rm['subcategory']] ?? null) : null;
                    $unit = $unitMap[$rm['unit']] ?? null;

                    Item::firstOrCreate(
                        ['company_id' => $company->id, 'code' => $rm['code']],
                        [
                            'category_id' => $cat?->id,
                            'subcategory_id' => $sub?->id,
                            'unit_id' => $unit?->id,
                            'name' => $rm['name'],
                            'item_type' => 'raw_material',
                            'min_price' => $rm['price'] * 0.9,
                            'normal_price' => $rm['price'],
                            'max_price' => $rm['price'] * 1.15,
                            'gst_percentage' => $rm['gst'] ?? 18,
                            'hsn_code' => $rm['hsn'] ?? '0000',
                            'reorder_quantity' => $rm['reorder'] ?? 50,
                            'status' => 'active',
                        ]
                    );
                }
            }

            // 11. Finished Products & Sales Items
            if (!empty($data['products'])) {
                foreach ($data['products'] as $p) {
                    $cat = $catMap[$p['category']] ?? null;
                    $sub = isset($p['subcategory']) ? ($subMap[$p['category'] . '::' . $p['subcategory']] ?? null) : null;
                    $unit = $unitMap[$p['unit']] ?? null;

                    $prod = Product::firstOrCreate(
                        ['company_id' => $company->id, 'code' => $p['code']],
                        [
                            'category_id' => $cat?->id,
                            'subcategory_id' => $sub?->id,
                            'name' => $p['name'],
                            'description' => $p['description'] ?? "Premium quality {$p['name']}",
                            'status' => 'active',
                        ]
                    );

                    Item::firstOrCreate(
                        ['company_id' => $company->id, 'code' => $p['code']],
                        [
                            'category_id' => $cat?->id,
                            'subcategory_id' => $sub?->id,
                            'product_id' => $prod->id,
                            'unit_id' => $unit?->id,
                            'name' => $p['name'],
                            'item_type' => 'sales_item',
                            'min_price' => $p['price'] * 0.9,
                            'normal_price' => $p['price'],
                            'max_price' => $p['price'] * 1.15,
                            'gst_percentage' => $p['gst'] ?? 18,
                            'hsn_code' => $p['hsn'] ?? '0000',
                            'reorder_quantity' => $p['reorder'] ?? 25,
                            'status' => 'active',
                        ]
                    );
                }
            }

            // 12. Suppliers (with Creditor Ledgers)
            if (!empty($data['suppliers'])) {
                foreach ($data['suppliers'] as $s) {
                    $sLedger = Ledger::firstOrCreate(
                        ['company_id' => $company->id, 'name' => $s['name']],
                        [
                            'account_group_id' => $groups['SC-01']->id,
                            'opening_balance' => 0,
                            'balance_type' => 'credit',
                            'current_balance' => 0,
                            'is_bank_or_cash' => false,
                            'status' => 'active',
                        ]
                    );

                    Supplier::firstOrCreate(
                        ['company_id' => $company->id, 'code' => $s['code']],
                        [
                            'name' => $s['name'],
                            'contact_person' => $s['contact_person'] ?? null,
                            'phone' => $s['phone'] ?? null,
                            'email' => $s['email'] ?? null,
                            'city' => $s['city'] ?? null,
                            'gst_number' => $s['gst'] ?? null,
                            'account_group_id' => $groups['SC-01']->id,
                            'ledger_id' => $sLedger->id,
                            'opening_balance' => 0,
                            'opening_balance_date' => '2026-04-01',
                            'account_type' => 'credit',
                            'current_balance' => 0,
                            'status' => 'active',
                        ]
                    );
                }
            }

            // 13. Customers (with Debtor Ledgers)
            if (!empty($data['customers'])) {
                foreach ($data['customers'] as $c) {
                    $cLedger = Ledger::firstOrCreate(
                        ['company_id' => $company->id, 'name' => $c['name']],
                        [
                            'account_group_id' => $groups['SD-01']->id,
                            'opening_balance' => 0,
                            'balance_type' => 'debit',
                            'current_balance' => 0,
                            'is_bank_or_cash' => false,
                            'status' => 'active',
                        ]
                    );

                    Customer::firstOrCreate(
                        ['company_id' => $company->id, 'code' => $c['code']],
                        [
                            'name' => $c['name'],
                            'shop_name' => $c['shop_name'] ?? null,
                            'phone' => $c['phone'] ?? null,
                            'email' => $c['email'] ?? null,
                            'city' => $c['city'] ?? null,
                            'gst_number' => $c['gst'] ?? null,
                            'account_group_id' => $groups['SD-01']->id,
                            'ledger_id' => $cLedger->id,
                            'price_level' => 'normal',
                            'opening_balance' => 0,
                            'opening_balance_date' => '2026-04-01',
                            'account_type' => 'debit',
                            'current_balance' => 0,
                            'status' => 'active',
                        ]
                    );
                }
            }

            // 14. Staff (Diverse employment types: salaried, hourly, contract, piece_rate, daily_wage)
            if (!empty($data['staff'])) {
                foreach ($data['staff'] as $st) {
                    $targetDept = isset($st['dept_code']) && isset($deptMap[$st['dept_code']])
                        ? $deptMap[$st['dept_code']]
                        : ($deptMap ? reset($deptMap) : null);

                    $targetBranch = isset($st['branch_code']) && isset($branchMap[$st['branch_code']])
                        ? $branchMap[$st['branch_code']]
                        : $mainBranch;

                    Staff::firstOrCreate(
                        ['company_id' => $company->id, 'employee_id' => $st['employee_id']],
                        [
                            'branch_id' => $targetBranch->id,
                            'department_id' => $targetDept?->id,
                            'first_name' => $st['first_name'],
                            'last_name' => $st['last_name'],
                            'email' => strtolower($st['first_name'] . '.' . $st['last_name'] . '@' . parse_url($data['company']['email'], PHP_URL_PATH) ?? 'demo.com'),
                            'phone' => '+91 9' . rand(100000000, 999999999),
                            'designation' => $st['designation'],
                            'employee_type' => $st['employee_type'],
                            'salary_amount' => $st['salary_amount'] ?? 0,
                            'basic_salary' => $st['basic_salary'] ?? ($st['employee_type'] === 'salaried' ? ($st['salary_amount'] ?? 0) : 0),
                            'hourly_rate' => $st['hourly_rate'] ?? 0,
                            'piece_rate' => $st['piece_rate'] ?? 0,
                            'date_of_joining' => '2026-04-01',
                            'pf_applicable' => $st['pf_applicable'] ?? false,
                            'esi_applicable' => $st['esi_applicable'] ?? false,
                            'status' => 'active',
                        ]
                    );
                }
            }

            // 15. Shifts & Holidays
            Shift::firstOrCreate(
                ['company_id' => $company->id, 'shift_name' => 'General Day Shift'],
                [
                    'branch_id' => $mainBranch->id,
                    'start_time' => '09:00:00',
                    'end_time' => '18:00:00',
                    'break_duration_minutes' => 60,
                    'status' => 'active',
                ]
            );
            Holiday::firstOrCreate(
                ['company_id' => $company->id, 'holiday_name' => 'Independence Day'],
                [
                    'branch_id' => $mainBranch->id,
                    'holiday_date' => '2026-08-15',
                    'holiday_type' => 'National',
                    'description' => 'National Holiday',
                ]
            );

            // 16. Demo Users (Admin, Accounts, Warehouse, Sales)
            $standardPassword = Hash::make('password');
            $userConfigs = [
                ['name' => "{$data['short_name']} Admin", 'email' => "admin@{$data['domain']}", 'role_id' => 2],
                ['name' => "{$data['short_name']} Accounts", 'email' => "accounts@{$data['domain']}", 'role_id' => 3],
                ['name' => "{$data['short_name']} Warehouse", 'email' => "warehouse@{$data['domain']}", 'role_id' => 4],
                ['name' => "{$data['short_name']} Sales", 'email' => "sales@{$data['domain']}", 'role_id' => 5],
            ];
            foreach ($userConfigs as $uc) {
                $user = User::firstOrCreate(
                    ['email' => $uc['email']],
                    [
                        'company_id' => $company->id,
                        'branch_id' => $mainBranch->id,
                        'role_id' => $uc['role_id'],
                        'name' => $uc['name'],
                        'password' => $standardPassword,
                        'is_active' => true,
                    ]
                );

                DB::table('user_company_branches')->updateOrInsert(
                    ['user_id' => $user->id, 'company_id' => $company->id],
                    ['branch_id' => $mainBranch->id]
                );
            }
        });
    }

    /**
     * Create Standard Account Groups
     */
    protected function createAccountGroups(Company $company): array
    {
        $ca = AccountGroup::firstOrCreate(['company_id' => $company->id, 'code' => 'CA-01'], ['name' => 'Current Assets', 'nature' => 'Assets']);
        $fa = AccountGroup::firstOrCreate(['company_id' => $company->id, 'code' => 'FA-01'], ['name' => 'Fixed Assets', 'nature' => 'Assets']);
        $bk = AccountGroup::firstOrCreate(['company_id' => $company->id, 'code' => 'BK-01'], ['name' => 'Bank & Cash Accounts', 'nature' => 'Assets', 'parent_id' => $ca->id]);
        $sd = AccountGroup::firstOrCreate(['company_id' => $company->id, 'code' => 'SD-01'], ['name' => 'Sundry Debtors (Customers)', 'nature' => 'Assets', 'parent_id' => $ca->id]);
        $cl = AccountGroup::firstOrCreate(['company_id' => $company->id, 'code' => 'CL-01'], ['name' => 'Current Liabilities', 'nature' => 'Liabilities']);
        $sc = AccountGroup::firstOrCreate(['company_id' => $company->id, 'code' => 'SC-01'], ['name' => 'Sundry Creditors (Suppliers)', 'nature' => 'Liabilities', 'parent_id' => $cl->id]);
        $dt = AccountGroup::firstOrCreate(['company_id' => $company->id, 'code' => 'DT-01'], ['name' => 'Duties & Taxes', 'nature' => 'Liabilities', 'parent_id' => $cl->id]);
        $eq = AccountGroup::firstOrCreate(['company_id' => $company->id, 'code' => 'EQ-01'], ['name' => 'Capital & Reserves', 'nature' => 'Equity']);
        $rev = AccountGroup::firstOrCreate(['company_id' => $company->id, 'code' => 'REV-01'], ['name' => 'Sales & Operating Revenue', 'nature' => 'Revenue']);
        $exp1 = AccountGroup::firstOrCreate(['company_id' => $company->id, 'code' => 'EXP-01'], ['name' => 'Direct Expenses & Purchases', 'nature' => 'Expenses']);
        $exp2 = AccountGroup::firstOrCreate(['company_id' => $company->id, 'code' => 'EXP-02'], ['name' => 'Administrative & Indirect Expenses', 'nature' => 'Expenses']);

        return [
            'CA-01' => $ca,
            'FA-01' => $fa,
            'BK-01' => $bk,
            'SD-01' => $sd,
            'CL-01' => $cl,
            'SC-01' => $sc,
            'DT-01' => $dt,
            'EQ-01' => $eq,
            'REV-01' => $rev,
            'EXP-01' => $exp1,
            'EXP-02' => $exp2,
        ];
    }

    /**
     * Create Standard Core Ledgers
     */
    protected function createStandardLedgers(Company $company, array $groups, array $bank): void
    {
        $ledgers = [
            ['name' => 'Cash in Hand', 'group' => $groups['BK-01']->id, 'type' => 'debit', 'is_bank_cash' => true],
            ['name' => $bank['name'], 'group' => $groups['BK-01']->id, 'type' => 'debit', 'is_bank_cash' => true, 'bank_name' => explode(' ', $bank['name'])[0], 'acc_no' => $bank['acc_no'], 'ifsc' => $bank['ifsc']],
            ['name' => 'Sales Account', 'group' => $groups['REV-01']->id, 'type' => 'credit', 'is_bank_cash' => false],
            ['name' => 'Sales Return Account', 'group' => $groups['REV-01']->id, 'type' => 'debit', 'is_bank_cash' => false],
            ['name' => 'Purchase Account', 'group' => $groups['EXP-01']->id, 'type' => 'debit', 'is_bank_cash' => false],
            ['name' => 'Purchase Return Account', 'group' => $groups['EXP-01']->id, 'type' => 'credit', 'is_bank_cash' => false],
            ['name' => 'Salaries & Staff Wages', 'group' => $groups['EXP-02']->id, 'type' => 'debit', 'is_bank_cash' => false],
            ['name' => 'Facility Rent & Lease', 'group' => $groups['EXP-02']->id, 'type' => 'debit', 'is_bank_cash' => false],
            ['name' => 'Electricity & Utilities', 'group' => $groups['EXP-02']->id, 'type' => 'debit', 'is_bank_cash' => false],
            ['name' => 'Freight & Logistics Outward', 'group' => $groups['EXP-02']->id, 'type' => 'debit', 'is_bank_cash' => false],
            ['name' => 'GST Input Tax Credit', 'group' => $groups['CA-01']->id, 'type' => 'debit', 'is_bank_cash' => false],
            ['name' => 'GST Output Tax Liability', 'group' => $groups['DT-01']->id, 'type' => 'credit', 'is_bank_cash' => false],
            ['name' => 'PF & ESI Payable', 'group' => $groups['DT-01']->id, 'type' => 'credit', 'is_bank_cash' => false],
            ['name' => 'Promoters Share Capital', 'group' => $groups['EQ-01']->id, 'type' => 'credit', 'is_bank_cash' => false],
        ];

        foreach ($ledgers as $l) {
            Ledger::firstOrCreate(
                ['company_id' => $company->id, 'name' => $l['name']],
                [
                    'account_group_id' => $l['group'],
                    'opening_balance' => 0,
                    'balance_type' => $l['type'],
                    'current_balance' => 0,
                    'is_bank_or_cash' => $l['is_bank_cash'],
                    'bank_name' => $l['bank_name'] ?? null,
                    'bank_account_no' => $l['acc_no'] ?? null,
                    'bank_ifsc' => $l['ifsc'] ?? null,
                    'status' => 'active',
                ]
            );
        }
    }

    /**
     * Exhaustive industry definitions for all 12 industries
     */
    protected function getIndustryDefinitions(): array
    {
        return [
            // 1. Food Manufacturing
            [
                'short_name' => 'Malabar',
                'domain' => 'malabarfoods.com',
                'company' => [
                    'name' => 'Malabar Taste Foods Pvt. Ltd.',
                    'code' => 'MALABAR-FOODS',
                    'registration_no' => 'U15400KL2021PTC068912',
                    'gst_no' => '32AABCM1234F1Z8',
                    'pan_no' => 'AABCM1234F',
                    'address' => 'Malabar Food Park, Kakkanad, Kochi, Kerala 682030',
                    'phone' => '+91 484 298 4400',
                    'email' => 'info@malabarfoods.com',
                    'website' => 'https://malabarfoods.example.com',
                    'business_type' => 'manufacturing',
                ],
                'bank' => ['name' => 'Federal Bank Current Account', 'acc_no' => '1029384756', 'ifsc' => 'FDRL0001029'],
                'modules' => ['staff_hr', 'inventory', 'purchase', 'sales', 'accounts', 'production', 'vehicle_route', 'quality', 'crm'],
                'branches' => [
                    ['name' => 'Kochi Central Processing & HQ', 'code' => 'BR-KOC-01', 'address' => 'Kakkanad Industrial Zone, Kochi', 'city' => 'Kochi', 'state' => 'Kerala', 'postal_code' => '682030', 'contact_number' => '+91 484 298 4401', 'email' => 'kochi@malabarfoods.com', 'is_main_branch' => true],
                    ['name' => 'Kozhikode Spices & Milling Plant', 'code' => 'BR-CCJ-02', 'address' => 'Mavoor Road Industrial Area, Kozhikode', 'city' => 'Kozhikode', 'state' => 'Kerala', 'postal_code' => '673004', 'contact_number' => '+91 495 272 8800', 'email' => 'calicut@malabarfoods.com'],
                    ['name' => 'Thrissur Regional Distribution Centre', 'code' => 'BR-TCR-03', 'address' => 'Olarikkara Commercial Hub, Thrissur', 'city' => 'Thrissur', 'state' => 'Kerala', 'postal_code' => '680012', 'contact_number' => '+91 487 236 1120', 'email' => 'thrissur@malabarfoods.com'],
                ],
                'departments' => [
                    ['name' => 'Spice Milling & Processing', 'code' => 'DEP-PROD', 'type' => 'production'],
                    ['name' => 'Quality Control & Food Testing', 'code' => 'DEP-QA', 'type' => 'quality'],
                    ['name' => 'Raw Materials & Packing Store', 'code' => 'DEP-WH', 'type' => 'inventory'],
                    ['name' => 'Channel Sales & Distribution', 'code' => 'DEP-SALES', 'type' => 'sales'],
                    ['name' => 'Finance & Costing', 'code' => 'DEP-FIN', 'type' => 'accounts'],
                ],
                'godowns' => [
                    ['name' => 'Raw Spices & Grain Godown', 'code' => 'GDW-KOC-RAW', 'location_type' => 'warehouse', 'branch_code' => 'BR-KOC-01'],
                    ['name' => 'Finished Condiments Packhouse', 'code' => 'GDW-KOC-FG', 'location_type' => 'store', 'branch_code' => 'BR-KOC-01'],
                    ['name' => 'Calicut Regional Hub Godown', 'code' => 'GDW-CCJ-HUB', 'location_type' => 'warehouse', 'branch_code' => 'BR-CCJ-02'],
                ],
                'units' => [
                    ['name' => 'Kilogram', 'symbol' => 'KG', 'type' => 'weight'],
                    ['name' => 'Gram', 'symbol' => 'GM', 'type' => 'weight'],
                    ['name' => 'Metric Ton', 'symbol' => 'TON', 'type' => 'weight'],
                    ['name' => 'Corrugated Box', 'symbol' => 'BOX', 'type' => 'quantity'],
                    ['name' => 'Packet', 'symbol' => 'PKT', 'type' => 'quantity'],
                ],
                'categories' => [
                    ['name' => 'Spices & Powders', 'description' => 'Whole spices and pure powders', 'subcategories' => ['Whole Spices', 'Ground Powders']],
                    ['name' => 'Pickles & Preserves', 'description' => 'Traditional ethnic condiments', 'subcategories' => ['Traditional Pickles']],
                    ['name' => 'Packaging Supplies', 'description' => 'Food grade pouches and shippers', 'subcategories' => ['Printed Pouches']],
                ],
                'raw_materials' => [
                    ['name' => 'Whole Black Pepper Grade 1', 'code' => 'RM-PEP-01', 'category' => 'Spices & Powders', 'subcategory' => 'Whole Spices', 'unit' => 'KG', 'price' => 520, 'gst' => 5, 'hsn' => '0904'],
                    ['name' => 'Dry Red Chilli Whole (Guntur)', 'code' => 'RM-CHI-01', 'category' => 'Spices & Powders', 'subcategory' => 'Whole Spices', 'unit' => 'KG', 'price' => 230, 'gst' => 5, 'hsn' => '0904'],
                    ['name' => 'Turmeric Whole Fingers (Salem)', 'code' => 'RM-TUR-01', 'category' => 'Spices & Powders', 'subcategory' => 'Whole Spices', 'unit' => 'KG', 'price' => 155, 'gst' => 5, 'hsn' => '0910'],
                    ['name' => 'Printed Spices Pouch 250g', 'code' => 'PM-PCH-250', 'category' => 'Packaging Supplies', 'subcategory' => 'Printed Pouches', 'unit' => 'PKT', 'price' => 2.5, 'gst' => 18, 'hsn' => '3923'],
                ],
                'products' => [
                    ['name' => 'Malabar Special Garam Masala 250g', 'code' => 'FG-GM-250', 'category' => 'Spices & Powders', 'subcategory' => 'Ground Powders', 'unit' => 'PKT', 'price' => 110, 'gst' => 5, 'hsn' => '0910'],
                    ['name' => 'Pure Turmeric Powder 500g', 'code' => 'FG-TUR-500', 'category' => 'Spices & Powders', 'subcategory' => 'Ground Powders', 'unit' => 'PKT', 'price' => 135, 'gst' => 5, 'hsn' => '0910'],
                    ['name' => 'Tender Mango Pickle Jar 400g', 'code' => 'FG-PCK-400', 'category' => 'Pickles & Preserves', 'subcategory' => 'Traditional Pickles', 'unit' => 'PKT', 'price' => 95, 'gst' => 12, 'hsn' => '2001'],
                ],
                'suppliers' => [
                    ['name' => 'Wayanad Pepper Planters Co-op', 'code' => 'SUP-WYD-01', 'contact_person' => 'Varghese Mathew', 'phone' => '+91 94471 23456', 'email' => 'sales@wayanadpepper.org', 'city' => 'Kalpetta', 'gst' => '32AAAFW1122D1Z4'],
                    ['name' => 'Guntur Spices Trading Company', 'code' => 'SUP-GNT-02', 'contact_person' => 'K. R. Rao', 'phone' => '+91 86322 34567', 'email' => 'orders@gunturspices.in', 'city' => 'Guntur', 'gst' => '37AAACG5566G1Z2'],
                    ['name' => 'Apex Flexible Packaging Systems', 'code' => 'SUP-APX-03', 'contact_person' => 'Dinesh Sharma', 'phone' => '+91 98201 98765', 'email' => 'dinesh@apexpackaging.in', 'city' => 'Mumbai', 'gst' => '27AAACA9988H1Z1'],
                ],
                'customers' => [
                    ['name' => 'Nilgiri Supermarkets Chain', 'code' => 'CUS-NIL-01', 'shop_name' => 'Nilgiri Superstores Kochi', 'phone' => '+91 98460 11223', 'email' => 'purchase@nilgiris.com', 'city' => 'Kochi', 'gst' => '32AAACN4455E1Z9'],
                    ['name' => 'Cochin Hypermarkets Ltd.', 'code' => 'CUS-CHM-02', 'shop_name' => 'Cochin Hypermarket MG Road', 'phone' => '+91 98470 22334', 'email' => 'cochin@hypermarkets.in', 'city' => 'Kochi', 'gst' => '32AAACC7788P1Z6'],
                    ['name' => 'Malabar Family Restaurant Chain', 'code' => 'CUS-MFR-03', 'shop_name' => 'Malabar Restaurant Kozhikode', 'phone' => '+91 94470 33445', 'email' => 'chef@malabarrest.com', 'city' => 'Kozhikode', 'gst' => '32AAACM3344M1Z5'],
                    ['name' => 'Calicut Daily Fresh Mart', 'code' => 'CUS-CDF-04', 'shop_name' => 'Daily Fresh Palayam', 'phone' => '+91 98465 44556', 'email' => 'dailyfresh@calicut.in', 'city' => 'Kozhikode', 'gst' => '32AAACD6677K1Z3'],
                ],
                'staff' => [
                    ['first_name' => 'Ramesh', 'last_name' => 'Pillai', 'employee_id' => 'STF-MAL-01', 'designation' => 'Plant Operations Head', 'employee_type' => 'salaried', 'salary_amount' => 55000, 'basic_salary' => 45000, 'dept_code' => 'DEP-PROD', 'pf_applicable' => true],
                    ['first_name' => 'Anil', 'last_name' => 'Kumar', 'employee_id' => 'STF-MAL-02', 'designation' => 'Milling Machine Operator', 'employee_type' => 'hourly', 'hourly_rate' => 175, 'dept_code' => 'DEP-PROD', 'esi_applicable' => true],
                    ['first_name' => 'Sujatha', 'last_name' => 'Menon', 'employee_id' => 'STF-MAL-03', 'designation' => 'Quality Lab Chemist', 'employee_type' => 'contract', 'salary_amount' => 32000, 'basic_salary' => 32000, 'dept_code' => 'DEP-QA'],
                    ['first_name' => 'Babu', 'last_name' => 'Raj', 'employee_id' => 'STF-MAL-04', 'designation' => 'Spice Packing Worker', 'employee_type' => 'piece_rate', 'piece_rate' => 12.5, 'dept_code' => 'DEP-PROD'],
                    ['first_name' => 'Mani', 'last_name' => 'Kandan', 'employee_id' => 'STF-MAL-05', 'designation' => 'Loading & Warehouse Helper', 'employee_type' => 'daily_wage', 'salary_amount' => 850, 'dept_code' => 'DEP-WH', 'esi_applicable' => true],
                ],
            ],

            // 2. Water Purification & Beverages
            [
                'short_name' => 'AquaFresh',
                'domain' => 'aquafreshbev.com',
                'company' => [
                    'name' => 'AquaFresh Beverages Pvt. Ltd.',
                    'code' => 'AQUAFRESH-BEV',
                    'registration_no' => 'U15549UP2020PTC134567',
                    'gst_no' => '09AAACA8899B1Z4',
                    'pan_no' => 'AAACA8899B',
                    'address' => 'Plot 18, Ecotech III, Greater Noida, Uttar Pradesh 201306',
                    'phone' => '+91 120 239 8811',
                    'email' => 'contact@aquafreshbev.com',
                    'website' => 'https://aquafreshbev.example.com',
                    'business_type' => 'manufacturing',
                ],
                'bank' => ['name' => 'HDFC Bank Bottling Division', 'acc_no' => '50200049281726', 'ifsc' => 'HDFC0000281'],
                'modules' => ['staff_hr', 'inventory', 'purchase', 'sales', 'accounts', 'production', 'vehicle_route', 'quality', 'crm'],
                'branches' => [
                    ['name' => 'Greater Noida Bottling & RO Plant', 'code' => 'BR-GNO-01', 'address' => 'Ecotech III Industrial Area, Greater Noida', 'city' => 'Greater Noida', 'state' => 'Uttar Pradesh', 'postal_code' => '201306', 'contact_number' => '+91 120 239 8812', 'email' => 'plant@aquafreshbev.com', 'is_main_branch' => true],
                    ['name' => 'Okhla Industrial Distribution Depot', 'code' => 'BR-OKH-02', 'address' => 'Okhla Phase II, New Delhi', 'city' => 'New Delhi', 'state' => 'Delhi', 'postal_code' => '110020', 'contact_number' => '+91 11 4161 7700', 'email' => 'okhla@aquafreshbev.com'],
                    ['name' => 'Gurgaon Corporate Hub', 'code' => 'BR-GGN-03', 'address' => 'Cyber City Phase 2, Gurgaon', 'city' => 'Gurgaon', 'state' => 'Haryana', 'postal_code' => '122002', 'contact_number' => '+91 124 458 9900', 'email' => 'gurgaon@aquafreshbev.com'],
                ],
                'departments' => [
                    ['name' => 'Water Treatment & Reverse Osmosis', 'code' => 'DEP-RO', 'type' => 'production'],
                    ['name' => 'Blow Molding & Bottling', 'code' => 'DEP-BTL', 'type' => 'production'],
                    ['name' => 'Quality Control & Lab Testing', 'code' => 'DEP-QC', 'type' => 'quality'],
                    ['name' => 'Fleet & Logistics Dispatch', 'code' => 'DEP-LOG', 'type' => 'inventory'],
                    ['name' => 'Commercial Accounts & Billing', 'code' => 'DEP-ACC', 'type' => 'accounts'],
                ],
                'godowns' => [
                    ['name' => 'Preform & Packaging Caps Store', 'code' => 'GDW-GNO-RAW', 'location_type' => 'warehouse', 'branch_code' => 'BR-GNO-01'],
                    ['name' => 'Bottled Water Ready Godown', 'code' => 'GDW-GNO-FG', 'location_type' => 'store', 'branch_code' => 'BR-GNO-01'],
                    ['name' => 'Okhla Transit & Fleet Depot', 'code' => 'GDW-OKH-DEPOT', 'location_type' => 'warehouse', 'branch_code' => 'BR-OKH-02'],
                ],
                'units' => [
                    ['name' => 'Litre', 'symbol' => 'LTR', 'type' => 'volume'],
                    ['name' => 'Millilitre', 'symbol' => 'ML', 'type' => 'volume'],
                    ['name' => 'Case of 12', 'symbol' => 'CASE', 'type' => 'quantity'],
                    ['name' => 'Single Bottle', 'symbol' => 'BTL', 'type' => 'quantity'],
                    ['name' => 'Piece', 'symbol' => 'PCS', 'type' => 'quantity'],
                ],
                'categories' => [
                    ['name' => 'Packaged Drinking Water', 'description' => 'Purified and mineralized water', 'subcategories' => ['1 Litre Bottled Water', '500ml Bottled Water']],
                    ['name' => 'Carbonated Sparkling', 'description' => 'Club soda and flavoured drinks', 'subcategories' => ['Sparkling Water']],
                    ['name' => 'Packaging Preforms & Caps', 'description' => 'Molding raw materials', 'subcategories' => ['PET Preforms', 'HDPE Caps']],
                ],
                'raw_materials' => [
                    ['name' => 'PET Resin Preform 20g (Natural)', 'code' => 'RM-PET-20G', 'category' => 'Packaging Preforms & Caps', 'subcategory' => 'PET Preforms', 'unit' => 'PCS', 'price' => 1.45, 'gst' => 18, 'hsn' => '3923'],
                    ['name' => 'HDPE Tamper-Evident Cap 28mm', 'code' => 'RM-CAP-28', 'category' => 'Packaging Preforms & Caps', 'subcategory' => 'HDPE Caps', 'unit' => 'PCS', 'price' => 0.35, 'gst' => 18, 'hsn' => '3923'],
                    ['name' => 'BOPP Wrap-Around Label 1L', 'code' => 'RM-LBL-1L', 'category' => 'Packaging Preforms & Caps', 'subcategory' => 'PET Preforms', 'unit' => 'PCS', 'price' => 0.40, 'gst' => 12, 'hsn' => '3919'],
                ],
                'products' => [
                    ['name' => 'AquaFresh Pure Mineral Water 1L Case (12 Pcs)', 'code' => 'FG-AQ-1L-CS', 'category' => 'Packaged Drinking Water', 'subcategory' => '1 Litre Bottled Water', 'unit' => 'CASE', 'price' => 180, 'gst' => 18, 'hsn' => '2201'],
                    ['name' => 'AquaFresh Natural Spring 500ml Case (24 Pcs)', 'code' => 'FG-AQ-500-CS', 'category' => 'Packaged Drinking Water', 'subcategory' => '500ml Bottled Water', 'unit' => 'CASE', 'price' => 210, 'gst' => 18, 'hsn' => '2201'],
                    ['name' => 'AquaFresh Club Soda 750ml Case (12 Pcs)', 'code' => 'FG-SODA-750', 'category' => 'Carbonated Sparkling', 'subcategory' => 'Sparkling Water', 'unit' => 'CASE', 'price' => 240, 'gst' => 18, 'hsn' => '2202'],
                ],
                'suppliers' => [
                    ['name' => 'Reliance Polymers Preforms Division', 'code' => 'SUP-REL-01', 'contact_person' => 'Sunil Gupta', 'phone' => '+91 265 223 4567', 'email' => 'preforms@ril.com', 'city' => 'Vadodara', 'gst' => '24AAACR1234F1Z9'],
                    ['name' => 'Avery Dennison Labels India', 'code' => 'SUP-AVY-02', 'contact_person' => 'Neha Kapoor', 'phone' => '+91 124 489 7700', 'email' => 'labels@averydennison.com', 'city' => 'Gurugram', 'gst' => '06AAACA5566K1Z3'],
                    ['name' => 'Ion Exchange Water Technologies', 'code' => 'SUP-ION-03', 'contact_person' => 'Deepak Verma', 'phone' => '+91 11 2578 9900', 'email' => 'delhi@ionexchange.co.in', 'city' => 'New Delhi', 'gst' => '07AAACI1122H1Z5'],
                ],
                'customers' => [
                    ['name' => 'Metro Cash & Carry NCR', 'code' => 'CUS-MET-01', 'shop_name' => 'Metro Wholesale Delhi', 'phone' => '+91 11 6688 9900', 'email' => 'beverages@metro.co.in', 'city' => 'New Delhi', 'gst' => '07AAACM3344J1Z8'],
                    ['name' => 'Delhi Airport Lounges & Retail', 'code' => 'CUS-DEL-02', 'shop_name' => 'T3 International Transit', 'phone' => '+91 11 4987 6543', 'email' => 'procurement@delhiairport.in', 'city' => 'New Delhi', 'gst' => '07AAACD9988C1Z2'],
                    ['name' => 'Grand Regency Hotels & Resorts', 'code' => 'CUS-REG-03', 'shop_name' => 'Regency Suites Noida', 'phone' => '+91 120 488 2200', 'email' => 'fnb@grandregency.com', 'city' => 'Noida', 'gst' => '09AAACG1122D1Z6'],
                    ['name' => 'City Express Departmental Stores', 'code' => 'CUS-EXP-04', 'shop_name' => 'City Express Galleria', 'phone' => '+91 124 422 3344', 'email' => 'purchase@cityexpress.in', 'city' => 'Gurugram', 'gst' => '06AAACE4455P1Z1'],
                ],
                'staff' => [
                    ['first_name' => 'Rajesh', 'last_name' => 'Khurana', 'employee_id' => 'STF-AQ-01', 'designation' => 'Plant Engineering Head', 'employee_type' => 'salaried', 'salary_amount' => 65000, 'basic_salary' => 50000, 'dept_code' => 'DEP-RO', 'pf_applicable' => true],
                    ['first_name' => 'Surender', 'last_name' => 'Yadav', 'employee_id' => 'STF-AQ-02', 'designation' => 'Bottling Line Mechanic', 'employee_type' => 'hourly', 'hourly_rate' => 160, 'dept_code' => 'DEP-BTL', 'esi_applicable' => true],
                    ['first_name' => 'Pooja', 'last_name' => 'Verma', 'employee_id' => 'STF-AQ-03', 'designation' => 'Water Quality Microbiologist', 'employee_type' => 'contract', 'salary_amount' => 35000, 'basic_salary' => 35000, 'dept_code' => 'DEP-QC'],
                    ['first_name' => 'Deepak', 'last_name' => 'Singh', 'employee_id' => 'STF-AQ-04', 'designation' => 'Pallet Loading Operator', 'employee_type' => 'piece_rate', 'piece_rate' => 4.5, 'dept_code' => 'DEP-LOG'],
                    ['first_name' => 'Jagdish', 'last_name' => 'Prasad', 'employee_id' => 'STF-AQ-05', 'designation' => 'Forklift Yard Attendant', 'employee_type' => 'daily_wage', 'salary_amount' => 900, 'dept_code' => 'DEP-LOG', 'esi_applicable' => true],
                ],
            ],

            // 3. Tailoring & Garments
            [
                'short_name' => 'Elegant Stitch',
                'domain' => 'elegantstitch.com',
                'company' => [
                    'name' => 'Elegant Stitch Boutique',
                    'code' => 'ELEGANT-STITCH',
                    'registration_no' => 'U18109MH2021PTC356789',
                    'gst_no' => '27AAACE1122C1Z6',
                    'pan_no' => 'AAACE1122C',
                    'address' => 'Plot 104, Turner Road, Bandra West, Mumbai, Maharashtra 400050',
                    'phone' => '+91 22 2640 5566',
                    'email' => 'concierge@elegantstitch.com',
                    'website' => 'https://elegantstitch.example.com',
                    'business_type' => 'retail',
                ],
                'bank' => ['name' => 'ICICI Bank Fashion Hub', 'acc_no' => '001105023948', 'ifsc' => 'ICIC0000011'],
                'modules' => ['staff_hr', 'inventory', 'purchase', 'sales', 'accounts', 'production', 'crm'],
                'branches' => [
                    ['name' => 'Bandra Flagship Studio & Workshop', 'code' => 'BR-BAN-01', 'address' => 'Turner Road, Bandra West', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'postal_code' => '400050', 'contact_number' => '+91 22 2640 5567', 'email' => 'bandra@elegantstitch.com', 'is_main_branch' => true],
                    ['name' => 'Lower Parel Stitching Atelier', 'code' => 'BR-PAR-02', 'address' => 'Mathuradas Mill Compound, Lower Parel', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'postal_code' => '400013', 'contact_number' => '+91 22 2498 3344', 'email' => 'atelier@elegantstitch.com'],
                    ['name' => 'Colaba Heritage Gallery', 'code' => 'BR-COL-03', 'address' => 'Apollo Bunder, Colaba', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'postal_code' => '400001', 'contact_number' => '+91 22 2284 1122', 'email' => 'colaba@elegantstitch.com'],
                ],
                'departments' => [
                    ['name' => 'Bespoke Cutting & Pattern Design', 'code' => 'DEP-CUT', 'type' => 'production'],
                    ['name' => 'Tailoring & Hand Embroidery', 'code' => 'DEP-STITCH', 'type' => 'production'],
                    ['name' => 'Client Fitting & Styling', 'code' => 'DEP-STYLE', 'type' => 'sales'],
                    ['name' => 'Textiles & Designer Trims', 'code' => 'DEP-TEX', 'type' => 'inventory'],
                    ['name' => 'Boutique Accounts', 'code' => 'DEP-ACC', 'type' => 'accounts'],
                ],
                'godowns' => [
                    ['name' => 'Silk & Brocade Textile Vault', 'code' => 'GDW-BAN-TEX', 'location_type' => 'store', 'branch_code' => 'BR-BAN-01'],
                    ['name' => 'Designer Trims & Zippers Store', 'code' => 'GDW-BAN-TRIM', 'location_type' => 'store', 'branch_code' => 'BR-BAN-01'],
                    ['name' => 'Finished Couture Ready Room', 'code' => 'GDW-PAR-FG', 'location_type' => 'store', 'branch_code' => 'BR-PAR-02'],
                ],
                'units' => [
                    ['name' => 'Metre', 'symbol' => 'MTR', 'type' => 'length'],
                    ['name' => 'Piece', 'symbol' => 'PCS', 'type' => 'quantity'],
                    ['name' => 'Set', 'symbol' => 'SET', 'type' => 'quantity'],
                    ['name' => 'Roll', 'symbol' => 'ROLL', 'type' => 'quantity'],
                    ['name' => 'Dozen', 'symbol' => 'DOZ', 'type' => 'quantity'],
                ],
                'categories' => [
                    ['name' => 'Luxury Fabrics', 'description' => 'Silks, wools and linens', 'subcategories' => ['Pure Silk & Brocade', 'Egyptian Cotton']],
                    ['name' => 'Sewing Trims & Zari', 'description' => 'Zippers, buttons and threads', 'subcategories' => ['Zippers & Buttons', 'Embroidery Threads']],
                    ['name' => 'Bespoke Couture', 'description' => 'Made to measure garments', 'subcategories' => ['Bridal & Sherwani', 'Tailored Suits']],
                ],
                'raw_materials' => [
                    ['name' => 'Pure Mulberry Silk 60g Fabric', 'code' => 'RM-SILK-01', 'category' => 'Luxury Fabrics', 'subcategory' => 'Pure Silk & Brocade', 'unit' => 'MTR', 'price' => 750, 'gst' => 5, 'hsn' => '5007'],
                    ['name' => 'Giza Egyptian Cotton Shirting', 'code' => 'RM-COT-01', 'category' => 'Luxury Fabrics', 'subcategory' => 'Egyptian Cotton', 'unit' => 'MTR', 'price' => 480, 'gst' => 5, 'hsn' => '5208'],
                    ['name' => 'Metallic Hand Zari Gold Thread', 'code' => 'RM-ZARI-01', 'category' => 'Sewing Trims & Zari', 'subcategory' => 'Embroidery Threads', 'unit' => 'ROLL', 'price' => 190, 'gst' => 12, 'hsn' => '5605'],
                ],
                'products' => [
                    ['name' => 'Handcrafted Raw Silk Sherwani Set', 'code' => 'FG-SHER-01', 'category' => 'Bespoke Couture', 'subcategory' => 'Bridal & Sherwani', 'unit' => 'SET', 'price' => 28500, 'gst' => 12, 'hsn' => '6203'],
                    ['name' => 'Bespoke Two-Piece Italian Wool Suit', 'code' => 'FG-SUIT-02', 'category' => 'Bespoke Couture', 'subcategory' => 'Tailored Suits', 'unit' => 'SET', 'price' => 22000, 'gst' => 12, 'hsn' => '6203'],
                    ['name' => 'Embroidered Chanderi Kurti Set', 'code' => 'FG-KURT-03', 'category' => 'Bespoke Couture', 'subcategory' => 'Bridal & Sherwani', 'unit' => 'SET', 'price' => 6500, 'gst' => 12, 'hsn' => '6204'],
                ],
                'suppliers' => [
                    ['name' => 'Surat Silk Weavers Co-op Society', 'code' => 'SUP-SUR-01', 'contact_person' => 'Bhavesh Patel', 'phone' => '+91 261 289 4455', 'email' => 'sales@suratsilk.org', 'city' => 'Surat', 'gst' => '24AAACS7788D1Z5'],
                    ['name' => 'Arvind Mills Luxury Textiles', 'code' => 'SUP-ARV-02', 'contact_person' => 'Gautam Shah', 'phone' => '+91 79 6677 8899', 'email' => 'cottons@arvind.in', 'city' => 'Ahmedabad', 'gst' => '24AAACA1122K1Z9'],
                    ['name' => 'Coats India Thread Mills', 'code' => 'SUP-COA-03', 'contact_person' => 'M. Joseph', 'phone' => '+91 22 2839 1100', 'email' => 'threads@coats.com', 'city' => 'Mumbai', 'gst' => '27AAACC4455L1Z1'],
                ],
                'customers' => [
                    ['name' => 'Bollywood Celebrity Styling Agency', 'code' => 'CUS-BOL-01', 'shop_name' => 'StarStyle Studio Bandra', 'phone' => '+91 98200 11223', 'email' => 'couture@starstyle.in', 'city' => 'Mumbai', 'gst' => '27AAACS9988G1Z7'],
                    ['name' => 'Luxury Wedding Shoppers Consortium', 'code' => 'CUS-WED-02', 'shop_name' => 'Juhu Bridal Studio', 'phone' => '+91 98210 22334', 'email' => 'bridal@weddingshop.in', 'city' => 'Mumbai', 'gst' => '27AAACW5566M1Z2'],
                    ['name' => 'High-Street Executive Patrons', 'code' => 'CUS-EXE-03', 'shop_name' => 'Nariman Point Club', 'phone' => '+91 98205 33445', 'email' => 'exec@southbombay.org', 'city' => 'Mumbai', 'gst' => '27AAACE7788P1Z4'],
                    ['name' => 'Bridal Couturiers Reseller Lounge', 'code' => 'CUS-COU-04', 'shop_name' => 'Colaba Heritage Boutique', 'phone' => '+91 98201 44556', 'email' => 'orders@couturiers.in', 'city' => 'Mumbai', 'gst' => '27AAACB1122E1Z8'],
                ],
                'staff' => [
                    ['first_name' => 'Farhan', 'last_name' => 'Akhtar', 'employee_id' => 'STF-EL-01', 'designation' => 'Head Fashion Stylist', 'employee_type' => 'salaried', 'salary_amount' => 70000, 'basic_salary' => 55000, 'dept_code' => 'DEP-STYLE', 'pf_applicable' => true],
                    ['first_name' => 'Mohammad', 'last_name' => 'Salim', 'employee_id' => 'STF-EL-02', 'designation' => 'Master Pattern Cutter', 'employee_type' => 'hourly', 'hourly_rate' => 220, 'dept_code' => 'DEP-CUT', 'esi_applicable' => true],
                    ['first_name' => 'Ananya', 'last_name' => 'Roy', 'employee_id' => 'STF-EL-03', 'designation' => 'Client Fitting Consultant', 'employee_type' => 'contract', 'salary_amount' => 38000, 'basic_salary' => 38000, 'dept_code' => 'DEP-STYLE'],
                    ['first_name' => 'Rasheed', 'last_name' => 'Khan', 'employee_id' => 'STF-EL-04', 'designation' => 'Hand Zari Embroiderer', 'employee_type' => 'piece_rate', 'piece_rate' => 450, 'dept_code' => 'DEP-STITCH'],
                    ['first_name' => 'Sunita', 'last_name' => 'Bai', 'employee_id' => 'STF-EL-05', 'designation' => 'Garment Finishing & Ironing', 'employee_type' => 'daily_wage', 'salary_amount' => 750, 'dept_code' => 'DEP-STITCH', 'esi_applicable' => true],
                ],
            ],

            // 4. Furniture Manufacturing
            [
                'short_name' => 'Royal Wood',
                'domain' => 'royalwood.com',
                'company' => [
                    'name' => 'Royal Wood Furniture Works',
                    'code' => 'ROYAL-WOOD',
                    'registration_no' => 'U36100KA2020PTC145678',
                    'gst_no' => '29AAACR3344D1Z2',
                    'pan_no' => 'AAACR3344D',
                    'address' => 'Plot 78, Peenya Industrial Area, Stage 3, Bangalore, Karnataka 560058',
                    'phone' => '+91 80 2839 7722',
                    'email' => 'hello@royalwood.com',
                    'website' => 'https://royalwood.example.com',
                    'business_type' => 'manufacturing',
                ],
                'bank' => ['name' => 'Canara Bank Peenya Branch', 'acc_no' => '1289201009847', 'ifsc' => 'CNRB0001289'],
                'modules' => ['staff_hr', 'inventory', 'purchase', 'sales', 'accounts', 'production', 'vehicle_route', 'quality', 'crm'],
                'branches' => [
                    ['name' => 'Peenya Industrial Factory & Mill', 'code' => 'BR-PEE-01', 'address' => 'Stage 3, Peenya Industrial Area', 'city' => 'Bangalore', 'state' => 'Karnataka', 'postal_code' => '560058', 'contact_number' => '+91 80 2839 7723', 'email' => 'factory@royalwood.com', 'is_main_branch' => true],
                    ['name' => 'Whitefield Design Studio & Showroom', 'code' => 'BR-WHF-02', 'address' => 'ITPB Main Road, Whitefield', 'city' => 'Bangalore', 'state' => 'Karnataka', 'postal_code' => '560066', 'contact_number' => '+91 80 4125 8899', 'email' => 'whitefield@royalwood.com'],
                    ['name' => 'Mysore Road Timber Seasoning Yard', 'code' => 'BR-MYS-03', 'address' => 'Nayandahalli, Mysore Road', 'city' => 'Bangalore', 'state' => 'Karnataka', 'postal_code' => '560039', 'contact_number' => '+91 80 2860 1144', 'email' => 'yard@royalwood.com'],
                ],
                'departments' => [
                    ['name' => 'Wood Joinery & Machining', 'code' => 'DEP-JOIN', 'type' => 'production'],
                    ['name' => 'Sanding Polishing & PU Finishing', 'code' => 'DEP-FIN', 'type' => 'production'],
                    ['name' => 'Cushioning & Upholstery', 'code' => 'DEP-UPH', 'type' => 'production'],
                    ['name' => 'Timber & Hardware Stores', 'code' => 'DEP-STORE', 'type' => 'inventory'],
                    ['name' => 'Corporate Billing & Finance', 'code' => 'DEP-ACC', 'type' => 'accounts'],
                ],
                'godowns' => [
                    ['name' => 'Seasoned Timber Yard', 'code' => 'GDW-PEE-TIMBER', 'location_type' => 'warehouse', 'branch_code' => 'BR-PEE-01'],
                    ['name' => 'Hardware & Lacquer Store', 'code' => 'GDW-PEE-HARD', 'location_type' => 'store', 'branch_code' => 'BR-PEE-01'],
                    ['name' => 'Finished Furniture Showroom Godown', 'code' => 'GDW-WHF-SHOW', 'location_type' => 'store', 'branch_code' => 'BR-WHF-02'],
                ],
                'units' => [
                    ['name' => 'Cubic Feet', 'symbol' => 'CFT', 'type' => 'other'],
                    ['name' => 'Piece', 'symbol' => 'PCS', 'type' => 'quantity'],
                    ['name' => 'Set', 'symbol' => 'SET', 'type' => 'quantity'],
                    ['name' => 'Square Feet', 'symbol' => 'SQFT', 'type' => 'other'],
                    ['name' => 'Litre', 'symbol' => 'LTR', 'type' => 'volume'],
                ],
                'categories' => [
                    ['name' => 'Hardwood & Timber', 'description' => 'Seasoned hardwood planks and logs', 'subcategories' => ['Teak Wood', 'Sheesham & Oak']],
                    ['name' => 'Hardware & Adhesives', 'description' => 'Fittings, slides and wood glues', 'subcategories' => ['Drawer Slides', 'Wood Adhesives']],
                    ['name' => 'Living & Dining Furniture', 'description' => 'Fine crafted home furniture', 'subcategories' => ['Dining Sets', 'Sofas & Recliners']],
                ],
                'raw_materials' => [
                    ['name' => 'Seasoned CP Teak Wood Planks 2-inch', 'code' => 'RM-TEAK-CP', 'category' => 'Hardwood & Timber', 'subcategory' => 'Teak Wood', 'unit' => 'CFT', 'price' => 3400, 'gst' => 18, 'hsn' => '4407'],
                    ['name' => 'Solid Sheesham Wood Square Beams', 'code' => 'RM-SHEESH', 'category' => 'Hardwood & Timber', 'subcategory' => 'Sheesham & Oak', 'unit' => 'CFT', 'price' => 2100, 'gst' => 18, 'hsn' => '4407'],
                    ['name' => 'Fevicol Marine Waterproof Wood Glue', 'code' => 'RM-FEV-10KG', 'category' => 'Hardware & Adhesives', 'subcategory' => 'Wood Adhesives', 'unit' => 'LTR', 'price' => 220, 'gst' => 18, 'hsn' => '3506'],
                ],
                'products' => [
                    ['name' => 'Royal Solid Teak 6-Seater Dining Table', 'code' => 'FG-TAB-TEAK6', 'category' => 'Living & Dining Furniture', 'subcategory' => 'Dining Sets', 'unit' => 'PCS', 'price' => 48000, 'gst' => 12, 'hsn' => '9403'],
                    ['name' => 'Sheesham Executive Ergonomic Desk', 'code' => 'FG-DESK-SH01', 'category' => 'Living & Dining Furniture', 'subcategory' => 'Dining Sets', 'unit' => 'PCS', 'price' => 26500, 'gst' => 12, 'hsn' => '9403'],
                    ['name' => 'Chesterfield 3-Seater Leatherette Sofa', 'code' => 'FG-SOFA-CH3', 'category' => 'Living & Dining Furniture', 'subcategory' => 'Sofas & Recliners', 'unit' => 'PCS', 'price' => 36000, 'gst' => 12, 'hsn' => '9401'],
                ],
                'suppliers' => [
                    ['name' => 'Dandeli Forest Timber Depot', 'code' => 'SUP-DAN-01', 'contact_person' => 'G. Manjunath', 'phone' => '+91 8284 233 445', 'email' => 'timber@dandeli.org', 'city' => 'Dandeli', 'gst' => '29AAACD4455H1Z3'],
                    ['name' => 'Hettich India Hardware Systems', 'code' => 'SUP-HET-02', 'contact_person' => 'Ashok Nayak', 'phone' => '+91 80 4152 7700', 'email' => 'fittings@hettich.in', 'city' => 'Bangalore', 'gst' => '29AAACH1122L1Z7'],
                    ['name' => 'Asian Paints Wood Coatings Division', 'code' => 'SUP-ASP-03', 'contact_person' => 'Sandeep Jain', 'phone' => '+91 80 2221 8899', 'email' => 'woodcoatings@asianpaints.com', 'city' => 'Bangalore', 'gst' => '29AAACA5566P1Z1'],
                ],
                'customers' => [
                    ['name' => 'Prestige Estates Luxury Villa Projects', 'code' => 'CUS-PRE-01', 'shop_name' => 'Prestige Golfshire Procurement', 'phone' => '+91 80 2559 1080', 'email' => 'interiors@prestigeconstructions.com', 'city' => 'Bangalore', 'gst' => '29AAACP9988D1Z6'],
                    ['name' => 'Urban Living High-End Interior Designers', 'code' => 'CUS-URB-02', 'shop_name' => 'Urban Living Indiranagar', 'phone' => '+91 80 4112 3344', 'email' => 'designs@urbanliving.in', 'city' => 'Bangalore', 'gst' => '29AAACU7788K1Z2'],
                    ['name' => 'Manyata Tech Park Corporate Offices', 'code' => 'CUS-MAN-03', 'shop_name' => 'Embassy Manyata Ops', 'phone' => '+91 80 4179 9999', 'email' => 'facilities@manyata.com', 'city' => 'Bangalore', 'gst' => '29AAACE1122M1Z8'],
                    ['name' => 'Royal Homes Heritage Furniture Retail', 'code' => 'CUS-ROY-04', 'shop_name' => 'Royal Homes Mysore', 'phone' => '+91 821 244 5566', 'email' => 'sales@royalhomes.in', 'city' => 'Mysore', 'gst' => '29AAACR5566N1Z4'],
                ],
                'staff' => [
                    ['first_name' => 'Basavaraj', 'last_name' => 'Gowda', 'employee_id' => 'STF-RW-01', 'designation' => 'Master Joinery Supervisor', 'employee_type' => 'salaried', 'salary_amount' => 50000, 'basic_salary' => 40000, 'dept_code' => 'DEP-JOIN', 'pf_applicable' => true],
                    ['first_name' => 'Nanjundaiah', 'last_name' => 'M', 'employee_id' => 'STF-RW-02', 'designation' => 'Spindle Molder Operator', 'employee_type' => 'hourly', 'hourly_rate' => 190, 'dept_code' => 'DEP-JOIN', 'esi_applicable' => true],
                    ['first_name' => 'Arpit', 'last_name' => 'Sharma', 'employee_id' => 'STF-RW-03', 'designation' => 'Showroom Interior Consultant', 'employee_type' => 'contract', 'salary_amount' => 35000, 'basic_salary' => 35000, 'dept_code' => 'DEP-FIN'],
                    ['first_name' => 'Krishna', 'last_name' => 'Murthy', 'employee_id' => 'STF-RW-04', 'designation' => 'Hand Wood Polisher', 'employee_type' => 'piece_rate', 'piece_rate' => 650, 'dept_code' => 'DEP-FIN'],
                    ['first_name' => 'Suresh', 'last_name' => 'Naik', 'employee_id' => 'STF-RW-05', 'designation' => 'Timber Log Handler', 'employee_type' => 'daily_wage', 'salary_amount' => 850, 'dept_code' => 'DEP-STORE', 'esi_applicable' => true],
                ],
            ],

            // 5. Construction & Contracting
            [
                'short_name' => 'BuildRight',
                'domain' => 'buildrightconst.com',
                'company' => [
                    'name' => 'BuildRight Constructions',
                    'code' => 'BUILDRIGHT-CONST',
                    'registration_no' => 'U45200TG2020PTC127890',
                    'gst_no' => '36AAACB5566C1Z8',
                    'pan_no' => 'AAACB5566C',
                    'address' => 'Tower B, 4th Floor, Hitec City, Madhapur, Hyderabad, Telangana 500081',
                    'phone' => '+91 40 4488 9900',
                    'email' => 'projects@buildrightconst.com',
                    'website' => 'https://buildrightconst.example.com',
                    'business_type' => 'service',
                ],
                'bank' => ['name' => 'State Bank of India Corporate Hub', 'acc_no' => '38910029381', 'ifsc' => 'SBIN0011746'],
                'modules' => ['staff_hr', 'inventory', 'purchase', 'sales', 'accounts', 'production', 'vehicle_route'],
                'branches' => [
                    ['name' => 'Hyderabad Corporate HQ & Project Office', 'code' => 'BR-HYD-01', 'address' => 'Hitec City, Madhapur', 'city' => 'Hyderabad', 'state' => 'Telangana', 'postal_code' => '500081', 'contact_number' => '+91 40 4488 9901', 'email' => 'corp@buildrightconst.com', 'is_main_branch' => true],
                    ['name' => 'Gachibowli High-Rise Project Site', 'code' => 'BR-GAC-02', 'address' => 'Financial District, Nanakramguda, Gachibowli', 'city' => 'Hyderabad', 'state' => 'Telangana', 'postal_code' => '500032', 'contact_number' => '+91 40 2988 4400', 'email' => 'gachibowli@buildrightconst.com'],
                    ['name' => 'Shamshabad Precast & Batching Yard', 'code' => 'BR-SHA-03', 'address' => 'ORR Junction, Shamshabad', 'city' => 'Hyderabad', 'state' => 'Telangana', 'postal_code' => '501218', 'contact_number' => '+91 40 2748 1122', 'email' => 'precast@buildrightconst.com'],
                ],
                'departments' => [
                    ['name' => 'Civil Project Execution', 'code' => 'DEP-CIVIL', 'type' => 'production'],
                    ['name' => 'Machinery & Heavy Equipment', 'code' => 'DEP-MACH', 'type' => 'inventory'],
                    ['name' => 'Structural Quality & Site Safety', 'code' => 'DEP-ENG', 'type' => 'quality'],
                    ['name' => 'Site Procurement & Stores', 'code' => 'DEP-STORES', 'type' => 'purchase'],
                    ['name' => 'Project Billing & Costing', 'code' => 'DEP-COST', 'type' => 'accounts'],
                ],
                'godowns' => [
                    ['name' => 'Gachibowli Site Material Yard', 'code' => 'GDW-GAC-YARD', 'location_type' => 'warehouse', 'branch_code' => 'BR-GAC-02'],
                    ['name' => 'Shamshabad Batching Plant Depot', 'code' => 'GDW-SHA-DEPOT', 'location_type' => 'warehouse', 'branch_code' => 'BR-SHA-03'],
                    ['name' => 'Central Tools & Safety Equipment Store', 'code' => 'GDW-HYD-TOOL', 'location_type' => 'store', 'branch_code' => 'BR-HYD-01'],
                ],
                'units' => [
                    ['name' => 'Metric Ton', 'symbol' => 'TON', 'type' => 'weight'],
                    ['name' => 'Bag of 50kg', 'symbol' => 'BAG', 'type' => 'quantity'],
                    ['name' => 'Cubic Feet', 'symbol' => 'CFT', 'type' => 'other'],
                    ['name' => 'Square Feet', 'symbol' => 'SQFT', 'type' => 'other'],
                    ['name' => 'Piece', 'symbol' => 'PCS', 'type' => 'quantity'],
                ],
                'categories' => [
                    ['name' => 'Structural Steel & TMT', 'description' => 'TMT rebars and structural sections', 'subcategories' => ['Fe 550D Rebars']],
                    ['name' => 'Cement & Aggregates', 'description' => 'Pozzolana cement and blue metal', 'subcategories' => ['Pozzolana Cement', 'Coarse Aggregates']],
                    ['name' => 'Precast Infrastructure', 'description' => 'Pavers and RCC drainage components', 'subcategories' => ['Paver Blocks', 'RCC Chambers']],
                ],
                'raw_materials' => [
                    ['name' => 'Tata Tiscon 550D TMT Rebar 16mm', 'code' => 'RM-TMT-16MM', 'category' => 'Structural Steel & TMT', 'subcategory' => 'Fe 550D Rebars', 'unit' => 'TON', 'price' => 62000, 'gst' => 18, 'hsn' => '7214'],
                    ['name' => 'UltraTech Super PPC Cement 50kg', 'code' => 'RM-CEM-PPC50', 'category' => 'Cement & Aggregates', 'subcategory' => 'Pozzolana Cement', 'unit' => 'BAG', 'price' => 385, 'gst' => 28, 'hsn' => '2523'],
                    ['name' => 'Crushed Blue Granite Metal 20mm', 'code' => 'RM-AGG-20MM', 'category' => 'Cement & Aggregates', 'subcategory' => 'Coarse Aggregates', 'unit' => 'TON', 'price' => 850, 'gst' => 5, 'hsn' => '2517'],
                ],
                'products' => [
                    ['name' => 'Heavy Duty Interlocking Paver Block 80mm', 'code' => 'FG-PAV-80MM', 'category' => 'Precast Infrastructure', 'subcategory' => 'Paver Blocks', 'unit' => 'SQFT', 'price' => 68, 'gst' => 18, 'hsn' => '6810'],
                    ['name' => 'Precast RCC Manhole Chamber 1000mm', 'code' => 'FG-RCC-MAN', 'category' => 'Precast Infrastructure', 'subcategory' => 'RCC Chambers', 'unit' => 'PCS', 'price' => 4800, 'gst' => 18, 'hsn' => '6810'],
                    ['name' => 'Ready Mix Concrete M30 Grade', 'code' => 'FG-RMC-M30', 'category' => 'Precast Infrastructure', 'subcategory' => 'RCC Chambers', 'unit' => 'CFT', 'price' => 165, 'gst' => 18, 'hsn' => '3824'],
                ],
                'suppliers' => [
                    ['name' => 'Tata Steel Infrastructure Marketing', 'code' => 'SUP-TATA-01', 'contact_person' => 'K. V. Subbarao', 'phone' => '+91 40 6625 3300', 'email' => 'tmt@tatasteel.com', 'city' => 'Hyderabad', 'gst' => '36AAACT1122D1Z9'],
                    ['name' => 'UltraTech Cement Regional Depot', 'code' => 'SUP-ULT-02', 'contact_person' => 'Mohan Krishna', 'phone' => '+91 40 2341 8800', 'email' => 'cement@ultratech.com', 'city' => 'Hyderabad', 'gst' => '36AAACU4455E1Z5'],
                    ['name' => 'Deccan Quarry Aggregates Ltd.', 'code' => 'SUP-DEC-03', 'contact_person' => 'Ravi Varma', 'phone' => '+91 40 2789 2200', 'email' => 'aggregates@deccanquarry.in', 'city' => 'Rangareddy', 'gst' => '36AAACD8899F1Z1'],
                ],
                'customers' => [
                    ['name' => 'Telangana State Infrastructure Housing', 'code' => 'CUS-TSI-01', 'shop_name' => 'TSIIC Procurement Dept', 'phone' => '+91 40 2323 7700', 'email' => 'projects@tsiic.telangana.gov.in', 'city' => 'Hyderabad', 'gst' => '36AAACT7788P1Z3'],
                    ['name' => 'Cyber City IT Towers Developers', 'code' => 'CUS-CYB-02', 'shop_name' => 'Cyber Towers Phase 2', 'phone' => '+91 40 4010 2200', 'email' => 'build@cybercity.in', 'city' => 'Hyderabad', 'gst' => '36AAACC1122K1Z7'],
                    ['name' => 'MyHome Construction Subcontracts', 'code' => 'CUS-MYH-03', 'shop_name' => 'MyHome Hub Kondapur', 'phone' => '+91 40 6688 8888', 'email' => 'subcontracts@myhomegroup.in', 'city' => 'Hyderabad', 'gst' => '36AAACM5566L1Z4'],
                    ['name' => 'Aparna Mega Villas Infrastructure', 'code' => 'CUS-APA-04', 'shop_name' => 'Aparna Sarovar Site', 'phone' => '+91 40 2335 2708', 'email' => 'procurement@aparnaconstructions.com', 'city' => 'Hyderabad', 'gst' => '36AAACA9988M1Z0'],
                ],
                'staff' => [
                    ['first_name' => 'Venkat', 'last_name' => 'Reddy', 'employee_id' => 'STF-BR-01', 'designation' => 'Chief Project Engineer', 'employee_type' => 'salaried', 'salary_amount' => 75000, 'basic_salary' => 60000, 'dept_code' => 'DEP-CIVIL', 'pf_applicable' => true],
                    ['first_name' => 'Chandrasekhar', 'last_name' => 'N', 'employee_id' => 'STF-BR-02', 'designation' => 'Tower Crane Operator', 'employee_type' => 'hourly', 'hourly_rate' => 250, 'dept_code' => 'DEP-MACH', 'esi_applicable' => true],
                    ['first_name' => 'Srikanth', 'last_name' => 'Rao', 'employee_id' => 'STF-BR-03', 'designation' => 'Structural Safety Inspector', 'employee_type' => 'contract', 'salary_amount' => 42000, 'basic_salary' => 42000, 'dept_code' => 'DEP-ENG'],
                    ['first_name' => 'Narsimha', 'last_name' => 'B', 'employee_id' => 'STF-BR-04', 'designation' => 'Steel Bar Bender & Tier', 'employee_type' => 'piece_rate', 'piece_rate' => 180, 'dept_code' => 'DEP-CIVIL'],
                    ['first_name' => 'Yadaiah', 'last_name' => 'K', 'employee_id' => 'STF-BR-05', 'designation' => 'Civil Concrete Mason', 'employee_type' => 'daily_wage', 'salary_amount' => 950, 'dept_code' => 'DEP-CIVIL', 'esi_applicable' => true],
                ],
            ],

            // 6. Automobile Services & Dealership
            [
                'short_name' => 'DriveCare',
                'domain' => 'drivecareauto.com',
                'company' => [
                    'name' => 'DriveCare Automobile Services',
                    'code' => 'DRIVECARE-AUTO',
                    'registration_no' => 'U50200PN2021PTC178901',
                    'gst_no' => '27AAACD7788E1Z9',
                    'pan_no' => 'AAACD7788E',
                    'address' => 'Survey 45/2, Old Mumbai-Pune Highway, Wakad, Pune, Maharashtra 411057',
                    'phone' => '+91 20 6722 3344',
                    'email' => 'service@drivecareauto.com',
                    'website' => 'https://drivecareauto.example.com',
                    'business_type' => 'service',
                ],
                'bank' => ['name' => 'Axis Bank Auto Hub', 'acc_no' => '921020038475829', 'ifsc' => 'UTIB0000412'],
                'modules' => ['staff_hr', 'inventory', 'purchase', 'sales', 'accounts', 'crm'],
                'branches' => [
                    ['name' => 'Wakad Mega Workshop & Hub', 'code' => 'BR-WAK-01', 'address' => 'Old Mumbai-Pune Highway, Wakad', 'city' => 'Pune', 'state' => 'Maharashtra', 'postal_code' => '411057', 'contact_number' => '+91 20 6722 3345', 'email' => 'wakad@drivecareauto.com', 'is_main_branch' => true],
                    ['name' => 'Hadapsar Express Service Center', 'code' => 'BR-HAD-02', 'address' => 'Magarpatta Road, Hadapsar', 'city' => 'Pune', 'state' => 'Maharashtra', 'postal_code' => '411028', 'contact_number' => '+91 20 2689 1100', 'email' => 'hadapsar@drivecareauto.com'],
                    ['name' => 'Bhosari Spares & Body Repair Depot', 'code' => 'BR-BHO-03', 'address' => 'MIDC Bhosari, Industrial Area', 'city' => 'Pune', 'state' => 'Maharashtra', 'postal_code' => '411026', 'contact_number' => '+91 20 2712 4455', 'email' => 'bhosari@drivecareauto.com'],
                ],
                'departments' => [
                    ['name' => 'Mechanical Diagnostic & Engine Bay', 'code' => 'DEP-MECH', 'type' => 'production'],
                    ['name' => 'Bodyshop Paint & Denting', 'code' => 'DEP-BODY', 'type' => 'production'],
                    ['name' => 'Spare Parts & Lubricants Store', 'code' => 'DEP-PARTS', 'type' => 'inventory'],
                    ['name' => 'Service Advisors & Front Desk', 'code' => 'DEP-DESK', 'type' => 'sales'],
                    ['name' => 'Workshop Accounts', 'code' => 'DEP-ACC', 'type' => 'accounts'],
                ],
                'godowns' => [
                    ['name' => 'OEM Spares & Brake Parts Godown', 'code' => 'GDW-WAK-PARTS', 'location_type' => 'store', 'branch_code' => 'BR-WAK-01'],
                    ['name' => 'Engine Oils & Chemical Drum Yard', 'code' => 'GDW-WAK-OIL', 'location_type' => 'warehouse', 'branch_code' => 'BR-WAK-01'],
                    ['name' => 'Hadapsar Fast-Moving Spares Rack', 'code' => 'GDW-HAD-RACK', 'location_type' => 'store', 'branch_code' => 'BR-HAD-02'],
                ],
                'units' => [
                    ['name' => 'Piece', 'symbol' => 'PCS', 'type' => 'quantity'],
                    ['name' => 'Litre', 'symbol' => 'LTR', 'type' => 'volume'],
                    ['name' => 'Set of Pair', 'symbol' => 'SET', 'type' => 'quantity'],
                    ['name' => 'Can', 'symbol' => 'CAN', 'type' => 'quantity'],
                    ['name' => 'Service Job', 'symbol' => 'SRV', 'type' => 'quantity'],
                ],
                'categories' => [
                    ['name' => 'Lubricants & Fluids', 'description' => 'Engine oils, coolants and brake fluids', 'subcategories' => ['Synthetic Oils', 'Coolants']],
                    ['name' => 'Brake & Suspension Parts', 'description' => 'Brake pads, discs and struts', 'subcategories' => ['Brake Pads', 'Filters']],
                    ['name' => 'Service Packages', 'description' => 'Standard preventive maintenance', 'subcategories' => ['Periodic Services', 'Detailing']],
                ],
                'raw_materials' => [
                    ['name' => 'Castrol Magnatec Fully Synthetic 5W-30', 'code' => 'RM-OIL-5W30', 'category' => 'Lubricants & Fluids', 'subcategory' => 'Synthetic Oils', 'unit' => 'LTR', 'price' => 520, 'gst' => 18, 'hsn' => '2710'],
                    ['name' => 'Bosch Front Disc Brake Pads Set', 'code' => 'RM-BRK-BOSCH', 'category' => 'Brake & Suspension Parts', 'subcategory' => 'Brake Pads', 'unit' => 'SET', 'price' => 1450, 'gst' => 28, 'hsn' => '8708'],
                    ['name' => 'Mann Engine Air Filter Element', 'code' => 'RM-FLT-AIR01', 'category' => 'Brake & Suspension Parts', 'subcategory' => 'Filters', 'unit' => 'PCS', 'price' => 380, 'gst' => 18, 'hsn' => '8421'],
                ],
                'products' => [
                    ['name' => 'Comprehensive Periodic Major Service', 'code' => 'FG-SRV-MAJOR', 'category' => 'Service Packages', 'subcategory' => 'Periodic Services', 'unit' => 'SRV', 'price' => 5500, 'gst' => 18, 'hsn' => '9987'],
                    ['name' => 'Express Oil Change & Inspection', 'code' => 'FG-SRV-OIL', 'category' => 'Service Packages', 'subcategory' => 'Periodic Services', 'unit' => 'SRV', 'price' => 2200, 'gst' => 18, 'hsn' => '9987'],
                    ['name' => '3M Ceramic Exterior Paint Protection', 'code' => 'FG-DET-CERAM', 'category' => 'Service Packages', 'subcategory' => 'Detailing', 'unit' => 'SRV', 'price' => 14500, 'gst' => 18, 'hsn' => '9987'],
                ],
                'suppliers' => [
                    ['name' => 'Bosch Automotive Aftermarket India', 'code' => 'SUP-BOS-01', 'contact_person' => 'Amitabh Saxena', 'phone' => '+91 20 6733 9900', 'email' => 'aftermarket@bosch.in', 'city' => 'Pune', 'gst' => '27AAACB1122D1Z3'],
                    ['name' => 'Castrol India Lubricants Distribution', 'code' => 'SUP-CAS-02', 'contact_person' => 'Girish Joshi', 'phone' => '+91 22 6698 4400', 'email' => 'distributor@castrol.co.in', 'city' => 'Mumbai', 'gst' => '27AAACC5566E1Z7'],
                    ['name' => 'Valeo OEM Automotive Components', 'code' => 'SUP-VAL-03', 'contact_person' => 'Nitin Kadam', 'phone' => '+91 20 2747 1122', 'email' => 'spares@valeo.in', 'city' => 'Pune', 'gst' => '27AAACV9988F1Z1'],
                ],
                'customers' => [
                    ['name' => 'Zoomcar Fleet Operations Pune', 'code' => 'CUS-ZOM-01', 'shop_name' => 'Zoomcar Pune Hub', 'phone' => '+91 20 4911 2200', 'email' => 'fleet.pune@zoomcar.com', 'city' => 'Pune', 'gst' => '27AAACZ1122G1Z9'],
                    ['name' => 'Orix Corporate Fleet Leasing', 'code' => 'CUS-ORX-02', 'shop_name' => 'Orix Hinjewadi Desk', 'phone' => '+91 20 6675 3300', 'email' => 'fleet@orixindia.com', 'city' => 'Pune', 'gst' => '27AAACO4455H1Z5'],
                    ['name' => 'Magarpatta Luxury Car Club', 'code' => 'CUS-MAG-03', 'shop_name' => 'Magarpatta City Club', 'phone' => '+91 20 2689 7788', 'email' => 'cars@magarpattacity.com', 'city' => 'Pune', 'gst' => '27AAACM8899J1Z1'],
                    ['name' => 'QuickCab Urban Taxi Fleet', 'code' => 'CUS-QCK-04', 'shop_name' => 'QuickCab Shivajinagar', 'phone' => '+91 20 2553 4455', 'email' => 'service@quickcab.in', 'city' => 'Pune', 'gst' => '27AAACO7788K1Z3'],
                ],
                'staff' => [
                    ['first_name' => 'Sachin', 'last_name' => 'Deshmukh', 'employee_id' => 'STF-DC-01', 'designation' => 'Service Director', 'employee_type' => 'salaried', 'salary_amount' => 65000, 'basic_salary' => 50000, 'dept_code' => 'DEP-MECH', 'pf_applicable' => true],
                    ['first_name' => 'Vijay', 'last_name' => 'Kamble', 'employee_id' => 'STF-DC-02', 'designation' => 'Diagnostic Auto Electrician', 'employee_type' => 'hourly', 'hourly_rate' => 200, 'dept_code' => 'DEP-MECH', 'esi_applicable' => true],
                    ['first_name' => 'Sneha', 'last_name' => 'Kulkarni', 'employee_id' => 'STF-DC-03', 'designation' => 'Service Customer Advisor', 'employee_type' => 'contract', 'salary_amount' => 34000, 'basic_salary' => 34000, 'dept_code' => 'DEP-DESK'],
                    ['first_name' => 'Pramod', 'last_name' => 'Shinde', 'employee_id' => 'STF-DC-04', 'designation' => 'Spray Booth Painter', 'employee_type' => 'piece_rate', 'piece_rate' => 500, 'dept_code' => 'DEP-BODY'],
                    ['first_name' => 'Ganesh', 'last_name' => 'Chavan', 'employee_id' => 'STF-DC-05', 'designation' => 'Workshop Bay Helper', 'employee_type' => 'daily_wage', 'salary_amount' => 800, 'dept_code' => 'DEP-PARTS', 'esi_applicable' => true],
                ],
            ],

            // 7. Agriculture & Farming
            [
                'short_name' => 'GreenField',
                'domain' => 'greenfieldagro.com',
                'company' => [
                    'name' => 'GreenField Agro Farms',
                    'code' => 'GREENFIELD-AGRO',
                    'registration_no' => 'U01100HP2020PTC089012',
                    'gst_no' => '02AAACG4455F1Z1',
                    'pan_no' => 'AAACG4455F',
                    'address' => 'Apple Valley Road, Theog, Shimla, Himachal Pradesh 171201',
                    'phone' => '+91 177 284 1155',
                    'email' => 'harvest@greenfieldagro.com',
                    'website' => 'https://greenfieldagro.example.com',
                    'business_type' => 'distribution',
                ],
                'bank' => ['name' => 'Punjab National Bank Shimla', 'acc_no' => '0412002100847291', 'ifsc' => 'PUNB0041200'],
                'modules' => ['staff_hr', 'inventory', 'purchase', 'sales', 'accounts', 'production', 'vehicle_route', 'quality'],
                'branches' => [
                    ['name' => 'Shimla Central Orchards & Packhouse', 'code' => 'BR-SHM-01', 'address' => 'Apple Valley Road, Theog', 'city' => 'Shimla', 'state' => 'Himachal Pradesh', 'postal_code' => '171201', 'contact_number' => '+91 177 284 1156', 'email' => 'orchard@greenfieldagro.com', 'is_main_branch' => true],
                    ['name' => 'Solan Cold Atmosphere CA Hub', 'code' => 'BR-SOL-02', 'address' => 'Chambaghat Industrial Area, Solan', 'city' => 'Solan', 'state' => 'Himachal Pradesh', 'postal_code' => '173213', 'contact_number' => '+91 1792 230 400', 'email' => 'solan@greenfieldagro.com'],
                    ['name' => 'Chandigarh Fresh Wholesale Point', 'code' => 'BR-CHD-03', 'address' => 'Sector 26 Grain Market, Chandigarh', 'city' => 'Chandigarh', 'state' => 'Chandigarh', 'postal_code' => '160019', 'contact_number' => '+91 172 279 3300', 'email' => 'chandigarh@greenfieldagro.com'],
                ],
                'departments' => [
                    ['name' => 'Orchard Farming & Soil Nutrition', 'code' => 'DEP-FARM', 'type' => 'production'],
                    ['name' => 'CA Cold Storage & Ripening', 'code' => 'DEP-COLD', 'type' => 'production'],
                    ['name' => 'Sorting Grading & Packing Line', 'code' => 'DEP-PACK', 'type' => 'quality'],
                    ['name' => 'Agro Fleets & Dispatch', 'code' => 'DEP-FLEET', 'type' => 'inventory'],
                    ['name' => 'Farm Accounting & APMC Sales', 'code' => 'DEP-ACC', 'type' => 'accounts'],
                ],
                'godowns' => [
                    ['name' => 'Shimla Controlled Atmosphere Chamber 1', 'code' => 'GDW-SHM-CA1', 'location_type' => 'warehouse', 'branch_code' => 'BR-SHM-01'],
                    ['name' => 'Organic Fertilizer & Compost Shed', 'code' => 'GDW-SHM-FERT', 'location_type' => 'store', 'branch_code' => 'BR-SHM-01'],
                    ['name' => 'Solan Cold Transit Depot', 'code' => 'GDW-SOL-DEPOT', 'location_type' => 'warehouse', 'branch_code' => 'BR-SOL-02'],
                ],
                'units' => [
                    ['name' => 'Kilogram', 'symbol' => 'KG', 'type' => 'weight'],
                    ['name' => 'Metric Ton', 'symbol' => 'TON', 'type' => 'weight'],
                    ['name' => 'Apple Shipper Box', 'symbol' => 'BOX', 'type' => 'quantity'],
                    ['name' => 'Plastic Crate', 'symbol' => 'CRT', 'type' => 'quantity'],
                    ['name' => 'Litre', 'symbol' => 'LTR', 'type' => 'volume'],
                ],
                'categories' => [
                    ['name' => 'Organic Soil Nutrition', 'description' => 'Compost and natural extract nutrients', 'subcategories' => ['Bio-Fertilizers', 'Neem Extracts']],
                    ['name' => 'Fresh Orchard Harvest', 'description' => 'Apples and stone fruits', 'subcategories' => ['Royal Delicious', 'Golden Delicious']],
                    ['name' => 'Cold-Pressed Juices', 'description' => 'Unfiltered pure farm juices', 'subcategories' => ['Pure Apple Juices']],
                ],
                'raw_materials' => [
                    ['name' => 'Certified Organic Cow Dung Compost', 'code' => 'RM-CMP-ORG', 'category' => 'Organic Soil Nutrition', 'subcategory' => 'Bio-Fertilizers', 'unit' => 'TON', 'price' => 4500, 'gst' => 5, 'hsn' => '3101'],
                    ['name' => 'Cold Pressed Pure Neem Oil Extract', 'code' => 'RM-NEEM-OIL', 'category' => 'Organic Soil Nutrition', 'subcategory' => 'Neem Extracts', 'unit' => 'LTR', 'price' => 340, 'gst' => 5, 'hsn' => '3808'],
                    ['name' => 'Telescopic Ventilated Apple Shipper Box', 'code' => 'RM-BOX-APPLE', 'category' => 'Organic Soil Nutrition', 'subcategory' => 'Bio-Fertilizers', 'unit' => 'BOX', 'price' => 45, 'gst' => 12, 'hsn' => '4819'],
                ],
                'products' => [
                    ['name' => 'Premium Royal Delicious Apples 10kg Box', 'code' => 'FG-APL-ROYAL', 'category' => 'Fresh Orchard Harvest', 'subcategory' => 'Royal Delicious', 'unit' => 'BOX', 'price' => 1450, 'gst' => 0, 'hsn' => '0808'],
                    ['name' => 'Export Grade Golden Delicious 10kg Box', 'code' => 'FG-APL-GOLD', 'category' => 'Fresh Orchard Harvest', 'subcategory' => 'Golden Delicious', 'unit' => 'BOX', 'price' => 1300, 'gst' => 0, 'hsn' => '0808'],
                    ['name' => 'Cold-Pressed Pure Unsweetened Apple Juice 1L', 'code' => 'FG-JCE-1L', 'category' => 'Cold-Pressed Juices', 'subcategory' => 'Pure Apple Juices', 'unit' => 'LTR', 'price' => 160, 'gst' => 12, 'hsn' => '2009'],
                ],
                'suppliers' => [
                    ['name' => 'Himalayan Bio-Organics Consortium', 'code' => 'SUP-HIM-01', 'contact_person' => 'Suraj Prakash', 'phone' => '+91 1792 225 100', 'email' => 'organics@himalayanbio.in', 'city' => 'Solan', 'gst' => '02AAACH1122K1Z8'],
                    ['name' => 'CorruKraft Agro Packaging Systems', 'code' => 'SUP-CRK-02', 'contact_person' => 'Manoj Bhardwaj', 'phone' => '+91 1795 244 800', 'email' => 'boxes@corrukraft.com', 'city' => 'Baddi', 'gst' => '02AAACC5566L1Z4'],
                    ['name' => 'ICAR Regional Fruit Research Seeds', 'code' => 'SUP-ICR-03', 'contact_person' => 'Dr. R. K. Dogra', 'phone' => '+91 177 283 0400', 'email' => 'horticulture@icar.gov.in', 'city' => 'Shimla', 'gst' => '02AAAGI9988M1Z0'],
                ],
                'customers' => [
                    ['name' => 'Nature\'s Basket Gourmet Chains', 'code' => 'CUS-NAT-01', 'shop_name' => 'Nature Basket Defence Colony', 'phone' => '+91 11 4160 8800', 'email' => 'fresh@naturesbasket.co.in', 'city' => 'New Delhi', 'gst' => '07AAACN1122D1Z2'],
                    ['name' => 'Reliance Retail Fresh Produce Division', 'code' => 'CUS-REL-02', 'shop_name' => 'Reliance Fresh Chandigarh', 'phone' => '+91 172 498 7700', 'email' => 'fruits@ril.com', 'city' => 'Chandigarh', 'gst' => '04AAACR5566E1Z6'],
                    ['name' => 'BigBasket Organic Direct Fulfillment', 'code' => 'CUS-BB-03', 'shop_name' => 'BigBasket Gurgaon Hub', 'phone' => '+91 124 492 8800', 'email' => 'organic@bigbasket.com', 'city' => 'Gurgaon', 'gst' => '06AAACB9988F1Z1'],
                    ['name' => 'Azadpur Wholesale Fruit Commission', 'code' => 'CUS-AZD-04', 'shop_name' => 'Shed 4 Azadpur Mandi', 'phone' => '+91 11 2767 3344', 'email' => 'azadpur@manditrader.in', 'city' => 'Delhi', 'gst' => '07AAACA4455G1Z5'],
                ],
                'staff' => [
                    ['first_name' => 'Kuldeep', 'last_name' => 'Thakur', 'employee_id' => 'STF-GA-01', 'designation' => 'Senior Orchard Agronomist', 'employee_type' => 'salaried', 'salary_amount' => 58000, 'basic_salary' => 45000, 'dept_code' => 'DEP-FARM', 'pf_applicable' => true],
                    ['first_name' => 'Manjeet', 'last_name' => 'Singh', 'employee_id' => 'STF-GA-02', 'designation' => 'Cold Chain Refrigeration Tech', 'employee_type' => 'hourly', 'hourly_rate' => 180, 'dept_code' => 'DEP-COLD', 'esi_applicable' => true],
                    ['first_name' => 'Rekha', 'last_name' => 'Sharma', 'employee_id' => 'STF-GA-03', 'designation' => 'Post-Harvest Quality Officer', 'employee_type' => 'contract', 'salary_amount' => 32000, 'basic_salary' => 32000, 'dept_code' => 'DEP-PACK'],
                    ['first_name' => 'Jagat', 'last_name' => 'Ram', 'employee_id' => 'STF-GA-04', 'designation' => 'Fruit Grading Specialist', 'employee_type' => 'piece_rate', 'piece_rate' => 15, 'dept_code' => 'DEP-PACK'],
                    ['first_name' => 'Birbal', 'last_name' => 'Ram', 'employee_id' => 'STF-GA-05', 'designation' => 'Orchard Irrigation Fieldman', 'employee_type' => 'daily_wage', 'salary_amount' => 800, 'dept_code' => 'DEP-FARM', 'esi_applicable' => true],
                ],
            ],

            // 8. Healthcare & Hospital
            [
                'short_name' => 'CarePlus',
                'domain' => 'careplusmed.com',
                'company' => [
                    'name' => 'CarePlus Medical Centre',
                    'code' => 'CAREPLUS-MED',
                    'registration_no' => 'U85110TN2020PTC156789',
                    'gst_no' => '33AAACC2233G1Z7',
                    'pan_no' => 'AAACC2233G',
                    'address' => 'No 42, Poonamallee High Road, Kilpauk, Chennai, Tamil Nadu 600010',
                    'phone' => '+91 44 2642 8899',
                    'email' => 'care@careplusmed.com',
                    'website' => 'https://careplusmed.example.com',
                    'business_type' => 'service',
                ],
                'bank' => ['name' => 'Indian Bank Kilpauk Branch', 'acc_no' => '50192837465', 'ifsc' => 'IDIB000K042'],
                'modules' => ['staff_hr', 'inventory', 'purchase', 'sales', 'accounts', 'quality'],
                'branches' => [
                    ['name' => 'Kilpauk Multi-Specialty Hospital', 'code' => 'BR-KIL-01', 'address' => 'Poonamallee High Road, Kilpauk', 'city' => 'Chennai', 'state' => 'Tamil Nadu', 'postal_code' => '600010', 'contact_number' => '+91 44 2642 8890', 'email' => 'kilpauk@careplusmed.com', 'is_main_branch' => true],
                    ['name' => 'Velachery Day Care & Dialysis Unit', 'code' => 'BR-VEL-02', 'address' => 'Velachery Main Road', 'city' => 'Chennai', 'state' => 'Tamil Nadu', 'postal_code' => '600042', 'contact_number' => '+91 44 2244 5566', 'email' => 'velachery@careplusmed.com'],
                    ['name' => 'Anna Nagar Specialty Polyclinic', 'code' => 'BR-ANN-03', 'address' => '2nd Avenue, Anna Nagar', 'city' => 'Chennai', 'state' => 'Tamil Nadu', 'postal_code' => '600040', 'contact_number' => '+91 44 2621 3344', 'email' => 'annanagar@careplusmed.com'],
                ],
                'departments' => [
                    ['name' => 'Inpatient & Outpatient Pharmacy', 'code' => 'DEP-PHARM', 'type' => 'sales'],
                    ['name' => 'Central Sterile Supply & OT Stores', 'code' => 'DEP-CSSD', 'type' => 'inventory'],
                    ['name' => 'Pathology & Biochemistry Lab', 'code' => 'DEP-LAB', 'type' => 'quality'],
                    ['name' => 'Patient Billing & Insurance TPA Desk', 'code' => 'DEP-BILL', 'type' => 'accounts'],
                    ['name' => 'Hospital Administration', 'code' => 'DEP-ADMIN', 'type' => 'hr'],
                ],
                'godowns' => [
                    ['name' => 'Central Hospital Pharmacy Vault', 'code' => 'GDW-KIL-PHARM', 'location_type' => 'store', 'branch_code' => 'BR-KIL-01'],
                    ['name' => 'OT Surgical Consumables Store', 'code' => 'GDW-KIL-SURG', 'location_type' => 'store', 'branch_code' => 'BR-KIL-01'],
                    ['name' => 'Vaccine Refrigerated Cold Store 2-8C', 'code' => 'GDW-KIL-COLD', 'location_type' => 'warehouse', 'branch_code' => 'BR-KIL-01'],
                ],
                'units' => [
                    ['name' => 'Strip of 10', 'symbol' => 'STRIP', 'type' => 'quantity'],
                    ['name' => 'Box', 'symbol' => 'BOX', 'type' => 'quantity'],
                    ['name' => 'Vial', 'symbol' => 'VIAL', 'type' => 'quantity'],
                    ['name' => 'Single Unit', 'symbol' => 'UNIT', 'type' => 'quantity'],
                    ['name' => 'Bottle of 500ml', 'symbol' => 'BTL', 'type' => 'quantity'],
                ],
                'categories' => [
                    ['name' => 'Essential Pharmaceuticals', 'description' => 'Prescription medicines and IV fluids', 'subcategories' => ['Antibiotics', 'IV Fluids']],
                    ['name' => 'Surgical Disposables & PPE', 'description' => 'Gloves, masks and syringes', 'subcategories' => ['Latex Gloves', 'Disposables']],
                    ['name' => 'Diagnostic Packages', 'description' => 'Preventive comprehensive checkups', 'subcategories' => ['Health Checkups']],
                ],
                'raw_materials' => [
                    ['name' => 'Amoxicillin + Clavulanic Acid 625mg Strips', 'code' => 'RM-MED-AMOX', 'category' => 'Essential Pharmaceuticals', 'subcategory' => 'Antibiotics', 'unit' => 'STRIP', 'price' => 120, 'gst' => 12, 'hsn' => '3004'],
                    ['name' => 'Normal Saline 0.9% IV Infusion 500ml', 'code' => 'RM-MED-NS500', 'category' => 'Essential Pharmaceuticals', 'subcategory' => 'IV Fluids', 'unit' => 'BTL', 'price' => 28, 'gst' => 12, 'hsn' => '3004'],
                    ['name' => 'Sterile Latex Surgical Gloves Size 7.5', 'code' => 'RM-SUR-GLV', 'category' => 'Surgical Disposables & PPE', 'subcategory' => 'Latex Gloves', 'unit' => 'BOX', 'price' => 480, 'gst' => 12, 'hsn' => '4015'],
                ],
                'products' => [
                    ['name' => 'Comprehensive Executive Health Package', 'code' => 'FG-PKG-EXEC', 'category' => 'Diagnostic Packages', 'subcategory' => 'Health Checkups', 'unit' => 'UNIT', 'price' => 3500, 'gst' => 0, 'hsn' => '9993'],
                    ['name' => 'Diabetic Comprehensive Wellness Screening', 'code' => 'FG-PKG-DIAB', 'category' => 'Diagnostic Packages', 'subcategory' => 'Health Checkups', 'unit' => 'UNIT', 'price' => 1800, 'gst' => 0, 'hsn' => '9993'],
                    ['name' => 'Inpatient Standard Post-Operative Kit', 'code' => 'FG-KIT-POSTOP', 'category' => 'Diagnostic Packages', 'subcategory' => 'Health Checkups', 'unit' => 'UNIT', 'price' => 1250, 'gst' => 12, 'hsn' => '3005'],
                ],
                'suppliers' => [
                    ['name' => 'Sun Pharma Laboratories Ltd.', 'code' => 'SUP-SUN-01', 'contact_person' => 'K. Ramanathan', 'phone' => '+91 44 2829 4455', 'email' => 'hospital@sunpharma.com', 'city' => 'Chennai', 'gst' => '33AAACS1122D1Z4'],
                    ['name' => 'Cipla Medical Specialties', 'code' => 'SUP-CIP-02', 'contact_person' => 'V. Natarajan', 'phone' => '+91 44 2498 7700', 'email' => 'sales.chennai@cipla.com', 'city' => 'Chennai', 'gst' => '33AAACC5566K1Z8'],
                    ['name' => 'Medline Disposables India', 'code' => 'SUP-MED-03', 'contact_person' => 'S. Karthikeyan', 'phone' => '+91 44 2618 1122', 'email' => 'surgicals@medline.in', 'city' => 'Chennai', 'gst' => '33AAACM9988L1Z2'],
                ],
                'customers' => [
                    ['name' => 'Star Health Insurance TPA Patients', 'code' => 'CUS-STR-01', 'shop_name' => 'Star Health TPA Desk', 'phone' => '+91 44 2828 8800', 'email' => 'claims@starhealth.in', 'city' => 'Chennai', 'gst' => '33AAACS9988M1Z6'],
                    ['name' => 'Chennai Port Trust Employee Panel', 'code' => 'CUS-CPT-02', 'shop_name' => 'Port Trust Medical Dept', 'phone' => '+91 44 2536 2201', 'email' => 'cmo@chennaiport.gov.in', 'city' => 'Chennai', 'gst' => '33AAATC1122N1Z0'],
                    ['name' => 'MedGenome Referral Diagnostics', 'code' => 'CUS-MGD-03', 'shop_name' => 'MedGenome Diagnostic Labs', 'phone' => '+91 44 4987 6600', 'email' => 'labreferrals@medgenome.com', 'city' => 'Chennai', 'gst' => '33AAACM4455P1Z4'],
                    ['name' => 'Walk-in Inpatient Family Accounts', 'code' => 'CUS-WLK-04', 'shop_name' => 'Kilpauk Admissions Desk', 'phone' => '+91 44 2642 8891', 'email' => 'admissions@careplusmed.com', 'city' => 'Chennai', 'gst' => null],
                ],
                'staff' => [
                    ['first_name' => 'Dr. S.', 'last_name' => 'Annamalai', 'employee_id' => 'STF-CP-01', 'designation' => 'Chief Medical Superintendent', 'employee_type' => 'salaried', 'salary_amount' => 95000, 'basic_salary' => 80000, 'dept_code' => 'DEP-ADMIN', 'pf_applicable' => true],
                    ['first_name' => 'Selvamani', 'last_name' => 'P', 'employee_id' => 'STF-CP-02', 'designation' => 'Senior Radiography Technician', 'employee_type' => 'hourly', 'hourly_rate' => 260, 'dept_code' => 'DEP-LAB', 'esi_applicable' => true],
                    ['first_name' => 'K.', 'last_name' => 'Priya', 'employee_id' => 'STF-CP-03', 'designation' => 'ICU Senior Staff Nurse', 'employee_type' => 'contract', 'salary_amount' => 38000, 'basic_salary' => 38000, 'dept_code' => 'DEP-ADMIN'],
                    ['first_name' => 'R.', 'last_name' => 'Murugesan', 'employee_id' => 'STF-CP-04', 'designation' => 'CSSD Instrument Packager', 'employee_type' => 'piece_rate', 'piece_rate' => 20, 'dept_code' => 'DEP-CSSD'],
                    ['first_name' => 'Ganesan', 'last_name' => 'M', 'employee_id' => 'STF-CP-05', 'designation' => 'Hospital Ward Care Attendant', 'employee_type' => 'daily_wage', 'salary_amount' => 850, 'dept_code' => 'DEP-ADMIN', 'esi_applicable' => true],
                ],
            ],

            // 9. Printing & Packaging
            [
                'short_name' => 'PrintPro',
                'domain' => 'printpropack.com',
                'company' => [
                    'name' => 'PrintPro Packaging Solutions',
                    'code' => 'PRINTPRO-PACK',
                    'registration_no' => 'U21020TN2020PTC167890',
                    'gst_no' => '33AAACP6677H1Z5',
                    'pan_no' => 'AAACP6677H',
                    'address' => 'Plot 55, Industrial Estate, Sivakasi, Tamil Nadu 626123',
                    'phone' => '+91 4562 278 900',
                    'email' => 'orders@printpropack.com',
                    'website' => 'https://printpropack.example.com',
                    'business_type' => 'manufacturing',
                ],
                'bank' => ['name' => 'Tamilnad Mercantile Bank', 'acc_no' => '10020050098471', 'ifsc' => 'TMBL0000100'],
                'modules' => ['staff_hr', 'inventory', 'purchase', 'sales', 'accounts', 'production', 'quality'],
                'branches' => [
                    ['name' => 'Sivakasi Master Printing Complex', 'code' => 'BR-SIV-01', 'address' => 'Sivakasi Industrial Estate', 'city' => 'Sivakasi', 'state' => 'Tamil Nadu', 'postal_code' => '626123', 'contact_number' => '+91 4562 278 901', 'email' => 'press@printpropack.com', 'is_main_branch' => true],
                    ['name' => 'Coimbatore Packaging Distribution Hub', 'code' => 'BR-CBE-02', 'address' => 'Peelamedu Industrial Area, Coimbatore', 'city' => 'Coimbatore', 'state' => 'Tamil Nadu', 'postal_code' => '641004', 'contact_number' => '+91 422 257 8899', 'email' => 'coimbatore@printpropack.com'],
                    ['name' => 'Madurai Client Service Centre', 'code' => 'BR-MDU-03', 'address' => 'KK Nagar Main Road, Madurai', 'city' => 'Madurai', 'state' => 'Tamil Nadu', 'postal_code' => '625020', 'contact_number' => '+91 452 258 4400', 'email' => 'madurai@printpropack.com'],
                ],
                'departments' => [
                    ['name' => 'Pre-Press & CTP Plate Imaging', 'code' => 'DEP-PRE', 'type' => 'production'],
                    ['name' => 'Offset & Flexographic Printing', 'code' => 'DEP-PRINT', 'type' => 'production'],
                    ['name' => 'Die-Punching & Automatic Folder-Gluer', 'code' => 'DEP-POST', 'type' => 'production'],
                    ['name' => 'Paper Reel & Duplex Board Yard', 'code' => 'DEP-PAPER', 'type' => 'inventory'],
                    ['name' => 'Commercial Accounts & Invoicing', 'code' => 'DEP-ACC', 'type' => 'accounts'],
                ],
                'godowns' => [
                    ['name' => 'Virgin Kraft Reel & Board Store', 'code' => 'GDW-SIV-REEL', 'location_type' => 'warehouse', 'branch_code' => 'BR-SIV-01'],
                    ['name' => 'Printing Inks & Varnish Room', 'code' => 'GDW-SIV-INK', 'location_type' => 'store', 'branch_code' => 'BR-SIV-01'],
                    ['name' => 'Finished Carton Shippers Godown', 'code' => 'GDW-SIV-FG', 'location_type' => 'store', 'branch_code' => 'BR-SIV-01'],
                ],
                'units' => [
                    ['name' => 'Reel', 'symbol' => 'REEL', 'type' => 'other'],
                    ['name' => 'Metric Ton', 'symbol' => 'TON', 'type' => 'weight'],
                    ['name' => 'Thousand Pieces', 'symbol' => 'THOU', 'type' => 'quantity'],
                    ['name' => 'Printed Sheet', 'symbol' => 'SHT', 'type' => 'quantity'],
                    ['name' => 'Single Piece', 'symbol' => 'PCS', 'type' => 'quantity'],
                ],
                'categories' => [
                    ['name' => 'Paper & Duplex Board', 'description' => 'Kraft reels and white back duplex boards', 'subcategories' => ['Kraft Paper', 'Duplex Board']],
                    ['name' => 'Inks & Coatings', 'description' => 'Process cyan, magenta, yellow and black inks', 'subcategories' => ['Offset Inks', 'Varnishes']],
                    ['name' => 'Finished Cartons & Shippers', 'description' => 'Pharma boxes and 5-ply corrugated shippers', 'subcategories' => ['Corrugated Shippers', 'Folding Cartons']],
                ],
                'raw_materials' => [
                    ['name' => 'Virgin Kraft Liner Paper Reel 180 GSM', 'code' => 'RM-PPR-KRAFT', 'category' => 'Paper & Duplex Board', 'subcategory' => 'Kraft Paper', 'unit' => 'TON', 'price' => 42000, 'gst' => 12, 'hsn' => '4804'],
                    ['name' => 'White Back Duplex Board 350 GSM', 'code' => 'RM-PPR-DUPLEX', 'category' => 'Paper & Duplex Board', 'subcategory' => 'Duplex Board', 'unit' => 'TON', 'price' => 54000, 'gst' => 12, 'hsn' => '4810'],
                    ['name' => 'Toyo Process Cyan Offset Printing Ink 2.5kg', 'code' => 'RM-INK-CYAN', 'category' => 'Inks & Coatings', 'subcategory' => 'Offset Inks', 'unit' => 'PCS', 'price' => 850, 'gst' => 18, 'hsn' => '3215'],
                ],
                'products' => [
                    ['name' => 'Heavy Duty 5-Ply Master Shipping Carton', 'code' => 'FG-BOX-5PLY', 'category' => 'Finished Cartons & Shippers', 'subcategory' => 'Corrugated Shippers', 'unit' => 'PCS', 'price' => 55, 'gst' => 18, 'hsn' => '4819'],
                    ['name' => 'Embossed Foil-Stamped Pharma Mono-Carton', 'code' => 'FG-CTN-PHARMA', 'category' => 'Finished Cartons & Shippers', 'subcategory' => 'Folding Cartons', 'unit' => 'THOU', 'price' => 1450, 'gst' => 18, 'hsn' => '4819'],
                    ['name' => 'Custom Printed Food-Grade Sweet Box 1kg', 'code' => 'FG-BOX-SWEET', 'category' => 'Finished Cartons & Shippers', 'subcategory' => 'Folding Cartons', 'unit' => 'THOU', 'price' => 3800, 'gst' => 18, 'hsn' => '4819'],
                ],
                'suppliers' => [
                    ['name' => 'ITC Paperboards & Specialty Papers', 'code' => 'SUP-ITC-01', 'contact_person' => 'B. Sridhar', 'phone' => '+91 40 2784 8800', 'email' => 'paperboards@itc.in', 'city' => 'Secunderabad', 'gst' => '36AAACI1122D1Z6'],
                    ['name' => 'DIC India Printing Inks Ltd.', 'code' => 'SUP-DIC-02', 'contact_person' => 'G. Murali', 'phone' => '+91 44 2625 4400', 'email' => 'inks@dic.co.in', 'city' => 'Chennai', 'gst' => '33AAACD5566E1Z0'],
                    ['name' => 'Bohler Die Steel Technologies', 'code' => 'SUP-BOH-03', 'contact_person' => 'Ajay Verma', 'phone' => '+91 22 2850 7799', 'email' => 'dies@bohlerindia.com', 'city' => 'Mumbai', 'gst' => '27AAACB9988F1Z4'],
                ],
                'customers' => [
                    ['name' => 'Himalaya Wellness Packaging Division', 'code' => 'CUS-HIM-01', 'shop_name' => 'Himalaya Makali Depot', 'phone' => '+91 80 6754 9900', 'email' => 'packaging@himalayawellness.com', 'city' => 'Bangalore', 'gst' => '29AAACH1122G1Z8'],
                    ['name' => 'Britannia Industries Shippers Dept', 'code' => 'CUS-BRT-02', 'shop_name' => 'Britannia Padi Plant', 'phone' => '+91 44 2625 3300', 'email' => 'procurement@britindia.com', 'city' => 'Chennai', 'gst' => '33AAACB5566H1Z2'],
                    ['name' => 'Titan Watches Luxury Box Procurement', 'code' => 'CUS-TTN-03', 'shop_name' => 'Titan Hosur Works', 'phone' => '+91 4344 664 400', 'email' => 'luxuryboxes@titan.co.in', 'city' => 'Hosur', 'gst' => '33AAACT9988J1Z7'],
                    ['name' => 'Standard Fireworks Master Packaging', 'code' => 'CUS-STD-04', 'shop_name' => 'Standard Works Sivakasi', 'phone' => '+91 4562 220 399', 'email' => 'boxes@standardfireworks.com', 'city' => 'Sivakasi', 'gst' => '33AAACS4455K1Z1'],
                ],
                'staff' => [
                    ['first_name' => 'K.', 'last_name' => 'Marimuthu', 'employee_id' => 'STF-PP-01', 'designation' => 'Printing Plant Superintendent', 'employee_type' => 'salaried', 'salary_amount' => 60000, 'basic_salary' => 48000, 'dept_code' => 'DEP-PRINT', 'pf_applicable' => true],
                    ['first_name' => 'Arumugam', 'last_name' => 'S', 'employee_id' => 'STF-PP-02', 'designation' => 'Komori 4-Colour Offset Pressman', 'employee_type' => 'hourly', 'hourly_rate' => 210, 'dept_code' => 'DEP-PRINT', 'esi_applicable' => true],
                    ['first_name' => 'S.', 'last_name' => 'Meenakshi', 'employee_id' => 'STF-PP-03', 'designation' => 'Pre-press CTP Graphic Designer', 'employee_type' => 'contract', 'salary_amount' => 33000, 'basic_salary' => 33000, 'dept_code' => 'DEP-PRE'],
                    ['first_name' => 'Karthik', 'last_name' => 'T', 'employee_id' => 'STF-PP-04', 'designation' => 'Folder Gluer Machine Operator', 'employee_type' => 'piece_rate', 'piece_rate' => 18, 'dept_code' => 'DEP-POST'],
                    ['first_name' => 'Palani', 'last_name' => 'M', 'employee_id' => 'STF-PP-05', 'designation' => 'Paper Reel Offloader', 'employee_type' => 'daily_wage', 'salary_amount' => 850, 'dept_code' => 'DEP-PAPER', 'esi_applicable' => true],
                ],
            ],

            // 10. Wholesale & Distribution
            [
                'short_name' => 'PrimeTrade',
                'domain' => 'primetradedist.com',
                'company' => [
                    'name' => 'PrimeTrade Wholesale Distributors',
                    'code' => 'PRIMETRADE-DIST',
                    'registration_no' => 'U51909MH2020PTC178923',
                    'gst_no' => '27AAACP9900J1Z3',
                    'pan_no' => 'AAACP9900J',
                    'address' => 'Bldg A4, Indian Corporation Logistics Park, Bhiwandi, Thane, Maharashtra 421302',
                    'phone' => '+91 2522 667 800',
                    'email' => 'wholesale@primetradedist.com',
                    'website' => 'https://primetradedist.example.com',
                    'business_type' => 'wholesale',
                ],
                'bank' => ['name' => 'Kotak Mahindra Bank Bhiwandi', 'acc_no' => '8920192837', 'ifsc' => 'KKBK0000672'],
                'modules' => ['staff_hr', 'inventory', 'purchase', 'sales', 'accounts', 'vehicle_route', 'crm'],
                'branches' => [
                    ['name' => 'Bhiwandi Central Logistics Mega-Hub', 'code' => 'BR-BHI-01', 'address' => 'Indian Corporation Logistics Park, Mankoli, Bhiwandi', 'city' => 'Thane', 'state' => 'Maharashtra', 'postal_code' => '421302', 'contact_number' => '+91 2522 667 801', 'email' => 'hub@primetradedist.com', 'is_main_branch' => true],
                    ['name' => 'Vashi APMC Wholesale Depot', 'code' => 'BR-VAS-02', 'address' => 'Sector 19, APMC Market 2, Vashi', 'city' => 'Navi Mumbai', 'state' => 'Maharashtra', 'postal_code' => '400703', 'contact_number' => '+91 22 2789 5566', 'email' => 'vashi@primetradedist.com'],
                    ['name' => 'Andheri North City Express Point', 'code' => 'BR-AND-03', 'address' => 'Saki Naka, Andheri East', 'city' => 'Mumbai', 'state' => 'Maharashtra', 'postal_code' => '400072', 'contact_number' => '+91 22 2857 1144', 'email' => 'andheri@primetradedist.com'],
                ],
                'departments' => [
                    ['name' => 'Inbound Receiving & High-Bay Stacking', 'code' => 'DEP-IN', 'type' => 'inventory'],
                    ['name' => 'Wholesale B2B Sales & Key Accounts', 'code' => 'DEP-SALES', 'type' => 'sales'],
                    ['name' => 'Outbound Dispatch & Fleet Routing', 'code' => 'DEP-OUT', 'type' => 'inventory'],
                    ['name' => 'Retailer Credit Control & Accounts', 'code' => 'DEP-ACC', 'type' => 'accounts'],
                    ['name' => 'Customer Care & Returns Desk', 'code' => 'DEP-CRM', 'type' => 'crm'],
                ],
                'godowns' => [
                    ['name' => 'Pallet High-Bay FMCG Godown', 'code' => 'GDW-BHI-HIGH', 'location_type' => 'warehouse', 'branch_code' => 'BR-BHI-01'],
                    ['name' => 'Bulk Cooking Oil & Grains Yard', 'code' => 'GDW-BHI-BULK', 'location_type' => 'warehouse', 'branch_code' => 'BR-BHI-01'],
                    ['name' => 'Vashi APMC Fast-Fulfillment Depot', 'code' => 'GDW-VAS-APMC', 'location_type' => 'store', 'branch_code' => 'BR-VAS-02'],
                ],
                'units' => [
                    ['name' => 'Case', 'symbol' => 'CASE', 'type' => 'quantity'],
                    ['name' => 'Master Carton', 'symbol' => 'CTN', 'type' => 'quantity'],
                    ['name' => 'Dozen', 'symbol' => 'DOZ', 'type' => 'quantity'],
                    ['name' => 'Consumer Pack', 'symbol' => 'PACK', 'type' => 'quantity'],
                    ['name' => 'Kilogram', 'symbol' => 'KG', 'type' => 'weight'],
                ],
                'categories' => [
                    ['name' => 'Packaged Staples & Oils', 'description' => 'Cooking oils and bulk flours', 'subcategories' => ['Edible Oils', 'Flour & Grains']],
                    ['name' => 'FMCG Personal Care', 'description' => 'Soaps, antiseptics and cleaners', 'subcategories' => ['Antiseptics', 'Dishwash']],
                    ['name' => 'Packaged Snacks & Biscuits', 'description' => 'Everyday cookies and snacks', 'subcategories' => ['Biscuits']],
                ],
                'raw_materials' => [
                    ['name' => 'Fortune Refined Sunflower Oil 1L (Carton of 15)', 'code' => 'RM-OIL-FORT15', 'category' => 'Packaged Staples & Oils', 'subcategory' => 'Edible Oils', 'unit' => 'CTN', 'price' => 1850, 'gst' => 5, 'hsn' => '1512'],
                    ['name' => 'Parle-G Glucose Biscuits Master Carton of 96', 'code' => 'RM-BSC-PARLE', 'category' => 'Packaged Snacks & Biscuits', 'subcategory' => 'Biscuits', 'unit' => 'CTN', 'price' => 920, 'gst' => 18, 'hsn' => '1905'],
                    ['name' => 'Dettol Antiseptic Liquid 500ml Case of 24', 'code' => 'RM-DET-500ML', 'category' => 'FMCG Personal Care', 'subcategory' => 'Antiseptics', 'unit' => 'CASE', 'price' => 3100, 'gst' => 18, 'hsn' => '3004'],
                ],
                'products' => [
                    ['name' => 'Kirana Super Saver Monthly Wholesale Combo', 'code' => 'FG-CMB-KIRANA', 'category' => 'Packaged Staples & Oils', 'subcategory' => 'Edible Oils', 'unit' => 'PACK', 'price' => 12500, 'gst' => 12, 'hsn' => '2106'],
                    ['name' => 'Modern Retail Fast-Moving Snacks Bundle', 'code' => 'FG-BND-SNACKS', 'category' => 'Packaged Snacks & Biscuits', 'subcategory' => 'Biscuits', 'unit' => 'PACK', 'price' => 8200, 'gst' => 18, 'hsn' => '1905'],
                    ['name' => 'Institutional Canteen Hygiene Starter Kit', 'code' => 'FG-KIT-HYG01', 'category' => 'FMCG Personal Care', 'subcategory' => 'Antiseptics', 'unit' => 'PACK', 'price' => 6400, 'gst' => 18, 'hsn' => '3402'],
                ],
                'suppliers' => [
                    ['name' => 'Hindustan Unilever Limited C&F', 'code' => 'SUP-HUL-01', 'contact_person' => 'R. Subramaniam', 'phone' => '+91 22 3983 0000', 'email' => 'orders@hul.com', 'city' => 'Mumbai', 'gst' => '27AAACH1122D1Z5'],
                    ['name' => 'Britannia Industries National Distribution', 'code' => 'SUP-BRT-02', 'contact_person' => 'Deepak Shenoy', 'phone' => '+91 22 2589 7700', 'email' => 'wholesale@britannia.co.in', 'city' => 'Thane', 'gst' => '27AAACB5566K1Z9'],
                    ['name' => 'Adani Wilmar Edible Oils Depo', 'code' => 'SUP-AWL-03', 'contact_person' => 'Viren Shah', 'phone' => '+91 22 2788 4400', 'email' => 'edibleoils@adaniwilmar.in', 'city' => 'Navi Mumbai', 'gst' => '27AAACA9988L1Z3'],
                ],
                'customers' => [
                    ['name' => 'D-Mart Avenue Supermarts Hub', 'code' => 'CUS-DMT-01', 'shop_name' => 'D-Mart Central DC Thane', 'phone' => '+91 22 4112 8800', 'email' => 'procurement@dmartindia.com', 'city' => 'Thane', 'gst' => '27AAACA1122M1Z7'],
                    ['name' => 'Mumbai Suburban Kirana Merchants Union', 'code' => 'CUS-KIR-02', 'shop_name' => 'Kirana Bazaar Kurla', 'phone' => '+91 22 2514 6677', 'email' => 'union@mumbaikirana.org', 'city' => 'Mumbai', 'gst' => '27AAACM5566N1Z1'],
                    ['name' => 'Reliable Provision Stores Vashi', 'code' => 'CUS-REL-03', 'shop_name' => 'Reliable Superstore Sector 17', 'phone' => '+91 22 2789 1122', 'email' => 'orders@reliablestores.in', 'city' => 'Navi Mumbai', 'gst' => '27AAACR9988P1Z5'],
                    ['name' => 'Ghatkopar Traders Association', 'code' => 'CUS-GHT-04', 'shop_name' => 'Ghatkopar Wholesale Mart', 'phone' => '+91 22 2501 3344', 'email' => 'traders@ghatkopar.in', 'city' => 'Mumbai', 'gst' => '27AAACG4455Q1Z9'],
                ],
                'staff' => [
                    ['first_name' => 'Harish', 'last_name' => 'Salve', 'employee_id' => 'STF-PT-01', 'designation' => 'Wholesale Operations Director', 'employee_type' => 'salaried', 'salary_amount' => 65000, 'basic_salary' => 50000, 'dept_code' => 'DEP-IN', 'pf_applicable' => true],
                    ['first_name' => 'Santosh', 'last_name' => 'Patil', 'employee_id' => 'STF-PT-02', 'designation' => 'Reach Truck Forklift Driver', 'employee_type' => 'hourly', 'hourly_rate' => 190, 'dept_code' => 'DEP-IN', 'esi_applicable' => true],
                    ['first_name' => 'Nilesh', 'last_name' => 'Jadhav', 'employee_id' => 'STF-PT-03', 'designation' => 'Institutional Key Account Mgr', 'employee_type' => 'contract', 'salary_amount' => 38000, 'basic_salary' => 38000, 'dept_code' => 'DEP-SALES'],
                    ['first_name' => 'Eknath', 'last_name' => 'More', 'employee_id' => 'STF-PT-04', 'designation' => 'Night Shift Order Case Picker', 'employee_type' => 'piece_rate', 'piece_rate' => 5, 'dept_code' => 'DEP-OUT'],
                    ['first_name' => 'Tukaram', 'last_name' => 'Shinde', 'employee_id' => 'STF-PT-05', 'designation' => 'Loading Dock Labourer', 'employee_type' => 'daily_wage', 'salary_amount' => 850, 'dept_code' => 'DEP-OUT', 'esi_applicable' => true],
                ],
            ],

            // 11. Personal Care Manufacturing
            [
                'short_name' => 'GlowCare',
                'domain' => 'glowcarepers.com',
                'company' => [
                    'name' => 'GlowCare Personal Products',
                    'code' => 'GLOWCARE-PERS',
                    'registration_no' => 'U24246HP2020PTC189012',
                    'gst_no' => '02AAACG7788K1Z3',
                    'pan_no' => 'AAACG7788K',
                    'address' => 'Plot 102, EPIP Phase I, Jharmajri, Baddi, Himachal Pradesh 174103',
                    'phone' => '+91 1795 244 556',
                    'email' => 'hello@glowcarepers.com',
                    'website' => 'https://glowcarepers.example.com',
                    'business_type' => 'manufacturing',
                ],
                'bank' => ['name' => 'HDFC Bank Baddi Industrial Area', 'acc_no' => '50200059182736', 'ifsc' => 'HDFC0001082'],
                'modules' => ['staff_hr', 'inventory', 'purchase', 'sales', 'accounts', 'production', 'quality', 'crm'],
                'branches' => [
                    ['name' => 'Baddi Cosmetic Formulation Plant', 'code' => 'BR-BAD-01', 'address' => 'EPIP Phase I, Jharmajri', 'city' => 'Baddi', 'state' => 'Himachal Pradesh', 'postal_code' => '174103', 'contact_number' => '+91 1795 244 557', 'email' => 'plant@glowcarepers.com', 'is_main_branch' => true],
                    ['name' => 'Chandigarh R&D & Formulation Lab', 'code' => 'BR-CHD-02', 'address' => 'IT Park, Phase 1', 'city' => 'Chandigarh', 'state' => 'Chandigarh', 'postal_code' => '160101', 'contact_number' => '+91 172 490 8800', 'email' => 'lab@glowcarepers.com'],
                    ['name' => 'Delhi NCR Central E-Commerce Depot', 'code' => 'BR-NCR-03', 'address' => 'Pace City 1, Sector 37', 'city' => 'Gurugram', 'state' => 'Haryana', 'postal_code' => '122001', 'contact_number' => '+91 124 432 9900', 'email' => 'fulfillment@glowcarepers.com'],
                ],
                'departments' => [
                    ['name' => 'Emulsification & Bulk Chemical Blending', 'code' => 'DEP-BLEND', 'type' => 'production'],
                    ['name' => 'Automated Filling & Shrink Capping', 'code' => 'DEP-FILL', 'type' => 'production'],
                    ['name' => 'Microbiology & Safety Testing', 'code' => 'DEP-MICRO', 'type' => 'quality'],
                    ['name' => 'Packaging Glass & Tube Store', 'code' => 'DEP-PACK', 'type' => 'inventory'],
                    ['name' => 'Finance & D2C Sales Accounting', 'code' => 'DEP-ACC', 'type' => 'accounts'],
                ],
                'godowns' => [
                    ['name' => 'Active Botanical Extract Vault', 'code' => 'GDW-BAD-BOT', 'location_type' => 'warehouse', 'branch_code' => 'BR-BAD-01'],
                    ['name' => 'Cosmetic Bottles & Droppers Store', 'code' => 'GDW-BAD-GLASS', 'location_type' => 'store', 'branch_code' => 'BR-BAD-01'],
                    ['name' => 'Finished Skincare Finished Goods Store', 'code' => 'GDW-BAD-FG', 'location_type' => 'store', 'branch_code' => 'BR-BAD-01'],
                ],
                'units' => [
                    ['name' => 'Kilogram', 'symbol' => 'KG', 'type' => 'weight'],
                    ['name' => 'Gram', 'symbol' => 'GM', 'type' => 'weight'],
                    ['name' => 'Litre', 'symbol' => 'LTR', 'type' => 'volume'],
                    ['name' => 'Dropper Bottle', 'symbol' => 'BTL', 'type' => 'quantity'],
                    ['name' => 'Squeeze Tube', 'symbol' => 'TUBE', 'type' => 'quantity'],
                    ['name' => 'Piece', 'symbol' => 'PCS', 'type' => 'quantity'],
                ],
                'categories' => [
                    ['name' => 'Cosmetic Actives & Extracts', 'description' => 'Niacinamide, argan oil and ceramides', 'subcategories' => ['Pure Actives', 'Botanical Oils']],
                    ['name' => 'Primary Cosmetic Packaging', 'description' => 'Dropper bottles and aluminum tubes', 'subcategories' => ['Glass Droppers', 'Tubes']],
                    ['name' => 'Dermatological Skincare', 'description' => 'Finished serums, creams and treatments', 'subcategories' => ['Face Serums', 'Moisturizers']],
                ],
                'raw_materials' => [
                    ['name' => 'Pure Niacinamide Powder 99% USP', 'code' => 'RM-ACT-NIAC', 'category' => 'Cosmetic Actives & Extracts', 'subcategory' => 'Pure Actives', 'unit' => 'KG', 'price' => 1850, 'gst' => 18, 'hsn' => '2936'],
                    ['name' => 'Moroccan Argan Oil Cold Pressed Pure', 'code' => 'RM-OIL-ARGAN', 'category' => 'Cosmetic Actives & Extracts', 'subcategory' => 'Botanical Oils', 'unit' => 'LTR', 'price' => 2400, 'gst' => 18, 'hsn' => '3301'],
                    ['name' => 'Amber Dropper Glass Bottle 30ml with Pipette', 'code' => 'RM-BOT-AMB30', 'category' => 'Primary Cosmetic Packaging', 'subcategory' => 'Glass Droppers', 'unit' => 'PCS', 'price' => 16.5, 'gst' => 18, 'hsn' => '7010'],
                ],
                'products' => [
                    ['name' => 'GlowCare 10% Niacinamide + Zinc Serum 30ml', 'code' => 'FG-SRM-NIAC30', 'category' => 'Dermatological Skincare', 'subcategory' => 'Face Serums', 'unit' => 'BTL', 'price' => 499, 'gst' => 18, 'hsn' => '3304'],
                    ['name' => 'GlowCare Ceramide Deep Moisture Cream 50g', 'code' => 'FG-CRM-CER50', 'category' => 'Dermatological Skincare', 'subcategory' => 'Moisturizers', 'unit' => 'TUBE', 'price' => 450, 'gst' => 18, 'hsn' => '3304'],
                    ['name' => 'GlowCare Moroccan Argan Hair Therapy 100ml', 'code' => 'FG-OIL-ARG100', 'category' => 'Dermatological Skincare', 'subcategory' => 'Face Serums', 'unit' => 'BTL', 'price' => 650, 'gst' => 18, 'hsn' => '3305'],
                ],
                'suppliers' => [
                    ['name' => 'BASF Personal Care Chemicals India', 'code' => 'SUP-BASF-01', 'contact_person' => 'Dr. Rajiv Menon', 'phone' => '+91 22 2858 0000', 'email' => 'personalcare@basf.com', 'city' => 'Mumbai', 'gst' => '27AAACB1122D1Z7'],
                    ['name' => 'Piramal Glass Technologies Ltd.', 'code' => 'SUP-PIR-02', 'contact_person' => 'Alok Parekh', 'phone' => '+91 265 264 2200', 'email' => 'bottles@piramal.com', 'city' => 'Vadodara', 'gst' => '24AAACP5566E1Z1'],
                    ['name' => 'Croda Chemicals India Specialties', 'code' => 'SUP-CRO-03', 'contact_person' => 'Anand Singhal', 'phone' => '+91 22 2778 7700', 'email' => 'botanicals@croda.com', 'city' => 'Navi Mumbai', 'gst' => '27AAACC9988F1Z5'],
                ],
                'customers' => [
                    ['name' => 'Nykaa E-Retail Private Ltd. Hub', 'code' => 'CUS-NYK-01', 'shop_name' => 'Nykaa Fulfillment Center Bhiwandi', 'phone' => '+91 22 6614 5500', 'email' => 'brands@nykaa.com', 'city' => 'Mumbai', 'gst' => '27AAACN1122G1Z3'],
                    ['name' => 'Tira Beauty Luxury Stores', 'code' => 'CUS-TIR-02', 'shop_name' => 'Tira Retail Aerocity', 'phone' => '+91 11 4987 8899', 'email' => 'procurement@tirabeauty.com', 'city' => 'New Delhi', 'gst' => '07AAACT5566H1Z7'],
                    ['name' => 'Apollo Pharmacy Derma Outlets', 'code' => 'CUS-APO-03', 'shop_name' => 'Apollo Derma Zone Sector 17', 'phone' => '+91 172 270 4455', 'email' => 'cosmetics@apollopharmacy.org', 'city' => 'Chandigarh', 'gst' => '04AAACA9988J1Z1'],
                    ['name' => 'Sephora India Retail Counters', 'code' => 'CUS-SEP-04', 'shop_name' => 'Sephora Ambience Mall', 'phone' => '+91 124 466 5500', 'email' => 'merchandising@sephora.in', 'city' => 'Gurgaon', 'gst' => '06AAACS4455K1Z5'],
                ],
                'staff' => [
                    ['first_name' => 'Dr. Nivedita', 'last_name' => 'Sen', 'employee_id' => 'STF-GC-01', 'designation' => 'Chief Cosmetic Formulation Scientist', 'employee_type' => 'salaried', 'salary_amount' => 80000, 'basic_salary' => 65000, 'dept_code' => 'DEP-BLEND', 'pf_applicable' => true],
                    ['first_name' => 'Vikram', 'last_name' => 'Thakur', 'employee_id' => 'STF-GC-02', 'designation' => 'High-Shear Mixer Operator', 'employee_type' => 'hourly', 'hourly_rate' => 195, 'dept_code' => 'DEP-BLEND', 'esi_applicable' => true],
                    ['first_name' => 'Simran', 'last_name' => 'Bhatia', 'employee_id' => 'STF-GC-03', 'designation' => 'Microbiology Quality Officer', 'employee_type' => 'contract', 'salary_amount' => 36000, 'basic_salary' => 36000, 'dept_code' => 'DEP-MICRO'],
                    ['first_name' => 'Anita', 'last_name' => 'Devi', 'employee_id' => 'STF-GC-04', 'designation' => 'Serum Dropper Assembly Worker', 'employee_type' => 'piece_rate', 'piece_rate' => 2.5, 'dept_code' => 'DEP-FILL'],
                    ['first_name' => 'Prem', 'last_name' => 'Chand', 'employee_id' => 'STF-GC-05', 'designation' => 'Raw Material Unloader', 'employee_type' => 'daily_wage', 'salary_amount' => 850, 'dept_code' => 'DEP-PACK', 'esi_applicable' => true],
                ],
            ],

            // 12. Jewellery & Bullion
            [
                'short_name' => 'Heritage Gold',
                'domain' => 'heritagegold.com',
                'company' => [
                    'name' => 'Heritage Gold & Diamonds',
                    'code' => 'HERITAGE-GOLD',
                    'registration_no' => 'U36911KL2020PTC190123',
                    'gst_no' => '32AAACH8899L1Z1',
                    'pan_no' => 'AAACH8899L',
                    'address' => 'Heritage Towers, Round South, Thrissur, Kerala 680001',
                    'phone' => '+91 487 242 9900',
                    'email' => 'concierge@heritagegold.com',
                    'website' => 'https://heritagegold.example.com',
                    'business_type' => 'retail',
                ],
                'bank' => ['name' => 'South Indian Bank Round South Branch', 'acc_no' => '0084053000984712', 'ifsc' => 'SIBL0000084'],
                'modules' => ['staff_hr', 'inventory', 'purchase', 'sales', 'accounts', 'production', 'quality', 'crm'],
                'branches' => [
                    ['name' => 'Thrissur Artisan Hub & Vault', 'code' => 'BR-TSR-01', 'address' => 'Round South, Thrissur', 'city' => 'Thrissur', 'state' => 'Kerala', 'postal_code' => '680001', 'contact_number' => '+91 487 242 9901', 'email' => 'thrissur@heritagegold.com', 'is_main_branch' => true],
                    ['name' => 'Kochi MG Road Luxury Showroom', 'code' => 'BR-KOC-02', 'address' => 'MG Road, Shenoys, Kochi', 'city' => 'Kochi', 'state' => 'Kerala', 'postal_code' => '682035', 'contact_number' => '+91 484 238 4400', 'email' => 'kochi@heritagegold.com'],
                    ['name' => 'Kozhikode Gold Souk Gallery', 'code' => 'BR-CCJ-03', 'address' => 'Mavoor Road, Kozhikode', 'city' => 'Kozhikode', 'state' => 'Kerala', 'postal_code' => '673004', 'contact_number' => '+91 495 272 1100', 'email' => 'calicut@heritagegold.com'],
                ],
                'departments' => [
                    ['name' => 'Bullion & Raw Gold Assay Testing', 'code' => 'DEP-BULLION', 'type' => 'quality'],
                    ['name' => 'Handcrafted Goldsmithing & Filigree', 'code' => 'DEP-SMITH', 'type' => 'production'],
                    ['name' => 'Diamond Grasping & Gemstone Setting', 'code' => 'DEP-GEMS', 'type' => 'production'],
                    ['name' => 'Showroom Customer Lounges', 'code' => 'DEP-SALES', 'type' => 'sales'],
                    ['name' => 'Bullion Treasury & Accounts', 'code' => 'DEP-ACC', 'type' => 'accounts'],
                ],
                'godowns' => [
                    ['name' => 'High-Security Underground Bullion Safe', 'code' => 'GDW-TSR-SAFE', 'location_type' => 'store', 'branch_code' => 'BR-TSR-01'],
                    ['name' => 'Certified Diamond & Gemstone Vault', 'code' => 'GDW-TSR-GEMS', 'location_type' => 'store', 'branch_code' => 'BR-TSR-01'],
                    ['name' => 'Kochi Showroom Strongroom', 'code' => 'GDW-KOC-SHOW', 'location_type' => 'store', 'branch_code' => 'BR-KOC-02'],
                ],
                'units' => [
                    ['name' => 'Gram', 'symbol' => 'GM', 'type' => 'weight'],
                    ['name' => 'Carat', 'symbol' => 'CRT', 'type' => 'weight'],
                    ['name' => 'Piece', 'symbol' => 'PCS', 'type' => 'quantity'],
                    ['name' => 'Bridal Set', 'symbol' => 'SET', 'type' => 'quantity'],
                    ['name' => 'Milligram', 'symbol' => 'MG', 'type' => 'weight'],
                ],
                'categories' => [
                    ['name' => '24K Bullion & Raw Metals', 'description' => '999 pure bullion bars and silver grains', 'subcategories' => ['Pure Gold Bars', 'Silver Granules']],
                    ['name' => 'Certified Cut Diamonds & Gems', 'description' => 'VVS diamonds and precious stones', 'subcategories' => ['Cut Diamonds', 'Precious Gems']],
                    ['name' => 'Fine Finished Jewellery', 'description' => '22K bridal temple jewellery and solitaire rings', 'subcategories' => ['Antique Temple Jewellery', 'Diamond Rings']],
                ],
                'raw_materials' => [
                    ['name' => '24 Karat Fine Gold Ingot 99.99% Purity', 'code' => 'RM-GOLD-24K', 'category' => '24K Bullion & Raw Metals', 'subcategory' => 'Pure Gold Bars', 'unit' => 'GM', 'price' => 7450, 'gst' => 3, 'hsn' => '7108'],
                    ['name' => 'Certified Round Brilliant Cut Diamond VVS1-E', 'code' => 'RM-DIA-RND', 'category' => 'Certified Cut Diamonds & Gems', 'subcategory' => 'Cut Diamonds', 'unit' => 'CRT', 'price' => 65000, 'gst' => 1.5, 'hsn' => '7102'],
                    ['name' => 'Natural Colombian Emerald Oval Cut', 'code' => 'RM-GEM-EMR', 'category' => 'Certified Cut Diamonds & Gems', 'subcategory' => 'Precious Gems', 'unit' => 'CRT', 'price' => 18500, 'gst' => 1.5, 'hsn' => '7103'],
                ],
                'products' => [
                    ['name' => '22 Karat Heritage Antique Temple Necklace 64g', 'code' => 'FG-JWL-TEMPLE', 'category' => 'Fine Finished Jewellery', 'subcategory' => 'Antique Temple Jewellery', 'unit' => 'PCS', 'price' => 510000, 'gst' => 3, 'hsn' => '7113'],
                    ['name' => '18 Karat White Gold 1.0 Carat Solitaire Ring', 'code' => 'FG-JWL-SOLRING', 'category' => 'Fine Finished Jewellery', 'subcategory' => 'Diamond Rings', 'unit' => 'PCS', 'price' => 98000, 'gst' => 3, 'hsn' => '7113'],
                    ['name' => '22 Karat Traditional Kasu Mala Chain 32g', 'code' => 'FG-JWL-KASU', 'category' => 'Fine Finished Jewellery', 'subcategory' => 'Antique Temple Jewellery', 'unit' => 'PCS', 'price' => 255000, 'gst' => 3, 'hsn' => '7113'],
                ],
                'suppliers' => [
                    ['name' => 'MMTC-PAMP India Bullion Refinery', 'code' => 'SUP-MMTC-01', 'contact_person' => 'Ravi Singhal', 'phone' => '+91 11 4055 7700', 'email' => 'bullion@mmtcpamp.com', 'city' => 'New Delhi', 'gst' => '07AAACM1122D1Z9'],
                    ['name' => 'Surat Diamond Bourse Exporters', 'code' => 'SUP-SDB-02', 'contact_person' => 'Jitesh Dholakia', 'phone' => '+91 261 298 4400', 'email' => 'diamonds@sdb.org', 'city' => 'Surat', 'gst' => '24AAACS5566E1Z3'],
                    ['name' => 'Emerald Jewel Industry India Ltd.', 'code' => 'SUP-EMR-03', 'contact_person' => 'K. Srinivasan', 'phone' => '+91 422 422 2200', 'email' => 'jewellery@ejindia.com', 'city' => 'Coimbatore', 'gst' => '33AAACE9988F1Z7'],
                ],
                'customers' => [
                    ['name' => 'Royal Travancore Heritage Trust', 'code' => 'CUS-TRV-01', 'shop_name' => 'Kowdiar Palace Treasury', 'phone' => '+91 471 231 4455', 'email' => 'trust@travancoreheritage.org', 'city' => 'Thiruvananthapuram', 'gst' => '32AAACT1122G1Z1'],
                    ['name' => 'Kochi Coastal NRI Family Accounts', 'code' => 'CUS-NRI-02', 'shop_name' => 'Panampilly Nagar VIP Desk', 'phone' => '+91 484 231 9900', 'email' => 'privateclient@heritagegold.com', 'city' => 'Kochi', 'gst' => null],
                    ['name' => 'Malabar Bridal Couture Patrons', 'code' => 'CUS-MAL-03', 'shop_name' => 'Calicut Bridal Salon', 'phone' => '+91 495 276 4455', 'email' => 'bridal@malabarpatrons.in', 'city' => 'Kozhikode', 'gst' => null],
                    ['name' => 'High Net-Worth Corporate Gifting Circle', 'code' => 'CUS-HNW-04', 'shop_name' => 'Thrissur Round Executive', 'phone' => '+91 487 233 8899', 'email' => 'corporate@thrissurjewels.com', 'city' => 'Thrissur', 'gst' => '32AAACH5566H1Z5'],
                ],
                'staff' => [
                    ['first_name' => 'K. R.', 'last_name' => 'Namboodiri', 'employee_id' => 'STF-HG-01', 'designation' => 'Chief Gemologist & Bullion Officer', 'employee_type' => 'salaried', 'salary_amount' => 85000, 'basic_salary' => 70000, 'dept_code' => 'DEP-BULLION', 'pf_applicable' => true],
                    ['first_name' => 'Unnikrishnan', 'last_name' => 'V', 'employee_id' => 'STF-HG-02', 'designation' => 'Master Goldsmith & Hand Filigree', 'employee_type' => 'piece_rate', 'piece_rate' => 1200, 'dept_code' => 'DEP-SMITH'],
                    ['first_name' => 'Sreedevi', 'last_name' => 'Nair', 'employee_id' => 'STF-HG-03', 'designation' => 'Diamond Grader & Appraiser', 'employee_type' => 'contract', 'salary_amount' => 45000, 'basic_salary' => 45000, 'dept_code' => 'DEP-GEMS'],
                    ['first_name' => 'Jayaprakash', 'last_name' => 'P', 'employee_id' => 'STF-HG-04', 'designation' => 'Stone Prong Setter', 'employee_type' => 'hourly', 'hourly_rate' => 300, 'dept_code' => 'DEP-SMITH', 'esi_applicable' => true],
                    ['first_name' => 'Raghavan', 'last_name' => 'K', 'employee_id' => 'STF-HG-05', 'designation' => 'Armed Vault Security Escort', 'employee_type' => 'daily_wage', 'salary_amount' => 1100, 'dept_code' => 'DEP-BULLION', 'esi_applicable' => true],
                ],
            ],
        ];
    }
}
