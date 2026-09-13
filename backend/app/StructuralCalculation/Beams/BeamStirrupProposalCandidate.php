<?php

namespace App\StructuralCalculation\Beams;

/** Candidat discret d'étrier et trace de toutes ses contraintes MVP. */
final readonly class BeamStirrupProposalCandidate
{
    /** @param list<BeamStirrupProposalRejectionReason> $rejectionReasons */
    public function __construct(
        public float $barDiameter,
        public int $legCount,
        public float $barArea,
        public float $providedArea,
        public float $spacing,
        public float $providedAreaPerLength,
        public float $targetAreaPerLength,
        public float $reinforcementExcess,
        public float $targetUtilization,
        public float $requiredMaximumSpacing,
        public float $maximumLongitudinalSpacing,
        public float $transverseLegSpacing,
        public float $maximumTransverseLegSpacing,
        public float $providedShearResistance,
        public bool $accepted,
        public array $rejectionReasons,
    ) {}
}
