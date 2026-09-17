<?php

namespace App\StructuralCalculation\Beams;

enum BeamReinforcementTargetGoverningRequirement: string
{
    case FLEXURAL_DEMAND = 'FLEXURAL_DEMAND';
    case MINIMUM_REINFORCEMENT = 'MINIMUM_REINFORCEMENT';
    case EQUAL_REQUIREMENTS = 'EQUAL_REQUIREMENTS';
}
