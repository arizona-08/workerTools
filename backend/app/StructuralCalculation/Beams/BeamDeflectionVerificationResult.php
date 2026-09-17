<?php

namespace App\StructuralCalculation\Beams;

/** Vérification locale l/d EC2 §7.4.2 ; aucune flèche physique n'est calculée. */
final readonly class BeamDeflectionVerificationResult
{
    public const LENGTH_UNIT = 'mm';

    public const AREA_UNIT = 'mm²';

    /** @param list<string> $warnings */
    public function __construct(
        public BeamDeflectionMethod $method,
        public BeamDeflectionVerificationStatus $applicabilityStatus,
        public float $effectiveSpan,
        public float $effectiveDepth,
        public ?float $actualSpanDepthRatio,
        public float $concreteCharacteristicStrength,
        public ?float $requiredReinforcementArea,
        public ?float $providedReinforcementArea,
        public ?float $reinforcementRatio,
        public ?float $referenceReinforcementRatio,
        public ?float $compressionReinforcementRatio,
        public BeamSupportSystem $structuralSystem,
        public ?float $structuralFactor,
        public ?BeamDeflectionFormulaBranch $formulaBranch,
        public ?float $baseAllowableSpanDepthRatio,
        public ?float $steelStressCorrectionFactor,
        public ?float $allowableSpanDepthRatio,
        public ?float $utilization,
        public BeamDeflectionVerificationStatus $status,
        public array $warnings,
    ) {}
}
