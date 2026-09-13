<?php

namespace App\StructuralCalculation\Beams;

enum BeamConcreteShearResistanceGoverningCriterion: string
{
    case MAIN_EXPRESSION = 'MAIN_EXPRESSION';
    case MINIMUM_SHEAR_RESISTANCE = 'MINIMUM_SHEAR_RESISTANCE';
    case EQUAL_RESISTANCES = 'EQUAL_RESISTANCES';
}
