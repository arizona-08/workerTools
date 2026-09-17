<?php

namespace App\StructuralCalculation\Beams;

/** Les trois combinaisons ELS du cas V1 à une seule action variable. */
final readonly class BeamServiceabilityCombinationsResult
{
    public function __construct(
        public BeamServiceabilityCombination $characteristic,
        public BeamServiceabilityCombination $frequent,
        public BeamServiceabilityCombination $quasiPermanent,
    ) {}
}
