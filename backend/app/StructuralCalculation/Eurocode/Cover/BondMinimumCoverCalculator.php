<?php

namespace App\StructuralCalculation\Eurocode\Cover;

final class BondMinimumCoverCalculator
{
    /** MVP: c_min,b equals the diameter for a single passive reinforcing bar. */
    public function calculate(float $reinforcementDiameter): float
    {
        return $reinforcementDiameter;
    }
}
