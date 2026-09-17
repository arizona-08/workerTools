<?php

namespace App\StructuralCalculation\Slabs;

/** Charges linéiques de la bande unitaire ; aucune sollicitation interne n'est déduite. */
final readonly class SlabStripLinearLoads
{
    public function __construct(
        public SlabStripLinearLoad $uls,
        public SlabStripLinearLoad $slsCharacteristic,
        public SlabStripLinearLoad $slsFrequent,
        public SlabStripLinearLoad $slsQuasiPermanent,
    ) {}
}
