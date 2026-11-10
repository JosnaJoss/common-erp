<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Create Roles
        $superAdmin = Role::firstOrCreate(['slug' => 'super-admin'], [
            'name' => 'Super Administrator',
            'description' => 'System-wide owner with complete unrestricted access to all companies and modules',
            'is_system' => true,
        ]);

        $companyAdmin = Role::firstOrCreate(['slug' => 'company-admin'], [
            'name' => 'Company Administrator',
            'description' => 'Full administrative access within the assigned company',
            'is_system' => true,
        ]);

        $accountsManager = Role::firstOrCreate(['slug' => 'accounts-manager'], [
            'name' => 'Accounts Manager',
            'description' => 'Manages finance, vouchers, ledgers, tax, payments, and financial reports',
            'is_system' => false,
        ]);

        $inventoryManager = Role::firstOrCreate(['slug' => 'inventory-manager'], [
            'name' => 'Inventory & Warehouse Manager',
            'description' => 'Controls stock, godowns, transfers, adjustments, and inventory alerts',
            'is_system' => false,
        ]);

        $salesManager = Role::firstOrCreate(['slug' => 'sales-manager'], [
            'name' => 'Sales Manager',
            'description' => 'Oversees customers, sales orders, invoices, dispatch, and collections',
            'is_system' => false,
        ]);

        $purchaseManager = Role::firstOrCreate(['slug' => 'purchase-manager'], [
            'name' => 'Purchase Manager',
            'description' => 'Oversees suppliers, purchase requests, orders, GRN, and bills',
            'is_system' => false,
        ]);

        $hrManager = Role::firstOrCreate(['slug' => 'hr-manager'], [
            'name' => 'HR & Payroll Manager',
            'description' => 'Manages employees, attendance, leaves, shifts, and monthly payroll',
            'is_system' => false,
        ]);

        // 2. Define Modules and Screens
        $modules = [
            'dashboard' => ['dashboard'],
            'companies' => ['company_profile', 'branches'],
            'master_data' => ['departments', 'godowns', 'units', 'categories', 'products', 'items', 'vehicles', 'routes', 'cities', 'locations'],
            'staff_hr' => ['staff', 'attendance', 'leaves', 'shifts', 'holidays', 'targets', 'incentives', 'allowances', 'overtime', 'payroll'],
            'suppliers' => ['suppliers', 'supplier_accounts'],
            'customers' => ['customers', 'customer_accounts'],
            'inventory' => ['stock_overview', 'inward', 'outward', 'transfers', 'adjustments', 'requests', 'batches', 'alerts'],
            'purchase' => ['purchase_requests', 'purchase_orders', 'grn', 'quality_checks', 'purchase_entries', 'purchase_returns', 'supplier_payments'],
            'sales' => ['sales_orders', 'sales_invoices', 'deliveries', 'sales_returns', 'collections'],
            'accounts' => ['chart_of_accounts', 'ledgers', 'journal_entries', 'payments', 'receipts', 'contra', 'financial_years'],
            'reports' => ['stock_reports', 'purchase_reports', 'sales_reports', 'financial_reports', 'staff_reports', 'vehicle_reports'],
            'settings' => ['roles', 'users', 'company_settings', 'audit_logs'],
        ];

        $actions = ['view', 'add', 'edit', 'delete', 'approve', 'export'];

        $allPermissionIds = [];
        foreach ($modules as $module => $screens) {
            foreach ($screens as $screen) {
                foreach ($actions as $action) {
                    $name = ucwords(str_replace('_', ' ', $action . ' ' . $screen));
                    $perm = Permission::firstOrCreate([
                        'module' => $module,
                        'screen' => $screen,
                        'action' => $action,
                    ], [
                        'name' => $name,
                        'description' => "Permission to {$action} in {$screen} under {$module}",
                    ]);
                    $allPermissionIds[] = $perm->id;
                }
            }
        }

        // Assign all permissions to Super Admin and Company Admin
        $superAdmin->permissions()->sync($allPermissionIds);
        $companyAdmin->permissions()->sync($allPermissionIds);

        // Assign specific permissions to Accounts Manager
        $accountsPerms = Permission::whereIn('module', ['dashboard', 'accounts', 'reports', 'suppliers', 'customers'])
            ->pluck('id')->toArray();
        $accountsManager->permissions()->sync($accountsPerms);

        // Assign specific permissions to Inventory Manager
        $inventoryPerms = Permission::whereIn('module', ['dashboard', 'inventory', 'master_data', 'reports'])
            ->pluck('id')->toArray();
        $inventoryManager->permissions()->sync($inventoryPerms);

        // Assign specific permissions to Sales Manager
        $salesPerms = Permission::whereIn('module', ['dashboard', 'sales', 'customers', 'reports', 'inventory'])
            ->pluck('id')->toArray();
        $salesManager->permissions()->sync($salesPerms);

        // Assign specific permissions to Purchase Manager
        $purchasePerms = Permission::whereIn('module', ['dashboard', 'purchase', 'suppliers', 'inventory', 'reports'])
            ->pluck('id')->toArray();
        $purchaseManager->permissions()->sync($purchasePerms);

        // Assign specific permissions to HR Manager
        $hrPerms = Permission::whereIn('module', ['dashboard', 'staff_hr', 'reports'])
            ->pluck('id')->toArray();
        $hrManager->permissions()->sync($hrPerms);
    }
}

