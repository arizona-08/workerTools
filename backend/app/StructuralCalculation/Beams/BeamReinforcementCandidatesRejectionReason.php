<?php

namespace App\StructuralCalculation\Beams;

enum BeamReinforcementCandidatesRejectionReason: string
{
    case INVALID_TARGET_AREA = 'INVALID_TARGET_AREA';
    case INVALID_MINIMUM_TENSION_BAR_COUNT = 'INVALID_MINIMUM_TENSION_BAR_COUNT';
    case INVALID_MAXIMUM_TENSION_BAR_COUNT = 'INVALID_MAXIMUM_TENSION_BAR_COUNT';
}
