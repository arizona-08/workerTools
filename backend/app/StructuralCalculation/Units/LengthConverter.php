<?php

namespace App\StructuralCalculation\Units;

/** Conversions de longueur explicites utilisées par le moteur. */
final class LengthConverter
{
    private const MILLIMETRES_PER_METRE = 1000.0;

    public function millimetresToMetres(float $millimetres): float
    {
        return $millimetres / self::MILLIMETRES_PER_METRE;
    }
}
