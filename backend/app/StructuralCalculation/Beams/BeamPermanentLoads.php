<?php

namespace App\StructuralCalculation\Beams;

/**
 * Actions permanentes saisies pour une poutre V1.
 * additionalPermanentLoad est une charge linéaire en kN/m, hors poids propre.
 */
final readonly class BeamPermanentLoads
{
    public function __construct(
        public bool $includeSelfWeight,
        public float $additionalPermanentLoad,
    ) {}
}
