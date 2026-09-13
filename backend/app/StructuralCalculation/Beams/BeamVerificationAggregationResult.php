<?php

namespace App\StructuralCalculation\Beams;

/** Statuts structurels agrégés, sans formule mécanique ni sélection gouvernante. */
final readonly class BeamVerificationAggregationResult
{
    /** @param list<string> $warnings */
    public function __construct(
        public BeamVerificationStatus $overallStatus,
        public BeamVerificationStatus $ulsStatus,
        public BeamVerificationStatus $slsStatus,
        public BeamVerificationComponent $flexureVerification,
        public BeamVerificationComponent $shearVerification,
        public BeamVerificationComponent $stressVerification,
        public BeamVerificationComponent $crackVerification,
        public BeamVerificationComponent $deflectionVerification,
        public array $warnings,
    ) {}
}
