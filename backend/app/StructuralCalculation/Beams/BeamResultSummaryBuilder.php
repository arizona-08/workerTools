<?php

namespace App\StructuralCalculation\Beams;

/** Mappe les sorties RESULT-01/02 et les résultats amont sans les recalculer. */
final class BeamResultSummaryBuilder
{
    public function build(
        BeamVerificationAggregationResult $aggregation,
        BeamGoverningVerificationResult $governing,
        BeamBendingMoment $designBendingMoment,
        BeamEffectiveDepthResult $effectiveDepth,
        BeamRequiredTensionReinforcementResult $requiredReinforcement,
        BeamReinforcementProposalCandidate $longitudinalReinforcement,
        BeamCalculationMode $mode,
        ?BeamCalculationConfiguration $configuration = null,
        ?BeamShearForce $designShearForce = null,
    ): BeamResultSummary {
        $configuration ??= BeamCalculationConfiguration::supported();

        return new BeamResultSummary(
            $governing->governingVerification?->utilization,
            $governing->governingVerification?->identifier,
            $designBendingMoment->maximumMoment,
            $effectiveDepth->effectiveDepth,
            $requiredReinforcement->requiredReinforcementArea,
            new BeamResultSummaryReinforcement(
                $mode === BeamCalculationMode::DESIGN ? BeamLongitudinalReinforcementSource::PROPOSED : BeamLongitudinalReinforcementSource::PROVIDED,
                $longitudinalReinforcement->barCount,
                $longitudinalReinforcement->barDiameter,
                $longitudinalReinforcement->providedArea,
                $longitudinalReinforcement->position,
            ),
            $aggregation->overallStatus,
            $configuration->elementType,
            $configuration->submodule,
            $configuration->supportSystem,
            $designShearForce?->maximumAbsoluteShear,
            $configuration->supportSystem === BeamSupportSystem::CANTILEVER ? BeamShearCriticalSectionLocation::FIXED_END : null,
        );
    }
}
