<?php

namespace App\StructuralCalculation\Slabs;

/** Projection d'un ferraillage de dalle réellement retenu, pour le contrat final. */
final readonly class SlabResultSummaryReinforcement
{
    public function __construct(public float $diameter, public float $spacing, public float $providedAreaPerMeter) {}
}
