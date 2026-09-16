<?php

namespace App\StructuralCalculation\Slabs;

/** Géométrie d'une dalle pleine unidirectionnelle V1 ; toutes les longueurs sont en mm. */
final readonly class SlabGeometry
{
    public const CALCULATION_STRIP_WIDTH_MM = 1000.0;

    public float $calculationStripWidth;

    public function __construct(
        public float $effectiveSpan,
        public float $thickness,
    ) {
        $this->calculationStripWidth = self::CALCULATION_STRIP_WIDTH_MM;
    }
}
