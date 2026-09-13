<?php

namespace App\StructuralCalculation\Beams;

enum BeamShearReinforcementGoverningRequirement: string
{
    case SHEAR_DEMAND = 'SHEAR_DEMAND';
    case MINIMUM_TRANSVERSE_REINFORCEMENT = 'MINIMUM_TRANSVERSE_REINFORCEMENT';
    case EQUAL_REQUIREMENTS = 'EQUAL_REQUIREMENTS';
}
