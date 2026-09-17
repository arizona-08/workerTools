<?php

namespace App\StructuralCalculation\Beams;

enum BeamCalculationMode: string
{
    case DESIGN = 'DESIGN';
    case VERIFICATION = 'VERIFICATION';
}
