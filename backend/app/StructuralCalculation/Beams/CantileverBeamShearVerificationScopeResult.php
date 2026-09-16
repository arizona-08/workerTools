<?php

namespace App\StructuralCalculation\Beams;

/** Trace les efforts au nu de l'encastrement et à la section EC2 située à d. */
final readonly class CantileverBeamShearVerificationScopeResult
{
    public const POSITION_UNIT = 'mm';

    public function __construct(
        public BeamShearForce $fixedEndDesignShearForce,
        public BeamShearCriticalSectionLocation $criticalSectionLocation,
        public float $criticalSectionPosition,
        public BeamShearForce $criticalSectionDesignShearForce,
        public string $criticalSectionFormula,
        public BeamReinforcementPosition $longitudinalReinforcementPosition,
        public float $longitudinalReinforcementArea,
    ) {}
}
