<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'description',
        'is_system',
    ];

    protected $casts = [
        'is_system' => 'boolean',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permissions');
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public static function seedCompanyRoles(int $companyId): void
    {
        $templateRoles = static::whereNull('company_id')->get();
        foreach ($templateRoles as $tpl) {
            $existing = static::where('company_id', $companyId)->where('slug', $tpl->slug)->first();
            if (!$existing) {
                $newRole = static::create([
                    'company_id' => $companyId,
                    'name' => $tpl->name,
                    'slug' => $tpl->slug,
                    'description' => $tpl->description,
                    'is_system' => $tpl->is_system,
                ]);

                $permIds = \Illuminate\Support\Facades\DB::table('role_permissions')
                    ->where('role_id', $tpl->id)
                    ->pluck('permission_id');
                
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
                        \Illuminate\Support\Facades\DB::table('role_permissions')->insertOrIgnore($chunk);
                    }
                }
            }
        }
    }
}

