<?php

namespace App\StructuralCalculation\Beams;

/** Armatures longitudinales tendues existantes ; un seul lit dans le MVP. */
final readonly class BeamLongitudinalReinforcement
{
    public float $providedSteelArea;

    public function __construct(
        public int $tensionBarCount,
        public float $tensionBarDiameter,
        public int $tensionRebarLayers = 1,
    ) {
        $this->providedSteelArea = $tensionBarCount * M_PI * $tensionBarDiameter ** 2 / 4;
    }
}
