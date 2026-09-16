<?php

namespace App\StructuralCalculation\Beams;

/** Limitation de fissuration : le modèle existant requiert un étrier réellement sélectionné. */
final readonly class CantileverBeamCrackVerificationScopeResult
{
    public const LIMITATION = 'CANTILEVER_CRACK_VERIFICATION_REQUIRES_FIXED_END_STIRRUP_LAYOUT';

    public function __construct(
        public BeamReinforcementPosition $longitudinalReinforcementPosition,
        public float $longitudinalReinforcementArea,
        public float $longitudinalBarDiameter,
        public BeamTensionFace $tensionFace,
        public BeamCrackVerificationStatus $status,
        public string $limitation,
    ) {}
}
