<?php

namespace App\StructuralCalculation\Beams;

/** Propositions classées d'étriers, sans conformité globale de poutre. */
final readonly class BeamStirrupProposalResult
{
    /** @param list<BeamStirrupProposalCandidate> $acceptedCandidates @param list<BeamStirrupProposalCandidate> $rejectedCandidates */
    public function __construct(
        public ?BeamStirrupProposalCandidate $recommendedCandidate,
        public array $acceptedCandidates,
        public array $rejectedCandidates,
        public bool $maximumShearResistanceValid,
    ) {}
}
