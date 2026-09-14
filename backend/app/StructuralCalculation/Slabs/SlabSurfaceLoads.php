<?php

namespace App\StructuralCalculation\Slabs;

/** Charges caractéristiques surfaciques saisies pour la dalle MVP, en kN/m². */
final readonly class SlabSurfaceLoads
{
    public const UNIT = 'kN/m²';

    public function __construct(
        public float $finishes,
        public float $partitions,
        public float $otherPermanent,
        public float $imposedLoad,
    ) {}
}
