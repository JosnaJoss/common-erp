<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'group_name',
        'setting_key',
        'setting_value',
    ];

    public static function getVal(int $companyId, string $key, $default = null)
    {
        $setting = self::where('company_id', $companyId)->where('setting_key', $key)->first();
        return $setting ? $setting->setting_value : $default;
    }

    public static function setVal(int $companyId, string $key, $value, string $group = 'general')
    {
        return self::updateOrCreate(
            ['company_id' => $companyId, 'setting_key' => $key],
            ['setting_value' => $value, 'group_name' => $group]
        );
    }
}

