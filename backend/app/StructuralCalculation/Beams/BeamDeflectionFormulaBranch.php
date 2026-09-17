<?php

namespace App\StructuralCalculation\Beams;

enum BeamDeflectionFormulaBranch: string
{
    case LOW_REINFORCEMENT_RATIO = 'LOW_REINFORCEMENT_RATIO';
    case HIGH_REINFORCEMENT_RATIO = 'HIGH_REINFORCEMENT_RATIO';
}
