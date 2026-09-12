<?php

namespace App\StructuralCalculation\Beams;

/** Résistance VRd,max des bielles comprimées, distincte de VRd,s et VRd,c. */
final readonly class BeamMaximumShearResistanceResult
{
    public const FORCE_UNIT = 'kN';

    public const LENGTH_UNIT = 'mm';

    public const STRENGTH_UNIT = 'MPa';

    public function __construct(
        public float $designShearForce,
        public float $webWidth,
        public float $leverArm,
        public float $characteristicConcreteStrength,
        public float $concreteDesignStrength,
        public float $alphaCw,
        public float $concreteShearStrengthReductionFactor,
        public float $cotTheta,
        public float $tanTheta,
        public float $maximumShearResistance,
        public float $utilizationMaximumShear,
        public BeamMaximumShearResistanceStatus $status,
    ) {}
}
