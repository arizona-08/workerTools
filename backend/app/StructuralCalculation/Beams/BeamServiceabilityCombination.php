<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Profiles\ServiceabilityCombinationExpression;

/** Une combinaison ELS traçable, exprimée en charge linéaire. */
final readonly class BeamServiceabilityCombination
{
    public const UNIT = 'kN/m';

    public function __construct(
        public float $permanentCharacteristicLoad,
        public float $variableCharacteristicLoad,
        public float $variableFactor,
        public float $permanentContribution,
        public float $variableContribution,
        public float $resultingLineLoad,
        public ServiceabilityCombinationExpression $expressionReference,
        public string $formula,
    ) {}
}
