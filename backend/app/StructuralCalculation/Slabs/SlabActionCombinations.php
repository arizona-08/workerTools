<?php

namespace App\StructuralCalculation\Slabs;

/** Les quatre charges surfaciques combinées requises avant l'analyse de bande. */
final readonly class SlabActionCombinations
{
    public function __construct(
        public SlabSurfaceLoadCombination $uls,
        public SlabSurfaceLoadCombination $slsCharacteristic,
        public SlabSurfaceLoadCombination $slsFrequent,
        public SlabSurfaceLoadCombination $slsQuasiPermanent,
    ) {}
}
