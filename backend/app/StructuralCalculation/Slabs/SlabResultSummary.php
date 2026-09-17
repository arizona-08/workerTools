<?php

namespace App\StructuralCalculation\Slabs;

use App\StructuralCalculation\Beams\BeamVerificationStatus;

/** Projection compacte des résultats source, sans nouveau calcul de dalle. */
final readonly class SlabResultSummary
{
    public function __construct(
        public BeamVerificationStatus $status,
        public ?float $utilization,
        public ?string $governingVerificationType,
        public ?float $designBendingMoment,
        public ?float $effectiveDepth,
        public ?float $requiredMainReinforcementArea,
        public ?float $minimumMainReinforcementArea,
        public ?SlabResultSummaryReinforcement $mainReinforcement,
        public ?SlabResultSummaryReinforcement $secondaryReinforcement,
    ) {}
}
