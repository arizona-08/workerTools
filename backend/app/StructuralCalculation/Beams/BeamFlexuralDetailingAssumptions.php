<?php

namespace App\StructuralCalculation\Beams;

/** Hypothèses de detailing configurables pour la géométrie initiale de flexion MVP. */
final readonly class BeamFlexuralDetailingAssumptions
{
    public function __construct(
        public float $transverseReinforcementDiameter,
        public float $designTensionBarDiameter,
    ) {}

    public static function mvp(): self
    {
        return new self(
            transverseReinforcementDiameter: 8.0,
            designTensionBarDiameter: 16.0,
        );
    }
}
