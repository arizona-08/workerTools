<?php

namespace App\StructuralCalculation\Beams;

/** Résistances matériaux de calcul disponibles avant toute équation de flexion. */
final readonly class BeamFlexuralDesignStrengthsResult
{
    public function __construct(
        public BeamFlexuralConcreteDesignStrength $concrete,
        public BeamFlexuralSteelDesignStrength $steel,
    ) {}
}
