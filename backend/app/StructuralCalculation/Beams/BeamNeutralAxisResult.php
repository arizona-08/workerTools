<?php

namespace App\StructuralCalculation\Beams;

/** Axe neutre issu de la petite racine physique du bloc rectangulaire simplifié EC2. */
final readonly class BeamNeutralAxisResult
{
    public const LENGTH_UNIT = 'mm';

    public const DIMENSIONLESS_UNIT = 'dimensionless';

    public const EQUILIBRIUM_FORMULA = 'μEd = η × λ × ξ × (1 - λξ / 2)';

    public const RATIO_FORMULA = 'ξ = [1 - sqrt(1 - 2 × μEd / η)] / λ';

    public const DEPTH_FORMULA = 'x = ξ × d';

    public function __construct(
        public float $reducedDesignMoment,
        public float $characteristicConcreteStrength,
        public float $lambda,
        public float $eta,
        public float $radicand,
        public float $neutralAxisRatio,
        public float $effectiveDepth,
        public float $neutralAxisDepth,
    ) {}
}
