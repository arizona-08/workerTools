<?php

namespace App\StructuralCalculation\Beams;

/** Résultat du filtre géométrique, conservant les candidats acceptés et rejetés. */
final readonly class BeamReinforcementGeometryCandidatesResult
{
    /** @param list<BeamReinforcementGeometryCheckResult> $acceptedCandidates @param list<BeamReinforcementGeometryCheckResult> $rejectedCandidates */
    public function __construct(
        public float $availableWidth,
        public array $acceptedCandidates,
        public array $rejectedCandidates,
    ) {}
}
