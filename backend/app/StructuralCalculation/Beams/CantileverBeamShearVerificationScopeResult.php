<?php

namespace App\StructuralCalculation\Beams;

/** Trace le VEd et la limitation normative avant toute résistance de cisaillement. */
final readonly class CantileverBeamShearVerificationScopeResult
{
    public const LIMITATION = 'CANTILEVER_FIXED_END_CRITICAL_SECTION_NOT_MODELLED';

    public function __construct(
        public BeamShearForce $designShearForce,
        public BeamShearCriticalSectionLocation $criticalSectionLocation,
        public float $criticalSectionPosition,
        public BeamReinforcementPosition $longitudinalReinforcementPosition,
        public float $longitudinalReinforcementArea,
        public BeamShearVerificationScopeStatus $status,
        public string $limitation,
    ) {}
}
