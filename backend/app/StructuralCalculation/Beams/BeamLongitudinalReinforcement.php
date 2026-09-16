<?php

namespace App\StructuralCalculation\Beams;

use App\StructuralCalculation\Materials\ReinforcementSteel\ReinforcementBarAreaCalculator;

/** Armatures longitudinales tendues existantes ; un seul lit dans le V1. */
final readonly class BeamLongitudinalReinforcement
{
    public float $providedSteelArea;

    public function __construct(
        public int $tensionBarCount,
        public float $tensionBarDiameter,
        public int $tensionRebarLayers = 1,
        public BeamReinforcementPosition $position = BeamReinforcementPosition::BOTTOM,
    ) {
        $this->providedSteelArea = $tensionBarCount * (new ReinforcementBarAreaCalculator)->calculate($tensionBarDiameter);
    }
}
