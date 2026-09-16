<?php

namespace App\StructuralCalculation\Beams;

/** Catalogues applicatifs V1 d'étriers, sans valeur normative implicite. */
final readonly class BeamStirrupProposalConfiguration
{
    /** @param list<float> $diameters @param list<float> $spacings */
    public function __construct(
        public array $diameters = [6.0, 8.0, 10.0, 12.0],
        public int $stirrupLegs = 2,
        public array $spacings = [100.0, 125.0, 150.0, 175.0, 200.0, 225.0, 250.0, 300.0, 350.0, 400.0],
    ) {}
}
