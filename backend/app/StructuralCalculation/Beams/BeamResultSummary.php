<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\ElementType;

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
        public ElementType $module = ElementType::BEAM,
        public BeamSubmodule $submodule = BeamSubmodule::BEAM_SIMPLE_RECTANGULAR,
        public BeamSupportSystem $supportSystem = BeamSupportSystem::SIMPLY_SUPPORTED,
        public ?float $designShearForce = null,
        public ?BeamShearCriticalSectionLocation $criticalSectionLocation = null,
    ) {}
}
