<?php

namespace App\StructuralCalculation\Materials\ReinforcementSteel;

/** Aire géométrique commune d'une barre circulaire d'armature. */
final class ReinforcementBarAreaCalculator
{
    public const FORMULA = 'Aφ = π × φ² / 4';

    public function calculate(float $diameter): float
    {
        if (! is_finite($diameter) || $diameter <= 0) {
            throw new \InvalidArgumentException('Reinforcement bar diameter must be a positive finite number.');
        }

        return M_PI * $diameter ** 2 / 4;
    }
}
