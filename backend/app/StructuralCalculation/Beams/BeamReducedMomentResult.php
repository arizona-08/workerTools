<?php

namespace App\StructuralCalculation\Beams;

/** Moment réduit ELU traçable de la section rectangulaire, sans décision de domaine. */
final readonly class BeamReducedMomentResult
{
    public const DESIGN_MOMENT_UNIT = 'kN·m';

    public const DESIGN_MOMENT_NEWTON_MILLIMETRES_UNIT = 'N·mm';

    public const LENGTH_UNIT = 'mm';

    public const CONCRETE_STRENGTH_UNIT = 'MPa';

    public const DIMENSIONLESS_UNIT = 'dimensionless';

    public const FORMULA = 'μEd = MEd_Nmm / (b × d² × fcd)';

    public function __construct(
        public float $designMoment,
        public float $designMomentInNewtonMillimetres,
        public float $sectionWidth,
        public float $effectiveDepth,
        public float $concreteDesignStrength,
        public float $normalizationTerm,
        public float $reducedDesignMoment,
        public float $signedDesignMoment = 0.0,
    ) {}
}
