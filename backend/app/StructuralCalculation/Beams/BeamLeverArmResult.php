<?php

namespace App\StructuralCalculation\Beams;

/** Bras de levier interne du bloc comprimé rectangulaire, sans calcul d'armatures. */
final readonly class BeamLeverArmResult
{
    public const UNIT = 'mm';

    public const FORMULA = 'z = d - λ × x / 2';

    public const EQUIVALENT_FORMULA = 'z = d × (1 - λ × ξ / 2)';

    public function __construct(
        public float $effectiveDepth,
        public float $neutralAxisDepth,
        public float $neutralAxisRatio,
        public float $lambda,
        public float $compressionBlockDepth,
        public float $compressionResultantDepth,
        public float $leverArm,
    ) {}
}
