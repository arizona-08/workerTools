<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;

/** Résultat traçable de l'expression fondamentale ELU, sans sollicitation. */
final readonly class BeamUltimateCombinationResult
{
    public const UNIT = 'kN/m';

    public const FORMULA = 'wEd = γG,sup × Gk_total + γQ × Qk';

    public function __construct(
        public float $permanentCharacteristicLoad,
        public float $permanentPartialFactor,
        public float $permanentDesignContribution,
        public float $variableCharacteristicLoad,
        public float $variablePartialFactor,
        public float $variableDesignContribution,
        public float $designLineLoad,
        public FundamentalUltimateCombinationExpression $expressionReference,
    ) {}
}
