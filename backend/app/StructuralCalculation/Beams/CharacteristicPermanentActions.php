<?php

namespace App\StructuralCalculation\Beams;

/** Actions permanentes caractéristiques traçables, sans coefficient partiel. */
final readonly class CharacteristicPermanentActions
{
    public const UNIT = 'kN/m';

    public const TOTAL_FORMULA = 'Gk_total = Gk_self + Gk_additional';

    public function __construct(
        public SelfWeightResult $selfWeight,
        public float $additionalPermanentLoad,
        public float $totalPermanentLoad,
    ) {}
}
