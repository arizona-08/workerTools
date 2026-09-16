<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Cover\CoverCalculationResult;
use App\StructuralCalculation\Eurocode\Profiles\DesignCodeProfile;
use App\StructuralCalculation\Materials\Concrete\ConcreteProperties;
use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementSteelProperties;

/** Orchestre une seule passe des calculateurs de flexion existants pour chaque candidat géométriquement admissible. */
final readonly class BeamReinforcementCandidateRecalculator
{
    public function __construct(
        private BeamEffectiveDepthCalculator $effectiveDepthCalculator,
        private BeamReducedMomentCalculator $reducedMomentCalculator,
        private BeamNeutralAxisCalculator $neutralAxisCalculator,
        private BeamLeverArmCalculator $leverArmCalculator,
        private BeamRequiredTensionReinforcementCalculator $requiredReinforcementCalculator,
        private BeamMinimumTensionReinforcementCalculator $minimumReinforcementCalculator,
        private BeamFlexuralDomainCheckCalculator $domainCheckCalculator,
        private BeamReinforcementTargetCalculator $targetCalculator,
    ) {}

    public function recalculate(
        BeamReinforcementGeometryCandidatesResult $geometryCandidates,
        BeamBendingMoment $ultimateMoment,
        BeamGeometry $geometry,
        CoverCalculationResult $cover,
        BeamFlexuralDetailingAssumptions $detailing,
        BeamFlexuralDesignStrengthsResult $designStrengths,
        ConcreteProperties $concrete,
        ReinforcementSteelProperties $steel,
        DesignCodeProfile $profile,
        BeamEffectiveDepthResult $initialEffectiveDepth,
        BeamRequiredTensionReinforcementResult $initialRequiredArea,
    ): BeamReinforcementRecalculationCandidatesResult {
        $valid = [];
        $rejected = [];
        foreach ($geometryCandidates->acceptedCandidates as $geometryCheck) {
            $recalculation = $this->recalculateCandidate(
                $geometryCheck->candidate, $ultimateMoment, $geometry, $cover, $detailing, $designStrengths,
                $concrete, $steel, $profile, $initialEffectiveDepth, $initialRequiredArea,
            );
            if ($recalculation->status === BeamReinforcementCandidateRecalculationStatus::VALID_AFTER_RECALCULATION) {
                $valid[] = $recalculation;
            } else {
                $rejected[] = $recalculation;
            }
        }

        return new BeamReinforcementRecalculationCandidatesResult($valid, $rejected);
    }

    private function recalculateCandidate(
        BeamReinforcementProposalCandidate $candidate,
        BeamBendingMoment $ultimateMoment,
        BeamGeometry $geometry,
        CoverCalculationResult $cover,
        BeamFlexuralDetailingAssumptions $detailing,
        BeamFlexuralDesignStrengthsResult $strengths,
        ConcreteProperties $concrete,
        ReinforcementSteelProperties $steel,
        DesignCodeProfile $profile,
        BeamEffectiveDepthResult $initialDepth,
        BeamRequiredTensionReinforcementResult $initialRequiredArea,
    ): BeamReinforcementCandidateRecalculationResult {
        $effectiveDepth = $this->effectiveDepthCalculator->calculateForCandidate($geometry, $cover, $detailing, $candidate->barDiameter, $candidate->position === BeamReinforcementPosition::TOP ? BeamTensionFace::TOP : BeamTensionFace::BOTTOM);
        $reducedMoment = $this->reducedMomentCalculator->calculate($ultimateMoment, $geometry, $effectiveDepth, $strengths->concrete);
        $neutralAxis = $this->neutralAxisCalculator->calculate($reducedMoment, $effectiveDepth, $strengths->concrete);
        $leverArm = $this->leverArmCalculator->calculate($effectiveDepth, $neutralAxis);
        $requiredArea = $this->requiredReinforcementCalculator->calculate($ultimateMoment, $strengths->steel, $leverArm);
        $minimumArea = $this->minimumReinforcementCalculator->calculate($concrete, $steel, $geometry, $effectiveDepth, $profile->beamLongitudinalReinforcementRequirements);
        $domain = $this->domainCheckCalculator->calculate($effectiveDepth, $neutralAxis, $strengths->concrete, $strengths->steel, $steel);

        if (! $domain->singlyReinforcedModelValid) {
            return new BeamReinforcementCandidateRecalculationResult(
                $candidate, $initialDepth->effectiveDepth, $initialRequiredArea->requiredReinforcementArea,
                $effectiveDepth, $reducedMoment, $neutralAxis, $leverArm, $requiredArea, $minimumArea,
                $domain, null, $candidate->providedArea, false,
                BeamReinforcementCandidateRecalculationStatus::INVALID_SINGLY_REINFORCED_DOMAIN,
            );
        }

        $targetArea = $this->targetCalculator->calculate($requiredArea, $minimumArea, $domain);
        $sufficient = $candidate->providedArea >= $targetArea->targetArea;

        return new BeamReinforcementCandidateRecalculationResult(
            $candidate, $initialDepth->effectiveDepth, $initialRequiredArea->requiredReinforcementArea,
            $effectiveDepth, $reducedMoment, $neutralAxis, $leverArm, $requiredArea, $minimumArea,
            $domain, $targetArea, $candidate->providedArea, $sufficient,
            $sufficient ? BeamReinforcementCandidateRecalculationStatus::VALID_AFTER_RECALCULATION : BeamReinforcementCandidateRecalculationStatus::INSUFFICIENT_AFTER_RECALCULATION,
        );
    }
}
