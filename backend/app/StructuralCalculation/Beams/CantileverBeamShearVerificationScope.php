<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Units\LengthConverter;

/**
 * Détermine la section de contrôle de cisaillement d'une console sous UDL.
 *
 * EN 1992-1-1:2004 §6.2.1(8) autorise, pour une charge uniformément répartie
 * prédominante, un contrôle à d de la face de l'appui. Les étriers calculés à
 * cette section doivent néanmoins être prolongés jusqu'à l'encastrement ;
 * l'effort au nu est conservé pour le contrôle VRd,max de la chaîne existante.
 */
final readonly class CantileverBeamShearVerificationScope
{
    public const CRITICAL_SECTION_FORMULA = 'VEd(x) = wEd × (L - x), avec x = d';

    public function __construct(private LengthConverter $lengthConverter) {}

    public function assess(
        BeamCalculationConfiguration $configuration,
        BeamGeometry $geometry,
        BeamShearForce $fixedEndDesignShearForce,
        BeamEffectiveDepthResult $effectiveDepth,
        BeamReinforcementProposalCandidate $longitudinalReinforcement,
    ): CantileverBeamShearVerificationScopeResult {
        if ($configuration->submodule !== BeamSubmodule::BEAM_CANTILEVER_RECTANGULAR
            || $configuration->supportSystem !== BeamSupportSystem::CANTILEVER) {
            throw new BeamAnalysisException(BeamAnalysisRejectionReason::CALCULATION_METHOD_NOT_SUPPORTED);
        }
        if (! is_finite($effectiveDepth->effectiveDepth) || ! is_finite($geometry->effectiveSpan)
            || $effectiveDepth->effectiveDepth <= 0.0 || $geometry->effectiveSpan <= $effectiveDepth->effectiveDepth) {
            throw new BeamAnalysisException(BeamAnalysisRejectionReason::CALCULATION_METHOD_NOT_SUPPORTED);
        }

        $span = $this->lengthConverter->millimetresToMetres($geometry->effectiveSpan);
        $position = $this->lengthConverter->millimetresToMetres($effectiveDepth->effectiveDepth);
        $controlShear = $fixedEndDesignShearForce->lineLoad * ($span - $position);

        return new CantileverBeamShearVerificationScopeResult(
            fixedEndDesignShearForce: $fixedEndDesignShearForce,
            criticalSectionLocation: BeamShearCriticalSectionLocation::EFFECTIVE_DEPTH_FROM_FIXED_END,
            criticalSectionPosition: $effectiveDepth->effectiveDepth,
            criticalSectionDesignShearForce: new BeamShearForce(
                $fixedEndDesignShearForce->lineLoad,
                $controlShear,
                $controlShear,
                0.0,
                $fixedEndDesignShearForce->combinationReference,
                self::CRITICAL_SECTION_FORMULA,
            ),
            criticalSectionFormula: self::CRITICAL_SECTION_FORMULA,
            longitudinalReinforcementPosition: $longitudinalReinforcement->position,
            longitudinalReinforcementArea: $longitudinalReinforcement->providedArea,
        );
    }
}
