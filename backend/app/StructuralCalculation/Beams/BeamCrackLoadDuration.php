<?php

namespace App\StructuralCalculation\Beams;

enum BeamCrackLoadDuration: string
{
    case SHORT_TERM = 'SHORT_TERM';
    case LONG_TERM = 'LONG_TERM';
}
