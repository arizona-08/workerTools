<?php

namespace App\StructuralCalculation\Beams;

enum BeamReinforcementSpacingGoverningCriterion: string
{
    case BAR_DIAMETER = 'BAR_DIAMETER';
    case AGGREGATE_SIZE = 'AGGREGATE_SIZE';
    case ABSOLUTE_MINIMUM = 'ABSOLUTE_MINIMUM';
    case TIE = 'TIE';
}
