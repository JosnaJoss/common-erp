<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Company;
use App\Models\Branch;
use App\Models\Role;
use App\Models\Staff;
use App\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

class UserManagementAndCompanyRolesEnhancementTest extends TestCase
{
    protected User $superAdmin;
    protected Company $companyA;
    protected Company $companyB;
    protected Role $roleA;
    protected Role $roleB;
    protected Staff $staffA;
    protected Staff $staffB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::where('email', 'admin@erp.com')->first();
        if (!$this->superAdmin) {
            $globalRole = Role::firstOrCreate(
                ['slug' => 'super-admin', 'company_id' => null],
                ['name' => 'Super Administrator', 'is_system' => true]
            );
            $this->superAdmin = User::create([
                'name' => 'Super Administrator',
                'email' => 'admin@erp.com',
                'password' => Hash::make('password'),
                'role_id' => $globalRole->id,
                'company_id' => 1,
                'is_active' => true,
            ]);
        }

        $this->companyA = Company::find(1) ?? Company::first();
        $this->companyB = Company::where('id', '!=', $this->companyA->id)->whereHas('users')->first();
        if (!$this->companyB) {
            $this->companyB = Company::where('id', '!=', $this->companyA->id)->first();
        }

        // Ensure both companies have roles
        Role::seedCompanyRoles($this->companyA->id);
        Role::seedCompanyRoles($this->companyB->id);

        $this->roleA = Role::where('company_id', $this->companyA->id)->first();
        $this->roleB = Role::where('company_id', $this->companyB->id)->first();

        // Ensure staff in both companies
        $this->staffA = Staff::firstOrCreate(
            ['employee_id' => 'TEST-STF-A1', 'company_id' => $this->companyA->id],
            [
                'first_name' => 'Amit',
                'last_name' => 'Patel',
                'email' => 'amit.patel@companya.test',
                'phone' => '9800000001',
                'status' => 'active',
            ]
        );

        $this->staffB = Staff::firstOrCreate(
            ['employee_id' => 'TEST-STF-B1', 'company_id' => $this->companyB->id],
            [
                'first_name' => 'Balraj',
                'last_name' => 'Singh',
                'email' => 'balraj.singh@companyb.test',
                'phone' => '9800000002',
                'status' => 'active',
            ]
        );
    }

    public function test_company_a_user_listing_shows_only_company_a_users()
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['active_company_id' => $this->companyA->id])
            ->get(route('settings.users.index'));

        $response->assertStatus(200);

        // All users in response view data must belong strictly to Company A
        $usersInView = $response->viewData('users');
        $this->assertNotEmpty($usersInView);
        foreach ($usersInView as $u) {
            $this->assertEquals($this->companyA->id, $u->company_id, "User {$u->id} should belong to Company A");
        }

        // Active company must be Company A
        $this->assertEquals($this->companyA->id, $response->viewData('activeCompany')->id);

        // Roles in view must belong to Company A
        $rolesInView = $response->viewData('roles');
        foreach ($rolesInView as $r) {
            $this->assertEquals($this->companyA->id, $r->company_id, "Role {$r->id} must belong to Company A");
        }
    }

    public function test_switching_to_company_b_shows_only_company_b_users()
    {
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['active_company_id' => $this->companyB->id])
            ->get(route('settings.users.index'));

        $response->assertStatus(200);

        $usersInView = $response->viewData('users');
        $this->assertNotEmpty($usersInView);
        foreach ($usersInView as $u) {
            $this->assertEquals($this->companyB->id, $u->company_id, "User {$u->id} should belong to Company B");
        }

        $this->assertEquals($this->companyB->id, $response->viewData('activeCompany')->id);

        $rolesInView = $response->viewData('roles');
        foreach ($rolesInView as $r) {
            $this->assertEquals($this->companyB->id, $r->company_id, "Role {$r->id} must belong to Company B");
        }
    }

    public function test_cannot_update_company_a_user_while_company_b_is_active()
    {
        $userA = User::where('company_id', $this->companyA->id)->first();
        $this->assertNotNull($userA);

        // Attempt updating User A while active company is Company B
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['active_company_id' => $this->companyB->id])
            ->post(route('settings.users.update', $userA), [
                'name' => 'Hacked Name',
                'role_id' => $this->roleB->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_user_search_and_role_filter_are_company_scoped()
    {
        $userA = User::where('company_id', $this->companyA->id)->first();

        $response = $this->actingAs($this->superAdmin)
            ->withSession(['active_company_id' => $this->companyA->id])
            ->get(route('settings.users.index', ['search' => $userA->email]));

        $response->assertStatus(200);
        $usersInView = $response->viewData('users');
        $this->assertTrue($usersInView->contains('id', $userA->id));

        // Searching for User A while Company B is active must return 0 results
        $responseB = $this->actingAs($this->superAdmin)
            ->withSession(['active_company_id' => $this->companyB->id])
            ->get(route('settings.users.index', ['search' => $userA->email]));

        $responseB->assertStatus(200);
        $this->assertFalse($responseB->viewData('users')->contains('id', $userA->id));
    }

    public function test_employee_based_user_creation_associates_staff_and_auto_assigns_company()
    {
        // Create an unassigned staff in Company A
        $uniqueCode = 'TEST-EMP-' . uniqid();
        $staff = Staff::create([
            'employee_id' => $uniqueCode,
            'company_id' => $this->companyA->id,
            'first_name' => 'Chetan',
            'last_name' => 'Sharma',
            'email' => 'chetan.' . uniqid() . '@companya.test',
            'phone' => '9811112222',
            'status' => 'active',
        ]);

        $userEmail = 'chetan.' . uniqid() . '@erp.test';

        $response = $this->actingAs($this->superAdmin)
            ->withSession(['active_company_id' => $this->companyA->id])
            ->post(route('settings.users.store'), [
                'staff_id' => $staff->id,
                'email' => $userEmail,
                'password' => 'secret123',
                'role_id' => $this->roleA->id,
                'phone' => '9811112222',
                'is_active' => 1,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verify user in DB
        $user = User::where('email', $userEmail)->first();
        $this->assertNotNull($user);
        $this->assertEquals('Chetan Sharma', $user->name);
        $this->assertEquals($this->companyA->id, $user->company_id);
        $this->assertEquals($staff->id, $user->staff_id);

        // Verify staff record was linked
        $staff->refresh();
        $this->assertEquals($user->id, $staff->user_id);
    }

    public function test_cannot_create_user_with_staff_from_another_company()
    {
        // Try creating user in Company A using Staff B (from Company B)
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['active_company_id' => $this->companyA->id])
            ->post(route('settings.users.store'), [
                'staff_id' => $this->staffB->id,
                'email' => 'cross.company.' . uniqid() . '@test.com',
                'password' => 'secret123',
                'role_id' => $this->roleA->id,
            ]);

        $response->assertSessionHasErrors('staff_id');
    }

    public function test_cannot_create_duplicate_user_for_same_employee()
    {
        // Find staff with an existing user or create one
        $staff = Staff::create([
            'employee_id' => 'TEST-DUP-' . uniqid(),
            'company_id' => $this->companyA->id,
            'first_name' => 'Dinesh',
            'last_name' => 'Karthik',
            'email' => 'dinesh.' . uniqid() . '@test.com',
            'status' => 'active',
        ]);

        // First creation succeeds
        $this->actingAs($this->superAdmin)
            ->withSession(['active_company_id' => $this->companyA->id])
            ->post(route('settings.users.store'), [
                'staff_id' => $staff->id,
                'email' => 'dinesh1.' . uniqid() . '@test.com',
                'password' => 'secret123',
                'role_id' => $this->roleA->id,
            ])->assertSessionHas('success');

        // Second creation for same staff must fail
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['active_company_id' => $this->companyA->id])
            ->post(route('settings.users.store'), [
                'staff_id' => $staff->id,
                'email' => 'dinesh2.' . uniqid() . '@test.com',
                'password' => 'secret123',
                'role_id' => $this->roleA->id,
            ]);

        $response->assertSessionHasErrors('staff_id');
    }

    public function test_create_super_administrator_and_manager_roles_in_both_company_a_and_company_b()
    {
        $roleName1 = 'Super Administrator Custom ' . rand(100, 999);
        $roleName2 = 'Floor Manager ' . rand(100, 999);

        // 1. Create role in Company A
        $resA1 = $this->actingAs($this->superAdmin)
            ->withSession(['active_company_id' => $this->companyA->id])
            ->post(route('settings.roles.store'), [
                'name' => $roleName1,
                'description' => 'Super admin within Company A',
            ]);
        $resA1->assertSessionHas('success');

        // 2. Create role with IDENTICAL name in Company B
        $resB1 = $this->actingAs($this->superAdmin)
            ->withSession(['active_company_id' => $this->companyB->id])
            ->post(route('settings.roles.store'), [
                'name' => $roleName1,
                'description' => 'Super admin within Company B',
            ]);
        $resB1->assertSessionHas('success');

        // 3. Create Manager in both companies
        $this->actingAs($this->superAdmin)
            ->withSession(['active_company_id' => $this->companyA->id])
            ->post(route('settings.roles.store'), [
                'name' => $roleName2,
                'description' => 'Manager in Company A',
            ])->assertSessionHas('success');

        $this->actingAs($this->superAdmin)
            ->withSession(['active_company_id' => $this->companyB->id])
            ->post(route('settings.roles.store'), [
                'name' => $roleName2,
                'description' => 'Manager in Company B',
            ])->assertSessionHas('success');

        // Verify both exist independently in database
        $roleA = Role::where('company_id', $this->companyA->id)->where('name', $roleName1)->first();
        $roleB = Role::where('company_id', $this->companyB->id)->where('name', $roleName1)->first();

        $this->assertNotNull($roleA);
        $this->assertNotNull($roleB);
        $this->assertNotEquals($roleA->id, $roleB->id);
    }

    public function test_duplicate_role_name_within_same_company_is_rejected()
    {
        $roleName = 'Unique Role ' . rand(1000, 9999);

        // First creation succeeds
        $this->actingAs($this->superAdmin)
            ->withSession(['active_company_id' => $this->companyA->id])
            ->post(route('settings.roles.store'), [
                'name' => $roleName,
            ])->assertSessionHas('success');

        // Duplicate creation in same company must fail validation
        $response = $this->actingAs($this->superAdmin)
            ->withSession(['active_company_id' => $this->companyA->id])
            ->post(route('settings.roles.store'), [
                'name' => $roleName,
            ]);

        $response->assertSessionHasErrors('name');
    }

    public function test_company_roles_permissions_are_isolated_between_companies()
    {
        $perm1 = Permission::where('module', 'inventory')->first();
        $perm2 = Permission::where('module', 'accounts')->first();

        $roleName = 'Auditor ' . rand(100, 999);

        // Create in Company A with perm1
        $this->actingAs($this->superAdmin)
            ->withSession(['active_company_id' => $this->companyA->id])
            ->post(route('settings.roles.store'), [
                'name' => $roleName,
                'permissions' => [$perm1->id],
            ])->assertSessionHas('success');

        // Create in Company B with perm2
        $this->actingAs($this->superAdmin)
            ->withSession(['active_company_id' => $this->companyB->id])
            ->post(route('settings.roles.store'), [
                'name' => $roleName,
                'permissions' => [$perm2->id],
            ])->assertSessionHas('success');

        $roleA = Role::where('company_id', $this->companyA->id)->where('name', $roleName)->first();
        $roleB = Role::where('company_id', $this->companyB->id)->where('name', $roleName)->first();

        $this->assertTrue($roleA->permissions->contains('id', $perm1->id));
        $this->assertFalse($roleA->permissions->contains('id', $perm2->id));

        $this->assertTrue($roleB->permissions->contains('id', $perm2->id));
        $this->assertFalse($roleB->permissions->contains('id', $perm1->id));
    }

    public function test_company_super_admin_role_does_not_grant_global_platform_privileges()
    {
        // Company A creates a custom role named "Super Administrator" with company_id = companyA
        $customRole = Role::create([
            'company_id' => $this->companyA->id,
            'name' => 'Super Administrator Local ' . uniqid(),
            'slug' => 'super-admin-' . uniqid(),
            'is_system' => false,
        ]);

        $companyUser = User::create([
            'name' => 'Company A Local Admin',
            'email' => 'localadmin.' . uniqid() . '@companya.test',
            'password' => Hash::make('secret'),
            'company_id' => $this->companyA->id,
            'role_id' => $customRole->id,
            'is_active' => true,
        ]);

        // Assert isSuperAdmin() returns FALSE for this company user
        $this->assertFalse($companyUser->isSuperAdmin(), "Local company role named Super Administrator must not grant global super admin privileges");

        // Global superAdmin user must still return TRUE
        $this->assertTrue($this->superAdmin->isSuperAdmin());
    }

    public function test_cannot_edit_or_delete_another_company_role()
    {
        $roleA = Role::where('company_id', $this->companyA->id)->first();
        $this->assertNotNull($roleA);

        // While Company B is active, attempt to edit Role A
        $editResponse = $this->actingAs($this->superAdmin)
            ->withSession(['active_company_id' => $this->companyB->id])
            ->get(route('settings.roles.edit', $roleA));

        $editResponse->assertStatus(403);

        // While Company B is active, attempt to update Role A
        $updateResponse = $this->actingAs($this->superAdmin)
            ->withSession(['active_company_id' => $this->companyB->id])
            ->post(route('settings.roles.update', $roleA), [
                'name' => 'Malicious Rename',
            ]);

        $updateResponse->assertStatus(403);

        // While Company B is active, attempt to delete Role A
        $deleteResponse = $this->actingAs($this->superAdmin)
            ->withSession(['active_company_id' => $this->companyB->id])
            ->delete(route('settings.roles.destroy', $roleA));

        $deleteResponse->assertStatus(403);
    }
}
