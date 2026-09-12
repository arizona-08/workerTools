<?php

namespace App\StructuralCalculation\Beams;

/** Traçabilité du placement horizontal d'un seul lit homogène entre les étriers. */
final readonly class BeamReinforcementGeometryCheckResult
{
    public const LENGTH_UNIT = 'mm';

    public function __construct(
        public BeamReinforcementProposalCandidate $candidate,
        public float $availableWidth,
        public float $maximumAggregateSize,
        public float $minimumClearSpacing,
        public BeamReinforcementSpacingGoverningCriterion $spacingGoverningCriterion,
        public float $requiredWidth,
        public float $remainingWidth,
        public bool $geometricallyAdmissible,
        public ?BeamReinforcementGeometryRejectionReason $rejectionReason,
    ) {}
}
