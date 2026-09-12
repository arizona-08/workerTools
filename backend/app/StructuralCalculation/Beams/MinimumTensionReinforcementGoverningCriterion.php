<?php

namespace App\StructuralCalculation\Beams;

enum MinimumTensionReinforcementGoverningCriterion: string
{
    case FCTM_FYK = 'FCTM_FYK';
    case ABSOLUTE_RATIO = 'ABSOLUTE_RATIO';
}
