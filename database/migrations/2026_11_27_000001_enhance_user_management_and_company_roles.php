<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Company;
use App\Models\Role;
use App\Models\User;
use App\Models\Staff;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Drop global unique slug on roles if present, add composite unique on company_id and slug
        $indexes = collect(DB::select("SHOW INDEXES FROM roles"))->pluck('Key_name')->unique();
        if ($indexes->contains('roles_slug_unique')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropUnique('roles_slug_unique');
            });
        }

        if (!$indexes->contains('roles_company_id_slug_unique')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->unique(['company_id', 'slug'], 'roles_company_id_slug_unique');
            });
        }

        // 2. Add staff_id to users if not present
        if (!Schema::hasColumn('users', 'staff_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('staff_id')->nullable()->after('role_id')->constrained('staff')->nullOnDelete();
            });
        }

        // 3. Seed company-specific roles for existing companies and associate existing users
        $templateRoles = Role::whereNull('company_id')->get();
        $companies = Company::all();

        foreach ($companies as $comp) {
            $roleMap = [];
            foreach ($templateRoles as $tpl) {
                $existing = Role::where('company_id', $comp->id)->where('slug', $tpl->slug)->first();
                if (!$existing) {
                    $newRole = Role::create([
                        'company_id' => $comp->id,
                        'name' => $tpl->name,
                        'slug' => $tpl->slug,
                        'description' => $tpl->description,
                        'is_system' => $tpl->is_system,
                    ]);

                    // Copy permissions in bulk chunks
                    $permIds = DB::table('role_permissions')->where('role_id', $tpl->id)->pluck('permission_id');
                    $rows = [];
                    foreach ($permIds as $pid) {
                        $rows[] = [
                            'role_id' => $newRole->id,
                            'permission_id' => $pid,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                    if (!empty($rows)) {
                        foreach (array_chunk($rows, 200) as $chunk) {
                            DB::table('role_permissions')->insertOrIgnore($chunk);
                        }
                    }
                    $roleMap[$tpl->id] = $newRole->id;
                } else {
                    $roleMap[$tpl->id] = $existing->id;
                }
            }

            // Update company users (preserve User 1 global super-admin)
            $users = User::where('company_id', $comp->id)->where('id', '!=', 1)->get();
            foreach ($users as $u) {
                if (isset($roleMap[$u->role_id])) {
                    $u->role_id = $roleMap[$u->role_id];
                    $u->save();
                }
            }
        }

        // 4. Link Sunil Gupta (User 3) with Staff 1 if matching
        $staff1 = Staff::find(1);
        $user3 = User::find(3);
        if ($staff1 && $user3 && is_null($user3->staff_id)) {
            $user3->staff_id = $staff1->id;
            $user3->save();
            $staff1->user_id = $user3->id;
            $staff1->save();
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'staff_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropForeign(['staff_id']);
                $table->dropColumn('staff_id');
            });
        }

        $indexes = collect(DB::select("SHOW INDEXES FROM roles"))->pluck('Key_name')->unique();
        if ($indexes->contains('roles_company_id_slug_unique')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->dropUnique('roles_company_id_slug_unique');
            });
        }

        if (!$indexes->contains('roles_slug_unique')) {
            Schema::table('roles', function (Blueprint $table) {
                $table->unique('slug', 'roles_slug_unique');
            });
        }
    }
};
