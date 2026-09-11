<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Eurocode\Profiles\VariableActionCategory;

/** Action variable unique du MVP, uniformément répartie et exprimée en kN/m. */
final readonly class BeamVariableLoad
{
    public function __construct(
        public VariableActionCategory $category,
        public float $characteristicLoad,
    ) {}
}
