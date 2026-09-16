<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Cover\CoverCalculationResult;

/** Résultat intermédiaire commun : flexion et proposition longitudinale, avant cisaillement/ELS. */
final readonly class BeamFlexuralDesignResult
{
    public function __construct(
        public CoverCalculationResult $cover,
        public BeamEffectiveDepthResult $initialEffectiveDepth,
        public BeamFlexuralDesignStrengthsResult $designStrengths,
        public BeamReducedMomentResult $reducedMoment,
        public BeamNeutralAxisResult $neutralAxis,
        public BeamLeverArmResult $leverArm,
        public BeamRequiredTensionReinforcementResult $requiredArea,
        public BeamMinimumTensionReinforcementResult $minimumArea,
        public BeamFlexuralDomainCheckResult $domain,
        public BeamRequiredReinforcementAreaResult $targetArea,
        public BeamReinforcementCandidatesResult $generatedCandidates,
        public BeamReinforcementGeometryCandidatesResult $geometryCandidates,
        public BeamReinforcementRecalculationCandidatesResult $recalculatedCandidates,
        public BeamReinforcementCandidateRecalculationResult $selectedCandidate,
    ) {}
}
