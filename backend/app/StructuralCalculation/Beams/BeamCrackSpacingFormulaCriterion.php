<?php

namespace App\StructuralCalculation\Beams;

enum BeamCrackSpacingFormulaCriterion: string
{
    case CLOSELY_SPACED_BARS = 'CLOSELY_SPACED_BARS';
    case WIDELY_SPACED_BARS = 'WIDELY_SPACED_BARS';
}
