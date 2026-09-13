<?php

namespace App\StructuralCalculation\Beams;

enum BeamCrackStrainDifferenceCriterion: string
{
    case MAIN_STRAIN_EXPRESSION = 'MAIN_STRAIN_EXPRESSION';
    case MINIMUM_STRAIN_DIFFERENCE = 'MINIMUM_STRAIN_DIFFERENCE';
}
