<?php

namespace App\StructuralCalculation\Eurocode\Combinations;

use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Eurocode\Profiles\ServiceabilityCombinationExpression;

/** Contributions et résultat d'une combinaison, sans imposer d'unité de charge. */
final readonly class CombinedAction
{
    public function __construct(
        public float $permanentCharacteristic,
        public float $variableCharacteristic,
        public float $permanentFactor,
        public float $variableFactor,
        public float $permanentContribution,
        public float $variableContribution,
        public float $result,
        public FundamentalUltimateCombinationExpression|ServiceabilityCombinationExpression $expressionReference,
    ) {}
}
