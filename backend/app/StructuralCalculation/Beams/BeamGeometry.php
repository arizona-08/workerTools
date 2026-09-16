<?php

namespace App\StructuralCalculation\Beams;

/** Géométrie d'une poutre rectangulaire V1 ; les trois longueurs sont en mm. */
final readonly class BeamGeometry
{
    public function __construct(
        public float $effectiveSpan,
        public float $width,
        public float $height,
    ) {}
}
