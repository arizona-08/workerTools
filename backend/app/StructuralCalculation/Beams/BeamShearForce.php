<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Eurocode\Profiles\ServiceabilityCombinationExpression;

/** Efforts tranchants aux appuis et valeur maximale absolue pour une combinaison. */
final readonly class BeamShearForce
{
    public const UNIT = 'kN';

    public function __construct(
        public float $lineLoad,
        public float $maximumAbsoluteShear,
        public float $leftSupportShear,
        public float $rightSupportShear,
        public FundamentalUltimateCombinationExpression|ServiceabilityCombinationExpression $combinationReference,
        public string $formula,
    ) {}
}
