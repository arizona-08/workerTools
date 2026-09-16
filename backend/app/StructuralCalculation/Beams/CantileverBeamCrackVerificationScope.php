<?php

namespace App\StructuralCalculation\Beams;

/** Rend explicite l'absence d'étrier réel requis par le moteur de fissuration commun. */
final class CantileverBeamCrackVerificationScope
{
    public function assess(BeamReinforcementProposalCandidate $longitudinalReinforcement, BeamTensionFace $tensionFace): CantileverBeamCrackVerificationScopeResult
    {
        return new CantileverBeamCrackVerificationScopeResult(
            $longitudinalReinforcement->position,
            $longitudinalReinforcement->providedArea,
            $longitudinalReinforcement->barDiameter,
            $tensionFace,
            BeamCrackVerificationStatus::CALCULATION_METHOD_NOT_SUPPORTED,
            CantileverBeamCrackVerificationScopeResult::LIMITATION,
        );
    }
}
