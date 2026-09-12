<?php

namespace App\StructuralCalculation\Beams;

/** Liste triée de candidats de ferraillage avant toute validation géométrique. */
final readonly class BeamReinforcementCandidatesResult
{
    public const AREA_UNIT = 'mm²';

    /** @param list<float> $catalogueUsed @param list<BeamReinforcementProposalCandidate> $candidates */
    public function __construct(
        public float $targetArea,
        public array $catalogueUsed,
        public int $minimumBarCount,
        public int $maximumBarCount,
        public BeamReinforcementCandidatesStatus $status,
        public array $candidates,
        public int $candidateCount,
    ) {}
}
