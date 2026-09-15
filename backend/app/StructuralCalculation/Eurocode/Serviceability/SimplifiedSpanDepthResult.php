<?php

namespace App\StructuralCalculation\Eurocode\Serviceability;

/** Résultat mathématique commun du contrôle simplifié EC2 §7.4.2, sans flèche en mm. */
final readonly class SimplifiedSpanDepthResult
{
    public function __construct(
        public float $actualSpanDepthRatio,
        public float $reinforcementRatio,
        public float $referenceReinforcementRatio,
        public string $formulaBranch,
        public float $baseAllowableSpanDepthRatio,
        public float $steelStressCorrectionFactor,
        public float $allowableSpanDepthRatio,
        public float $utilization,
    ) {}
}
