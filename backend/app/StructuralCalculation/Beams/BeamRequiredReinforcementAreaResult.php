<?php

namespace App\StructuralCalculation\Beams;

/** Aire continue cible avant toute sélection discrète de barres. */
final readonly class BeamRequiredReinforcementAreaResult
{
    public const AREA_UNIT = 'mm²';

    public const FORMULA = 'As_target = max(As_req, As_min)';

    public function __construct(
        public float $flexuralRequiredArea,
        public float $minimumRequiredArea,
        public float $targetArea,
        public BeamReinforcementTargetGoverningRequirement $governingRequirement,
    ) {}
}
