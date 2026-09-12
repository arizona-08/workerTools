<?php

namespace App\StructuralCalculation\Units;

/** Conversions de moment explicites nécessaires aux équations de section en mm et N. */
final class MomentConverter
{
    public const NEWTON_MILLIMETRES_PER_KILONEWTON_METRE = 1_000_000.0;

    public function kilonewtonMetresToNewtonMillimetres(float $kilonewtonMetres): float
    {
        return $kilonewtonMetres * self::NEWTON_MILLIMETRES_PER_KILONEWTON_METRE;
    }
}
