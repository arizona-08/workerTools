<?php

namespace App\StructuralCalculation\Beams;

/** Candidate d'aire uniquement : le logement géométrique est hors de cette étape. */
final readonly class BeamReinforcementProposalCandidate
{
    public const DIAMETER_UNIT = 'mm';

    public const AREA_UNIT = 'mm²';

    public function __construct(
        public int $barCount,
        public float $barDiameter,
        public float $barArea,
        public float $providedArea,
        public float $targetArea,
        public float $excessArea,
        public float $utilizationRatio,
    ) {}
}
