<?php

namespace App\StructuralCalculation\Eurocode\Beams;

/** Paramètres nationaux applicables à l'armature longitudinale minimale des poutres. */
final readonly class BeamLongitudinalReinforcementRequirements
{
    public function __construct(
        public float $minimumReinforcementStrengthCoefficient,
        public float $minimumReinforcementRatio,
    ) {}
}
