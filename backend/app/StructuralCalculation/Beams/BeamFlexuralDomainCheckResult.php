<?php

namespace App\StructuralCalculation\Beams;

/** Domaine de validité de σs = fyd pour l'équilibre utilisé par As_req. */
final readonly class BeamFlexuralDomainCheckResult
{
    public const LENGTH_UNIT = 'mm';

    public const STRENGTH_UNIT = 'MPa';

    public const STRAIN_UNIT = 'dimensionless';

    public const STEEL_STRAIN_FORMULA = 'εs = εcu3 × (1 - ξ) / ξ';

    public const YIELDING_NEUTRAL_AXIS_LIMIT_FORMULA = 'ξ_yield = εcu3 / (εcu3 + εyd)';

    public function __construct(
        public float $characteristicConcreteStrength,
        public float $concreteUltimateStrain,
        public float $steelDesignStrength,
        public float $steelElasticModulus,
        public float $steelDesignYieldStrain,
        public float $effectiveDepth,
        public float $neutralAxisDepth,
        public float $neutralAxisRatio,
        public ?float $tensionSteelStrain,
        public float $yieldingNeutralAxisLimit,
        public ?bool $tensionSteelReachesDesignYield,
        public bool $singlyReinforcedModelValid,
    ) {}
}
