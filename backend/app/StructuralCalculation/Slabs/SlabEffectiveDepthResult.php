<?php

namespace App\StructuralCalculation\Slabs;

/** Hauteur utile de la bande Dalle, sans étrier enveloppant. */
final readonly class SlabEffectiveDepthResult
{
    public const UNIT = 'mm';

    public const FORMULA = 'd = h - c_nom - φ_main / 2';

    public function __construct(
        public float $overallDepth,
        public float $nominalCover,
        public float $preliminaryMainBarDiameter,
        public float $tensionSteelCentroidOffset,
        public float $effectiveDepth,
        public string $substitution,
    ) {}
}
