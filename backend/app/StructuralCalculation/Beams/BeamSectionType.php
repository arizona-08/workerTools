<?php

namespace App\StructuralCalculation\Beams;

/** Types conceptuellement reconnus ; seul RECTANGULAR appartient au V1. */
enum BeamSectionType: string
{
    case RECTANGULAR = 'RECTANGULAR';
    case T_SECTION = 'T_SECTION';
    case L_SECTION = 'L_SECTION';
    case VARIABLE = 'VARIABLE';
    case CIRCULAR = 'CIRCULAR';
}
