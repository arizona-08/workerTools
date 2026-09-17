<?php

namespace App\StructuralCalculation\Beams;

/** Quantités théoriques d'étriers, sans proposition ni contrôle VRd,max. */
final readonly class BeamShearReinforcementDesignResult
{
    public const FORCE_UNIT = 'kN';

    public const REINFORCEMENT_PER_LENGTH_UNIT = 'mm²/mm';

    public const DISPLAY_REINFORCEMENT_PER_LENGTH_UNIT = 'mm²/m';

    public const LENGTH_UNIT = 'mm';

    public const STRENGTH_UNIT = 'MPa';

    public function __construct(
        public float $designShearForce,
        public float $concreteShearResistance,
        public bool $requiredByShearDemand,
        public float $webWidth,
        public float $leverArm,
        public float $stirrupSteelCharacteristicStrength,
        public float $stirrupSteelDesignStrength,
        public float $cotTheta,
        public float $minimumShearReinforcementRatio,
        public float $requiredShearReinforcementPerLength,
        public float $minimumShearReinforcementPerLength,
        public float $targetShearReinforcementPerLength,
        public BeamShearReinforcementGoverningRequirement $governingRequirement,
        public float $targetShearResistance,
    ) {}
}
