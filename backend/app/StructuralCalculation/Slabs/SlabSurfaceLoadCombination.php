<?php

namespace App\StructuralCalculation\Slabs;

use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Eurocode\Profiles\ServiceabilityCombinationExpression;

/** Une charge surfacique combinée traçable, sans conversion vers une bande. */
final readonly class SlabSurfaceLoadCombination
{
    public const UNIT = 'kN/m²';

    public function __construct(
        public SlabSurfaceLoadCombinationType $type,
        public float $permanentCharacteristicLoad,
        public float $variableCharacteristicLoad,
        public float $permanentFactor,
        public float $variableFactor,
        public float $permanentContribution,
        public float $variableContribution,
        public float $value,
        public FundamentalUltimateCombinationExpression|ServiceabilityCombinationExpression $expressionReference,
        public string $formula,
    ) {}
}
