<?php

namespace App\StructuralCalculation\Beams;

/** Géométrie traçable de flexion pour un unique lit d'armatures longitudinales tendues. */
final readonly class BeamEffectiveDepthResult
{
    public const UNIT = 'mm';

    public const FORMULA = 'd = h - c_nom - φ_st - φ_long / 2';

    public function __construct(
        public BeamCalculationMode $mode,
        public float $overallDepth,
        public float $nominalCover,
        public float $transverseBarDiameter,
        public float $longitudinalBarDiameter,
        public LongitudinalBarDiameterSource $longitudinalBarDiameterSource,
        public float $tensionSteelCentroidOffset,
        public float $effectiveDepth,
    ) {}
}
