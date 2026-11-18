<?php

namespace App\Services;

use App\Models\Item;
use App\Models\Unit;
use InvalidArgumentException;

class UnitConversionService
{
    /**
     * Converts an entered quantity in $fromUnit to the base quantity of $item.
     * Returns: ['entered_quantity', 'base_quantity', 'conversion_factor', 'unit_id']
     */
    public static function convertToBaseQuantity(Item $item, float $enteredQty, ?int $unitId = null): array
    {
        $baseUnit = $item->unit;
        if (!$unitId || !$baseUnit || $unitId == $baseUnit->id) {
            return [
                'entered_quantity' => $enteredQty,
                'base_quantity' => $enteredQty,
                'conversion_factor' => 1.0,
                'unit_id' => $baseUnit?->id ?? $unitId,
            ];
        }

        $enteredUnit = Unit::find($unitId);
        if (!$enteredUnit) {
            return [
                'entered_quantity' => $enteredQty,
                'base_quantity' => $enteredQty,
                'conversion_factor' => 1.0,
                'unit_id' => $baseUnit->id,
            ];
        }

        // Validate unit type compatibility (e.g. weight with weight, quantity with quantity)
        if ($enteredUnit->unit_type !== $baseUnit->unit_type && $enteredUnit->unit_type !== 'other' && $baseUnit->unit_type !== 'other') {
            throw new InvalidArgumentException("Incompatible unit conversion between {$enteredUnit->name} ({$enteredUnit->unit_type}) and {$item->name}'s base unit {$baseUnit->name} ({$baseUnit->unit_type}).");
        }

        $baseFactor = (float)($baseUnit->conversion_factor ?: 1.0);
        $enteredFactor = (float)($enteredUnit->conversion_factor ?: 1.0);

        $conversionRatio = $enteredFactor / $baseFactor;
        $baseQty = round($enteredQty * $conversionRatio, 4);

        return [
            'entered_quantity' => $enteredQty,
            'base_quantity' => $baseQty,
            'conversion_factor' => $conversionRatio,
            'unit_id' => $enteredUnit->id,
        ];
    }
}
