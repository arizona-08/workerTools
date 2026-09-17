<?php

namespace App\StructuralCalculation\Slabs;

use App\StructuralCalculation\Beams\BeamVerificationComponent;
use App\StructuralCalculation\Beams\BeamVerificationStatus;

/** Détail ordonné de projections des étapes SLAB-01 à SLAB-10. */
final readonly class SlabCalculationDetails
{
    public function __construct(
        public BeamVerificationStatus $overallStatus,
        public BeamVerificationStatus $ulsStatus,
        public BeamVerificationStatus $slsStatus,
        public ?BeamVerificationComponent $governingVerification,
        public array $assumptions,
        public SlabCharacteristicActions $characteristicActions,
        public array $combinations,
        public array $internalForces,
        public array $flexure,
        public array $mainReinforcement,
        public array $secondaryReinforcement,
        public array $serviceability,
        public array $warnings,
    ) {}
}
