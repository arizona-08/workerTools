<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Actions\ReinforcedConcreteUnitWeight;

/** Résultat traçable du seul calcul de poids propre, sans total ni combinaison. */
final readonly class SelfWeightResult
{
    public const SECTION_AREA_UNIT = 'm²';

    public const LINE_LOAD_UNIT = 'kN/m';

    public const FORMULA = 'Gk_self = Ac × γ_RC';

    public function __construct(
        public bool $included,
        public float $widthMillimetres,
        public float $heightMillimetres,
        public float $sectionArea,
        public ReinforcedConcreteUnitWeight $unitWeight,
        public float $characteristicLineLoad,
    ) {}
}
