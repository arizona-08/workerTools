<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Units\ForceConverter;

/** Formule unique VRd,s des étriers verticaux, réutilisable par le dimensionnement et les propositions. */
final readonly class BeamShearReinforcementResistanceCalculator
{
    public function __construct(private ForceConverter $forceConverter) {}

    public function calculate(float $reinforcementPerLength, float $leverArm, float $steelDesignStrength, float $cotTheta): float
    {
        return $this->forceConverter->newtonsToKilonewtons($reinforcementPerLength * $leverArm * $steelDesignStrength * $cotTheta);
    }
}
