<?php

namespace App\StructuralCalculation\Slabs;

/** Conversion traçable d'une charge surfacique combinée en charge de bande. */
final readonly class SlabStripLinearLoad
{
    public const SURFACE_LOAD_UNIT = 'kN/m²';

    public const STRIP_WIDTH_UNIT = 'm';

    public const LINE_LOAD_UNIT = 'kN/m';

    public const FORMULA = 'w = q × b';

    public function __construct(
        public SlabSurfaceLoadCombination $surfaceLoadCombination,
        public float $stripWidthMillimetres,
        public float $stripWidthMetres,
        public float $surfaceLoad,
        public float $lineLoad,
        public string $name,
        public string $substitution,
    ) {}
}
