<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Profiles\FundamentalUltimateCombinationExpression;
use App\StructuralCalculation\Eurocode\Profiles\ServiceabilityCombinationExpression;

/** Moment maximal associé à une charge linéaire et à sa combinaison source. */
final readonly class BeamBendingMoment
{
    public const UNIT = 'kN·m';

    public function __construct(
        public float $lineLoad,
        public float $maximumMoment,
        public FundamentalUltimateCombinationExpression|ServiceabilityCombinationExpression $combinationReference,
        public string $formula,
    ) {}

    /** Magnitude utilisée par les équations de résistance, sans effacer le signe de MEd. */
    public function magnitude(): float
    {
        return abs($this->maximumMoment);
    }
}
