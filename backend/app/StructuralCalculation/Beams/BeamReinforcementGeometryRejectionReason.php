<?php

namespace App\StructuralCalculation\Beams;

enum BeamReinforcementGeometryRejectionReason: string
{
    case INSUFFICIENT_HORIZONTAL_SPACE = 'INSUFFICIENT_HORIZONTAL_SPACE';
}
