<?php

namespace App\StructuralCalculation\Beams;

/**
 * Cadre la vérification locale au voisinage de l'encastrement.
 *
 * Les règles V1 de VRd,c et de proposition d'étriers excluent actuellement les
 * zones d'appui. Aucune distance de section de contrôle n'étant documentée pour
 * la console, ce service ne transmet pas de valeur fabriquée aux calculateurs.
 */
final class CantileverBeamShearVerificationScope
{
    public function assess(
        BeamCalculationConfiguration $configuration,
        BeamShearForce $designShearForce,
        BeamReinforcementProposalCandidate $longitudinalReinforcement,
    ): CantileverBeamShearVerificationScopeResult {
        if ($configuration->submodule !== BeamSubmodule::BEAM_CANTILEVER_RECTANGULAR
            || $configuration->supportSystem !== BeamSupportSystem::CANTILEVER) {
            throw new BeamAnalysisException(BeamAnalysisRejectionReason::CALCULATION_METHOD_NOT_SUPPORTED);
        }

        return new CantileverBeamShearVerificationScopeResult(
            designShearForce: $designShearForce,
            criticalSectionLocation: BeamShearCriticalSectionLocation::FIXED_END,
            criticalSectionPosition: 0.0,
            longitudinalReinforcementPosition: $longitudinalReinforcement->position,
            longitudinalReinforcementArea: $longitudinalReinforcement->providedArea,
            status: BeamShearVerificationScopeStatus::CALCULATION_METHOD_NOT_SUPPORTED,
            limitation: CantileverBeamShearVerificationScopeResult::LIMITATION,
        );
    }
}
