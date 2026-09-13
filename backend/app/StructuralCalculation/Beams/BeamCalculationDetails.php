<?php

namespace App\StructuralCalculation\Beams;

/** DTO complet pour les accordéons frontend, assemblé exclusivement depuis des résultats existants. */
final readonly class BeamCalculationDetails
{
    /** @param array<string, mixed> $assumptions @param array<string, mixed> $combinations @param array<string, mixed> $internalForces @param array<string, mixed> $flexure @param array<string, mixed> $reinforcement @param array<string, mixed> $shear @param array<string, mixed> $serviceability @param list<string> $warnings */
    public function __construct(
        public BeamVerificationStatus $overallStatus,
        public BeamVerificationStatus $ulsStatus,
        public BeamVerificationStatus $slsStatus,
        public ?BeamVerificationComponent $governingVerification,
        public array $assumptions,
        public array $combinations,
        public array $internalForces,
        public array $flexure,
        public array $reinforcement,
        public array $shear,
        public array $serviceability,
        public array $warnings,
    ) {}
}
