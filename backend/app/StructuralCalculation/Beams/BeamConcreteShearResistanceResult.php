<?php

namespace App\StructuralCalculation\Beams;

/** Résultat traçable de la seule résistance VRd,c selon EN 1992-1-1 §6.2.2. */
final readonly class BeamConcreteShearResistanceResult
{
    public const FORCE_UNIT = 'kN';

    public const LENGTH_UNIT = 'mm';

    public const AREA_UNIT = 'mm²';

    public const STRESS_UNIT = 'MPa';

    public function __construct(
        public float $designShearForce,
        public float $webWidth,
        public float $effectiveDepth,
        public float $longitudinalReinforcementArea,
        public float $longitudinalReinforcementRatioRaw,
        public float $longitudinalReinforcementRatioUsed,
        public bool $longitudinalReinforcementRatioCapped,
        public float $sizeEffectFactorRaw,
        public float $sizeEffectFactor,
        public bool $sizeEffectFactorCapped,
        public float $normalForce,
        public float $concreteArea,
        public float $meanCompressiveStress,
        public float $characteristicConcreteStrength,
        public float $concreteDesignStrength,
        public float $concreteShearResistanceCoefficient,
        public float $compressionStressCoefficient,
        public float $minimumShearStress,
        public float $mainShearResistanceStress,
        public float $minimumShearResistanceStress,
        public float $governingResistanceStress,
        public BeamConcreteShearResistanceGoverningCriterion $governingCriterion,
        public float $concreteShearResistance,
        public float $utilizationConcreteShear,
        public BeamConcreteShearResistanceStatus $status,
    ) {}
}
