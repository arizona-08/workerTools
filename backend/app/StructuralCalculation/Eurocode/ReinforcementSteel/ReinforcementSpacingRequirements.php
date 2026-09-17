<?php

namespace App\StructuralCalculation\Eurocode\ReinforcementSteel;

/** Paramètres nationaux d'espacement libre des armatures selon EC2 §8.2. */
final readonly class ReinforcementSpacingRequirements
{
    public function __construct(
        public float $barDiameterFactor,
        public float $aggregateSizeAllowance,
        public float $absoluteMinimumClearSpacing,
    ) {}
}
