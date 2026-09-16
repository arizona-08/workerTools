<?php

namespace App\StructuralCalculation\Beams;

/** Systèmes conceptuellement reconnus ; seul SIMPLY_SUPPORTED appartient au V1. */
enum BeamSupportSystem: string
{
    case SIMPLY_SUPPORTED = 'SIMPLY_SUPPORTED';
    case CONTINUOUS = 'CONTINUOUS';
    case CANTILEVER = 'CANTILEVER';
    case FIXED_ENDED = 'FIXED_ENDED';
    case MULTI_SPAN = 'MULTI_SPAN';
}
