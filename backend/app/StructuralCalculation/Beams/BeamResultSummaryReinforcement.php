<?php

namespace App\StructuralCalculation\Beams;

final readonly class BeamResultSummaryReinforcement
{
    public function __construct(public BeamLongitudinalReinforcementSource $source, public int $barCount, public float $barDiameter, public float $providedArea, public BeamReinforcementPosition $position = BeamReinforcementPosition::BOTTOM) {}
}
