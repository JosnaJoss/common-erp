<?php

namespace Database\Seeders;

use App\Models\AccountGroup;
use App\Models\Company;
use App\Models\FinancialYear;
use App\Models\Ledger;
use Illuminate\Database\Seeder;

class AccountChartSeeder extends Seeder
{
    public function run(): void
    {
        $companies = Company::all();

        foreach ($companies as $company) {
            // 1. Financial Year
            FinancialYear::firstOrCreate([
                'company_id' => $company->id,
                'title' => 'FY 2026-2027',
            ], [
                'start_date' => '2026-04-01',
                'end_date' => '2027-03-31',
                'is_closed' => false,
            ]);

            // 2. Account Groups
            $assetGroup = AccountGroup::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Current Assets'],
                ['nature' => 'Assets', 'code' => 'CA-01']
            );

            $fixedAssetGroup = AccountGroup::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Fixed Assets'],
                ['nature' => 'Assets', 'code' => 'FA-01']
            );

            $bankCashGroup = AccountGroup::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Bank & Cash Accounts'],
                ['nature' => 'Assets', 'code' => 'BK-01', 'parent_id' => $assetGroup->id]
            );

            $debtorGroup = AccountGroup::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Sundry Debtors (Customers)'],
                ['nature' => 'Assets', 'code' => 'SD-01', 'parent_id' => $assetGroup->id]
            );

            $liabilityGroup = AccountGroup::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Current Liabilities'],
                ['nature' => 'Liabilities', 'code' => 'CL-01']
            );

            $creditorGroup = AccountGroup::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Sundry Creditors (Suppliers)'],
                ['nature' => 'Liabilities', 'code' => 'SC-01', 'parent_id' => $liabilityGroup->id]
            );

            $dutiesTaxesGroup = AccountGroup::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Duties & Taxes'],
                ['nature' => 'Liabilities', 'code' => 'DT-01', 'parent_id' => $liabilityGroup->id]
            );

            $equityGroup = AccountGroup::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Capital & Reserves'],
                ['nature' => 'Equity', 'code' => 'EQ-01']
            );

            $revenueGroup = AccountGroup::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Sales & Operating Revenue'],
                ['nature' => 'Revenue', 'code' => 'REV-01']
            );

            $expenseGroup = AccountGroup::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Direct Expenses & Purchases'],
                ['nature' => 'Expenses', 'code' => 'EXP-01']
            );

            $indirectExpenseGroup = AccountGroup::firstOrCreate(
                ['company_id' => $company->id, 'name' => 'Administrative & Indirect Expenses'],
                ['nature' => 'Expenses', 'code' => 'EXP-02']
            );

            // 3. Standard Ledgers
            $ledgers = [
                // Cash & Bank
                ['name' => 'Cash in Hand', 'group' => $bankCashGroup, 'nature' => 'debit', 'bal' => 250000.00, 'bank' => true],
                ['name' => 'HDFC Bank Ltd (Current A/c 50200012345678)', 'group' => $bankCashGroup, 'nature' => 'debit', 'bal' => 1500000.00, 'bank' => true, 'acc' => '50200012345678', 'ifsc' => 'HDFC0000123', 'bname' => 'HDFC Bank'],
                ['name' => 'State Bank of India (A/c 30129876543)', 'group' => $bankCashGroup, 'nature' => 'debit', 'bal' => 750000.00, 'bank' => true, 'acc' => '30129876543', 'ifsc' => 'SBIN0000456', 'bname' => 'State Bank of India'],
                
                // Revenue & Expense
                ['name' => 'Sales Account', 'group' => $revenueGroup, 'nature' => 'credit', 'bal' => 0.00, 'bank' => false],
                ['name' => 'Sales Return Account', 'group' => $revenueGroup, 'nature' => 'debit', 'bal' => 0.00, 'bank' => false],
                ['name' => 'Purchase Account', 'group' => $expenseGroup, 'nature' => 'debit', 'bal' => 0.00, 'bank' => false],
                ['name' => 'Purchase Return Account', 'group' => $expenseGroup, 'nature' => 'credit', 'bal' => 0.00, 'bank' => false],
                ['name' => 'Salaries & Staff Wages', 'group' => $indirectExpenseGroup, 'nature' => 'debit', 'bal' => 0.00, 'bank' => false],
                ['name' => 'Freight & Logistics Outward', 'group' => $indirectExpenseGroup, 'nature' => 'debit', 'bal' => 0.00, 'bank' => false],
                ['name' => 'Office Rent & Maintenance', 'group' => $indirectExpenseGroup, 'nature' => 'debit', 'bal' => 0.00, 'bank' => false],
                ['name' => 'Electricity & Utilities', 'group' => $indirectExpenseGroup, 'nature' => 'debit', 'bal' => 0.00, 'bank' => false],

                // Taxes
                ['name' => 'GST Input Tax Credit (Receivable)', 'group' => $assetGroup, 'nature' => 'debit', 'bal' => 50000.00, 'bank' => false],
                ['name' => 'GST Output Tax Liability (Payable)', 'group' => $dutiesTaxesGroup, 'nature' => 'credit', 'bal' => 0.00, 'bank' => false],
                ['name' => 'PF & ESI Payable', 'group' => $dutiesTaxesGroup, 'nature' => 'credit', 'bal' => 0.00, 'bank' => false],

                // Equity (Balances initial Assets 2550000 + Debtors 225000 - Creditors 73000 = 2702000)
                ['name' => 'Share Capital Account', 'group' => $equityGroup, 'nature' => 'credit', 'bal' => 2702000.00, 'bank' => false],
            ];

            foreach ($ledgers as $l) {
                Ledger::firstOrCreate(['company_id' => $company->id, 'name' => $l['name']], [
                    'account_group_id' => $l['group']->id,
                    'opening_balance' => $l['bal'],
                    'balance_type' => $l['nature'],
                    'current_balance' => $l['bal'],
                    'is_bank_or_cash' => $l['bank'],
                    'bank_account_no' => $l['acc'] ?? null,
                    'bank_ifsc' => $l['ifsc'] ?? null,
                    'bank_name' => $l['bname'] ?? null,
                    'status' => 'active',
                ]);
            }
        }
    }
}
