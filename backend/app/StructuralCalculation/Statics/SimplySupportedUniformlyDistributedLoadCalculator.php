<?php

namespace App\StructuralCalculation\Statics;

/**
 * Noyau analytique commun, limité à une travée simplement appuyée sous charge uniforme.
 * Les appelants conservent la responsabilité de valider leurs invariants métier.
 */
final class SimplySupportedUniformlyDistributedLoadCalculator
{
    public const MAXIMUM_MOMENT_COEFFICIENT = 1 / 8;

    public const MAXIMUM_SHEAR_COEFFICIENT = 1 / 2;

    public function maximumMoment(float $lineLoad, float $span): float
    {
        return $lineLoad * $span ** 2 * self::MAXIMUM_MOMENT_COEFFICIENT;
    }

    public function maximumShear(float $lineLoad, float $span): float
    {
        return $lineLoad * $span * self::MAXIMUM_SHEAR_COEFFICIENT;
    }
}
