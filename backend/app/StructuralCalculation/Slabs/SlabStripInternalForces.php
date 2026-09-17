<?php

namespace App\StructuralCalculation\Slabs;

/** Sollicitations ELU et ELS de la bande unitaire, sans vérification de matériau. */
final readonly class SlabStripInternalForces
{
    public function __construct(
        public SlabStripInternalForce $uls,
        public SlabStripInternalForce $slsCharacteristic,
        public SlabStripInternalForce $slsFrequent,
        public SlabStripInternalForce $slsQuasiPermanent,
    ) {}
}
