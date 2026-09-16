<?php

namespace App\StructuralCalculation\Beams;

/** Position physique du lit principal d'armatures longitudinales. */
enum BeamReinforcementPosition: string
{
    case TOP = 'TOP';
    case BOTTOM = 'BOTTOM';
}
