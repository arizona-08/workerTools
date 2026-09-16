<?php

namespace App\StructuralCalculation\Beams;

/** Localisation physique de la section dont le cisaillement est étudié. */
enum BeamShearCriticalSectionLocation: string
{
    case FIXED_END = 'FIXED_END';
}
