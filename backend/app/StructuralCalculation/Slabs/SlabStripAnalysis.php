<?php

namespace App\StructuralCalculation\Slabs;

/** Chaîne SLAB-06 : conversion de charge de bande puis sollicitations internes. */
final readonly class SlabStripAnalysis
{
    public const CALCULATION_STRIP_DESCRIPTION = 'bande de dalle de 1 m';

    public function __construct(
        public SlabStripLinearLoads $linearLoads,
        public SlabStripInternalForces $internalForces,
    ) {}
}
