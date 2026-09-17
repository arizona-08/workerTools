<?php

namespace App\StructuralCalculation\Materials\ReinforcementSteel;

/**
 * Diamètres nominaux d'armatures passives proposés par le V1.
 * Configuration applicative, non une liste normative exhaustive.
 */
final class ReinforcementBarDiameterCatalog
{
    /** @var list<float> */
    private const DIAMETERS = [8.0, 10.0, 12.0, 14.0, 16.0, 20.0, 25.0, 32.0];

    /** @return list<float> */
    public function all(): array
    {
        return self::DIAMETERS;
    }

    public function supports(float $diameter): bool
    {
        return in_array($diameter, self::DIAMETERS, true);
    }
}
