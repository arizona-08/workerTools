<?php

namespace App\StructuralCalculation\Units;

/** Conversions d'effort explicites utilisées par le moteur. */
final class ForceConverter
{
    public const NEWTONS_PER_KILONEWTON = 1000.0;

    public function kilonewtonsToNewtons(float $kilonewtons): float
    {
        return $kilonewtons * self::NEWTONS_PER_KILONEWTON;
    }

    public function newtonsToKilonewtons(float $newtons): float
    {
        return $newtons / self::NEWTONS_PER_KILONEWTON;
    }
}
