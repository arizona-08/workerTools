<?php

namespace App\StructuralCalculation\Eurocode\Actions;

/**
 * Référence MVP EN 1991-1-1 pour le béton armé de masse volumique normale.
 * Cette valeur ne dépend pas de la classe de résistance du béton.
 */
final class ReinforcedConcreteUnitWeightRepository
{
    private const NORMAL_WEIGHT_REINFORCED_CONCRETE = 25.0;

    public function normalWeightReinforcedConcrete(): ReinforcedConcreteUnitWeight
    {
        return new ReinforcedConcreteUnitWeight(self::NORMAL_WEIGHT_REINFORCED_CONCRETE);
    }
}
