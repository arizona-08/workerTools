<?php

namespace App\StructuralCalculation\Beams;

/** Candidats mécaniquement suffisants et rejetés après substitution du diamètre réel. */
final readonly class BeamReinforcementRecalculationCandidatesResult
{
    /** @param list<BeamReinforcementCandidateRecalculationResult> $validCandidates @param list<BeamReinforcementCandidateRecalculationResult> $rejectedCandidates */
    public function __construct(public array $validCandidates, public array $rejectedCandidates) {}
}
