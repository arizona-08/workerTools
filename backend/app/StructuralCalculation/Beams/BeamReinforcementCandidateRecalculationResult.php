<?php

namespace App\StructuralCalculation\Beams;

/** Une passe de recalcul de flexion avec le diamètre fixe d'un candidat. */
final readonly class BeamReinforcementCandidateRecalculationResult
{
    public function __construct(
        public BeamReinforcementProposalCandidate $originalCandidate,
        public float $initialEffectiveDepth,
        public float $initialFlexuralRequiredArea,
        public BeamEffectiveDepthResult $effectiveDepth,
        public BeamReducedMomentResult $reducedMoment,
        public BeamNeutralAxisResult $neutralAxis,
        public BeamLeverArmResult $leverArm,
        public BeamRequiredTensionReinforcementResult $requiredArea,
        public BeamMinimumTensionReinforcementResult $minimumArea,
        public BeamFlexuralDomainCheckResult $flexuralDomain,
        public ?BeamRequiredReinforcementAreaResult $targetArea,
        public float $providedArea,
        public bool $sufficientAfterRecalculation,
        public BeamReinforcementCandidateRecalculationStatus $status,
    ) {}
}
