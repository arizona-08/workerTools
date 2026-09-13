<?php

namespace App\StructuralCalculation\Beams;

/** Projection compacte de résultats existants, destinée aux cartes frontend. */
final readonly class BeamResultSummary
{
    public function __construct(
        public ?float $utilization,
        public ?string $governingVerificationType,
        public float $designBendingMoment,
        public float $effectiveDepth,
        public float $requiredLongitudinalReinforcementArea,
        public BeamResultSummaryReinforcement $longitudinalReinforcement,
        public BeamVerificationStatus $status,
    ) {}
}
