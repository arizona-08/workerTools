<?php

namespace App\StructuralCalculation\Slabs;

/** Contrôle local simplifié l/d ; aucune flèche physique en millimètres n'est calculée. */
final readonly class SlabDeflectionVerificationResult
{
    public const METHOD = 'SIMPLIFIED_SPAN_DEPTH';

    public function __construct(
        public SlabDeflectionVerificationStatus $status,
        public string $method,
        public ?float $effectiveSpan,
        public ?float $effectiveDepth,
        public ?float $actualSpanDepthRatio,
        public ?float $reinforcementRatio,
        public ?float $referenceReinforcementRatio,
        public ?float $structuralFactor,
        public ?float $steelStressCorrectionFactor,
        public ?float $allowableSpanDepthRatio,
        public ?float $utilization,
        public array $warnings = [],
    ) {}
}
