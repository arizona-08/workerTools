<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Detailing\PreliminaryLongitudinalBarDiameter;

/** Hypothèses de detailing configurables pour la géométrie initiale de flexion V1. */
final readonly class BeamFlexuralDetailingAssumptions
{
    public function __construct(
        public float $transverseReinforcementDiameter,
        public float $designTensionBarDiameter,
    ) {}

    public static function supported(): self
    {
        return new self(
            transverseReinforcementDiameter: 8.0,
            designTensionBarDiameter: PreliminaryLongitudinalBarDiameter::DEFAULT_MILLIMETRES,
        );
    }
}
