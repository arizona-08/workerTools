<?php

namespace App\StructuralCalculation\Slabs;

use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeight;

/** Actions surfaciques caractéristiques traçables, sans combinaison ni analyse. */
final readonly class SlabCharacteristicActions
{
    public const LOAD_UNIT = 'kN/m²';

    public const SELF_WEIGHT_FORMULA = 'gk_self = γ_concrete × h';

    public const PERMANENT_TOTAL_FORMULA = 'Gk_total = gk_self + gk_finishes + gk_partitions + gk_otherPermanent';

    public function __construct(
        public float $thicknessMillimetres,
        public float $thicknessMetres,
        public ReinforcedConcreteUnitWeight $unitWeight,
        public float $selfWeight,
        public float $finishes,
        public float $partitions,
        public float $otherPermanent,
        public float $permanentTotal,
        public float $imposedLoad,
    ) {}
}
